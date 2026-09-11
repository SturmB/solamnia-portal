<?php

use App\Enums\InviteStatus;
use App\Models\Invite;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * LLDAP answers the happy path: no such user, create it, find `members`, add.
 */
function fakeLldapProvisioning(): void
{
    Http::fake([
        'lldap/auth/simple/login' => Http::response(['token' => 'jwt-abc']),
        'lldap/api/graphql' => Http::sequence()
            ->push(['data' => ['users' => []]])
            ->push(['data' => ['createUser' => ['id' => 'brightblade']]])
            ->push(['data' => ['groups' => [['id' => 3, 'displayName' => 'members']]]])
            ->push(['data' => ['addUserToGroup' => ['ok' => true]]]),
    ]);
}

beforeEach(function () {
    $this->rawToken = Str::random(64);
    $this->invite = Invite::factory()->create([
        'email' => 'sturm@example.com',
        'suggested_name' => 'Sturm',
        'token' => hash('sha256', $this->rawToken),
    ]);

    Http::fake(['api.pushover.net/*' => Http::response()]);
    Http::preventStrayRequests();

    config([
        'services.authelia.base_url' => 'http://auth',
        'services.lldap.base_url' => 'http://lldap',
        'services.pushover.token' => 'pushover-token',
        'services.pushover.user' => 'pushover-user',
    ]);
});

it('renders the invite acceptance form', function () {
    $this->get(route('invites.show', $this->rawToken))
        ->assertOk()
        ->assertSee('sturm@example.com')
        ->assertSee('name="username"', escape: false)
        ->assertSee('id="username"', escape: false)
        ->assertSee('value="Sturm"', escape: false)
        ->assertSee(route('invites.accept', $this->rawToken), escape: false);
});

it('rejects a badly formed username and touches nothing', function (string $username) {
    $this->post(route('invites.accept', $this->rawToken), [
        'username' => $username,
        'name' => 'Sturm',
    ])->assertRedirectBackWithErrors('username');

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Pending);
    Http::assertNothingSent();
})->with([
    'too short' => 'ab',
    'starts with a digit' => '1sturm',
    'illegal character' => 'sturm!',
    'too long' => str_repeat('a', 33),
]);

it('provisions the Member into LLDAP and stamps the Invite', function () {
    fakeLldapProvisioning();

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'Brightblade', // capitalised on purpose: proves normalisation
        'name' => 'Sturm Brightblade',
    ])
        ->assertOk()
        ->assertSee('brightblade')
        ->assertSee('http://auth/reset-password/step1', escape: false);

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Accepted)
        ->and($this->invite->fresh()->username)->toBe('brightblade');

    Http::assertSent(fn ($request) => Str::endsWith($request->url(), '/api/graphql')
        && $request->hasHeader('Authorization', 'Bearer jwt-abc')
        && Str::contains($request['query'], 'createUser')
        && $request['variables']['user'] === [
            'id' => 'brightblade',
            'email' => 'sturm@example.com',
            'displayName' => 'Sturm Brightblade',
        ]);

    Http::assertSent(fn ($request) => Str::endsWith($request->url(), '/api/graphql')
        && $request->hasHeader('Authorization', 'Bearer jwt-abc')
        && Str::contains($request['query'], 'addUserToGroup')
        && $request['variables'] === [
            'userId' => 'brightblade',
            'groupId' => 3,
        ]);

    Http::assertSent(fn ($request) => Str::contains($request->url(), 'pushover')
        && Str::contains($request['message'], 'brightblade'));
});

it('rejects a username that already exists in LLDAP', function () {
    Http::fake([
        'lldap/auth/simple/login' => Http::response(['token' => 'jwt-abc']),
        'lldap/api/graphql' => Http::response([
            'data' => ['users' => [['id' => 'brightblade', 'email' => 'someone-else@example.com']]],
        ]),
    ]);

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
    ])->assertSessionHasErrors('username');

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Pending);
    Http::assertNotSent(fn ($request) => Str::contains($request['query'] ?? '', 'createUser'));
});

it('leaves the Invite pending and renders the retry page when LLDAP fails', function (array $responses) {
    $sequence = Http::sequence();
    foreach ($responses as $response) {
        $sequence->push($response);
    }
    Http::fake([
        'lldap/auth/simple/login' => Http::response(['token' => 'jwt-abc']),
        'lldap/api/graphql' => $sequence,
    ]);

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
    ])
        ->assertOk()
        ->assertSee('try again');

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Pending)
        ->and($this->invite->fresh()->username)->toBeNull();
    Http::assertSent(fn ($request) => Str::contains($request['message'] ?? '', 'Directory on fire'));
})->with([
    'on create' => [[
        ['data' => ['users' => []]],
        ['errors' => [['message' => 'Directory on fire']]],
    ]],
    'on group-add' => [[
        ['data' => ['users' => []]],
        ['data' => ['createUser' => ['id' => 'brightblade']]],
        ['data' => ['groups' => [['id' => 3, 'displayName' => 'members']]]],
        ['errors' => [['message' => 'Directory on fire']]],
    ]],
]);

it('continues to grouping when the user already exists from a failed attempt', function () {
    Http::fake([
        'lldap/auth/simple/login' => Http::response(['token' => 'jwt-abc']),
        'lldap/api/graphql' => Http::sequence()
            ->push(['data' => ['users' => [['id' => 'brightblade', 'email' => 'sturm@example.com']]]])
            ->push(['data' => ['groups' => [['id' => 3, 'displayName' => 'members']]]])
            ->push(['data' => ['addUserToGroup' => ['ok' => true]]]),
    ]);

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
    ])->assertOk();

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Accepted)
        ->and($this->invite->fresh()->username)->toBe('brightblade');
    Http::assertNotSent(fn ($request) => Str::contains($request['query'] ?? '', 'createUser'));
    Http::assertSent(fn ($request) => Str::contains($request['query'] ?? '', 'addUserToGroup'));
});

it('shows the friendly page instead of provisioning for a dead or unknown token', function (?string $state) {
    $rawToken = Str::random(64);
    if ($state !== null) {
        Invite::factory()->{$state}()->create(['token' => hash('sha256', $rawToken)]);
    }

    $this->post(route('invites.accept', $rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
    ])
        ->assertOk()
        ->assertSee('no longer valid');

    Http::assertNothingSent();
})->with(['accepted', 'unknown' => null]);

it('maps a duplicate-user error on create to the taken error', function () {
    Http::fake([
        'lldap/auth/simple/login' => Http::response(['token' => 'jwt-abc']),
        'lldap/api/graphql' => Http::sequence()
            ->push(['data' => ['users' => []]])
            ->push(['errors' => [['message' => 'Entity already exists']]]),
    ]);

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
    ])->assertSessionHasErrors('username');

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Pending);
    Http::assertNotSent(fn ($request) => Str::contains($request->url(), 'pushover'));
});

it('renders the retry page when LLDAP is unreachable', function () {
    Http::fake(['lldap/*' => Http::failedConnection()]);

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
    ])
        ->assertOk()
        ->assertSee('try again');

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Pending);
    Http::assertSent(fn ($request) => Str::contains($request['message'] ?? '', 'unreachable'));
});

it('writes the eager shadow row after acceptance', function () {
    fakeLldapProvisioning();

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
    ])->assertOk();

    $shadow = User::where('email', 'sturm@example.com')->sole();

    expect($shadow->name)->toBe('Sturm Brightblade')
        ->and($shadow->email_verified_at)->not->toBeNull()
        ->and($shadow->oidc_sub)->toBeNull()
        ->and($shadow->password)->toBeNull()
        ->and($shadow->is_admin)->toBeFalse();
});

it('creates a Subscriber after acceptance when none exists', function () {
    fakeLldapProvisioning();

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
    ])->assertOk();

    $subscriber = Subscriber::where('email', 'sturm@example.com')->sole();

    expect($subscriber->name)->toBe('Sturm Brightblade')
        ->and($subscriber->unsubscribed_at)->toBeNull();
});

it('adopts an existing unsubscribed Subscriber without resubscribing them', function () {
    $unsubscribed = Subscriber::factory()->unsubscribed()->create([
        'email' => 'sturm@example.com',
        'name' => 'Old Name',
    ]);
    fakeLldapProvisioning();

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
    ])->assertOk();

    expect(Subscriber::count())->toBe(1)
        ->and($unsubscribed->fresh()->unsubscribed_at)->not->toBeNull()
        ->and($unsubscribed->fresh()->name)->toBe('Old Name');
});

it('keeps the success response when a follow-up throws, and reports it', function (string $model, string $survivor) {
    Exceptions::fake();
    $model::creating(fn () => throw new RuntimeException('Table on fire'));
    fakeLldapProvisioning();

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
    ])
        ->assertOk()
        ->assertSee('brightblade');

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Accepted)
        ->and($model::where('email', 'sturm@example.com')->exists())->toBeFalse()
        ->and($survivor::where('email', 'sturm@example.com')->exists())->toBeTrue();

    Exceptions::assertReported(RuntimeException::class);
    Http::assertSent(fn ($request) => Str::contains($request->url(), 'pushover')
        && $request['priority'] === 1
        && Str::contains($request['message'], 'Table on fire'));
})->with([
    'users row' => [User::class, Subscriber::class],
    'Subscriber' => [Subscriber::class, User::class],
]);

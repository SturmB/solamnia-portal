<?php

use App\Enums\InviteStatus;
use App\Jobs\ShareMediaLibraries;
use App\Models\Invite;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\LdapConnection;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery\MockInterface;

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

beforeEach(function (): void {
    $this->rawToken = Str::random(64);
    $this->invite = Invite::factory()->create([
        'email' => 'sturm@example.com',
        'suggested_name' => 'Sturm',
        'plain_token' => $this->rawToken,
    ]);

    Http::fake([
        'api.pushover.net/*' => Http::response(),
        // Every range lookup reports one breached password: "password1234", seen 1,000 times.
        'api.pwnedpasswords.com/*' => Http::response('password1234'
                |> sha1(...)
                |> strtoupper(...)
                |> (fn (string $hash): string => substr($hash, 5).':1000')),
    ]);
    Http::preventStrayRequests();
    Queue::fake();

    config([
        'services.authelia.base_url' => 'http://auth',
        'services.lldap.base_url' => 'http://lldap',
        'services.pushover.token' => 'pushover-token',
        'services.pushover.user' => 'pushover-user',
        'services.lldap.username' => 'solamnia-portal-svc',
        'services.lldap.password' => 'svc-secret',
        'services.lldap.base_dn' => 'dc=solamnia,dc=tv',
    ]);

    $this->ldap = $this->mock(LdapConnection::class, function (MockInterface $mock): void {
        $mock->shouldReceive('bind')->andReturnTrue();
        $mock->shouldReceive('exopPasswd')->andReturnTrue();
    });
});

it('renders the invite acceptance form', function (): void {
    $this->get(route('invites.show', $this->rawToken))
        ->assertOk()
        ->assertSee('sturm@example.com')
        ->assertSee('name="username"', escape: false)
        ->assertSee('id="username"', escape: false)
        ->assertSee('value="Sturm"', escape: false)
        ->assertSee('type="password"', escape: false)
        ->assertSee('name="password"', escape: false)
        ->assertSee('autocomplete="new-password"', escape: false)
        ->assertSee('Create my account')
        ->assertSee(route('invites.accept', $this->rawToken), escape: false)
        ->assertDontSee('data-error-summary', escape: false);
});

it('rejects a badly formed username and touches nothing', function (string $username): void {
    $this->post(route('invites.accept', $this->rawToken), [
        'username' => $username,
        'name' => 'Sturm',
        'password' => 'correct horse battery staple',
    ])->assertRedirectBackWithErrors('username');

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Pending);
    Http::assertNotSent(fn ($request): bool => Str::contains($request->url(), 'lldap'));
})->with([
    'too short' => 'ab',
    'starts with a digit' => '1sturm',
    'illegal character' => 'sturm!',
    'too long' => str_repeat('a', 33),
]);

it('provisions the Member, sets their password, and hands off to SSO', function (): void {
    fakeLldapProvisioning();

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'Brightblade', // capitalized on purpose: proves normalization
        'name' => 'Sturm Brightblade',
        'password' => 'correct horse battery staple',
    ])->assertRedirectToRoute('auth.redirect');

    $this->ldap->shouldHaveReceived('exopPasswd')
        ->with('uid=brightblade,ou=people,dc=solamnia,dc=tv', 'correct horse battery staple');

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Accepted)
        ->and($this->invite->fresh()->username)->toBe('brightblade');

    Http::assertSent(fn ($request): bool => Str::endsWith($request->url(), '/api/graphql')
        && $request->hasHeader('Authorization', 'Bearer jwt-abc')
        && Str::contains($request['query'], 'createUser')
        && $request['variables']['user'] === [
            'id' => 'brightblade',
            'email' => 'sturm@example.com',
            'displayName' => 'Sturm Brightblade',
        ]);

    Http::assertSent(fn ($request): bool => Str::endsWith($request->url(), '/api/graphql')
        && $request->hasHeader('Authorization', 'Bearer jwt-abc')
        && Str::contains($request['query'], 'addUserToGroup')
        && $request['variables'] === [
            'userId' => 'brightblade',
            'groupId' => 3,
        ]);

    Http::assertSent(fn ($request): bool => Str::contains($request->url(), 'pushover')
        && Str::contains($request['message'], 'brightblade'));
});

it('queues the media-server invite after acceptance', function (): void {
    fakeLldapProvisioning();

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
        'password' => 'correct horse battery staple',
    ])->assertRedirectToRoute('auth.redirect');

    Queue::assertPushed(ShareMediaLibraries::class, fn (ShareMediaLibraries $job): bool => $job->email === 'sturm@example.com');
});

it('mentions the coming Plex invite only when the media server is configured', function (?string $token, ?string $inviter, ?string $expected): void {
    config([
        'services.plex.token' => $token,
        'services.plex.inviter_email' => $inviter,
    ]);

    $response = $this->get(route('invites.show', $this->rawToken))->assertOk();

    $expected === null
        ? $response->assertDontSee('Plex')
        : $response->assertSee($expected);
})->with([
    'with an inviter' => ['plex-token', 'owner@example.com', 'Plex will email you an invite from owner@example.com'],
    'without an inviter' => ['plex-token', '', 'Plex will email you an invite.'],
    'unconfigured' => [null, 'owner@example.com', null],
]);

it('rejects a username that already exists in LLDAP', function (): void {
    Http::fake([
        'lldap/auth/simple/login' => Http::response(['token' => 'jwt-abc']),
        'lldap/api/graphql' => Http::response([
            'data' => ['users' => [['id' => 'brightblade', 'email' => 'someone-else@example.com']]],
        ]),
    ]);

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
        'password' => 'correct horse battery staple',
    ])->assertSessionHasErrors('username');

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Pending);
    Http::assertNotSent(fn ($request) => Str::contains($request['query'] ?? '', 'createUser'));
});

it('leaves the Invite pending and renders the retry page when LLDAP fails', function (array $responses): void {
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
        'password' => 'correct horse battery staple',
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

it('continues to grouping when the user already exists from a failed attempt', function (): void {
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
        'password' => 'correct horse battery staple',
    ])->assertRedirectToRoute('auth.redirect');

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Accepted)
        ->and($this->invite->fresh()->username)->toBe('brightblade');
    Http::assertNotSent(fn ($request) => Str::contains($request['query'] ?? '', 'createUser'));
    Http::assertSent(fn ($request) => Str::contains($request['query'] ?? '', 'addUserToGroup'));
    $this->ldap->shouldHaveReceived('exopPasswd')
        ->with('uid=brightblade,ou=people,dc=solamnia,dc=tv', 'correct horse battery staple');
});

it('shows the friendly page instead of provisioning for a dead or unknown token', function (?string $state): void {
    $rawToken = Str::random(64);
    if ($state !== null) {
        Invite::factory()->{$state}()->create(['plain_token' => $rawToken]);
    }

    $this->post(route('invites.accept', $rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
        'password' => 'correct horse battery staple',
    ])
        ->assertOk()
        ->assertSee('no longer valid');

    Http::assertNothingSent();
})->with(['accepted', 'unknown' => null]);

it('maps a duplicate-user error on create to the taken error', function (): void {
    Http::fake([
        'lldap/auth/simple/login' => Http::response(['token' => 'jwt-abc']),
        'lldap/api/graphql' => Http::sequence()
            ->push(['data' => ['users' => []]])
            ->push(['errors' => [['message' => 'Entity already exists']]]),
    ]);

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
        'password' => 'correct horse battery staple',
    ])->assertSessionHasErrors('username');

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Pending);
    Http::assertNotSent(fn ($request) => Str::contains($request->url(), 'pushover'));
});

it('renders the retry page when LLDAP is unreachable', function (): void {
    Http::fake(['lldap/*' => Http::failedConnection()]);

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
        'password' => 'correct horse battery staple',
    ])
        ->assertOk()
        ->assertSee('try again');

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Pending);
    Http::assertSent(fn ($request) => Str::contains($request['message'] ?? '', 'unreachable'));
});

it('writes the eager shadow row after acceptance', function (): void {
    fakeLldapProvisioning();

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
        'password' => 'correct horse battery staple',
    ])->assertRedirectToRoute('auth.redirect');

    $shadow = User::where('email', 'sturm@example.com')->sole();

    expect($shadow->name)->toBe('Sturm Brightblade')
        ->and($shadow->email_verified_at)->not->toBeNull()
        ->and($shadow->oidc_sub)->toBeNull()
        ->and($shadow->password)->toBeNull()
        ->and($shadow->is_admin)->toBeFalse();
});

it('creates a Subscriber after acceptance when none exists', function (): void {
    fakeLldapProvisioning();

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
        'password' => 'correct horse battery staple',
    ])->assertRedirectToRoute('auth.redirect');

    $subscriber = Subscriber::where('email', 'sturm@example.com')->sole();

    expect($subscriber->name)->toBe('Sturm Brightblade')
        ->and($subscriber->unsubscribed_at)->toBeNull();
});

it('adopts an existing unsubscribed Subscriber without resubscribing them', function (): void {
    $unsubscribed = Subscriber::factory()->unsubscribed()->create([
        'email' => 'sturm@example.com',
        'name' => 'Old Name',
    ]);
    fakeLldapProvisioning();

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
        'password' => 'correct horse battery staple',
    ])->assertRedirectToRoute('auth.redirect');

    expect(Subscriber::count())->toBe(1)
        ->and($unsubscribed->fresh()->unsubscribed_at)->not->toBeNull()
        ->and($unsubscribed->fresh()->name)->toBe('Old Name');
});

it('keeps the success response when a follow-up throws, and reports it', function (string $model, string $survivor): void {
    Exceptions::fake();
    $model::creating(fn () => throw new RuntimeException('Table on fire'));
    fakeLldapProvisioning();

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
        'password' => 'correct horse battery staple',
    ])
        ->assertRedirectToRoute('auth.redirect');

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Accepted)
        ->and($model::where('email', 'sturm@example.com')->exists())->toBeFalse()
        ->and($survivor::where('email', 'sturm@example.com')->exists())->toBeTrue();

    Exceptions::assertReported(RuntimeException::class);
    Http::assertSent(fn ($request): bool => Str::contains($request->url(), 'pushover')
        && $request['priority'] === 1
        && Str::contains($request['message'], 'Table on fire'));
})->with([
    'users row' => [User::class, Subscriber::class],
    'Subscriber' => [Subscriber::class, User::class],
]);

it('rejects a weak password inline and touches nothing', function (string $password): void {
    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
        'password' => $password,
    ])->assertRedirectBackWithErrors('password');

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Pending);
    $this->ldap->shouldNotHaveReceived('exopPasswd');
    Http::assertNotSent(fn ($request): bool => Str::contains($request->url(), 'lldap'));
})->with([
    'too short' => 'eleven-char',
    'breached' => 'password1234',
]);

it('never flashes the password back into the form', function (): void {
    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
        'password' => 'eleven-char',
    ]);

    expect(session()->getOldInput())
        ->toHaveKey('username')
        ->not->toHaveKey('password');
});

it('leaves the Invite pending and offers a retry when setting the password fails', function (): void {
    $this->mock(LdapConnection::class, function (MockInterface $mock): void {
        $mock->shouldReceive('bind')->andReturnTrue();
        $mock->shouldReceive('exopPasswd')->andReturnFalse();
        $mock->shouldReceive('error')->andReturn('Insufficient access');
    });
    fakeLldapProvisioning();

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
        'password' => 'correct horse battery staple',
    ])
        ->assertOk()
        ->assertSee('try again');

    expect($this->invite->fresh()->status())->toBe(InviteStatus::Pending);
    Http::assertNotSent(fn ($request): bool => Str::contains($request['query'] ?? '', 'addUserToGroup'));
    Http::assertSent(fn ($request): bool => Str::contains($request['message'] ?? '', 'Insufficient access'));
});

it('keeps the password out of the logs when an unexpected error is reported', function (): void {
    app()->instance(LdapConnection::class, new class extends LdapConnection
    {
        /**
         * Skips the real constructor, which reads the LDAP URL and opens a connection.
         *
         * @noinspection PhpMissingParentConstructorInspection
         */
        public function __construct() {}

        public function bind(string $dn, #[SensitiveParameter] string $password): bool
        {
            return true;
        }

        public function exopPasswd(string $dn, #[SensitiveParameter] string $newPassword): bool
        {
            throw new RuntimeException('LDAP on fire');
        }
    });
    fakeLldapProvisioning();

    $logged = '';
    Event::listen(MessageLogged::class, function (MessageLogged $log) use (&$logged): void {
        $logged .= $log->message.($log->context['exception'] ?? '');
    });

    $this->post(route('invites.accept', $this->rawToken), [
        'username' => 'brightblade',
        'name' => 'Sturm Brightblade',
        'password' => 'correct horse battery staple',
    ])->assertServerError();

    expect($logged)
        ->toContain('LDAP on fire')
        ->not->toContain('correct horse');
});

it('pre-fills a valid username suggested by the Invite', function (string $suggestedName, string $expected): void {
    $this->invite->update(['suggested_name' => $suggestedName]);

    $this->get(route('invites.show', $this->rawToken))
        ->assertOk()
        ->assertSee('value="'.$expected.'"', escape: false);
})->with([
    'two words' => ['Sturm Brightblade', 'sturm-brightblade'],
    'accented' => ['Éowyn', 'eowyn'],
    'leading digit' => ['7 Seas', 'seas'],
    'too long' => ['Tasslehoff Burrfoot of the Kender Lands', 'tasslehoff-burrfoot-of-the-kende'],
    'too short' => ['Al', ''],
    'nothing usable' => ['李', ''],
]);

it('keeps the typed username over the suggestion after a failed submit', function (): void {
    $this->from(route('invites.show', $this->rawToken))
        ->post(route('invites.accept', $this->rawToken), [
            'username' => '7seasons',
            'name' => 'Sturm',
            'password' => 'eleven-char',
        ]);

    $this->get(route('invites.show', $this->rawToken))
        ->assertSee('value="7seasons"', escape: false);
});

it('marks the invalid field and links its error for screen readers', function (string $field, array $input): void {
    $this->from(route('invites.show', $this->rawToken))
        ->post(route('invites.accept', $this->rawToken), [
            'username' => 'sturm',
            'name' => 'Sturm',
            'password' => 'correct horse battery staple',
            ...$input,
        ]);

    $this->get(route('invites.show', $this->rawToken))
        ->assertSee("id=\"{$field}-error\"", escape: false)
        ->assertSee("aria-describedby=\"{$field}-error", escape: false)
        ->assertSee('aria-invalid="true"', escape: false);
})->with([
    'username' => ['username', ['username' => '7seasons']],
    'display name' => ['name', ['name' => '']],
    'password' => ['password', ['password' => 'eleven-char']],
]);

it('summarises the errors at the top of the form', function (): void {
    $this->from(route('invites.show', $this->rawToken))
        ->post(route('invites.accept', $this->rawToken), [
            'username' => '7seasons',
            'name' => 'Sturm',
            'password' => 'eleven-char',
        ]);

    $this->get(route('invites.show', $this->rawToken))
        ->assertSeeInOrder(['data-error-summary', 'href="#username"', 'href="#password"', 'id="username"'], escape: false);
});

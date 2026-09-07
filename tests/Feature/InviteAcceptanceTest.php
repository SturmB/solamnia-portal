<?php

use App\Enums\InviteStatus;
use App\Models\Invite;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->rawToken = Str::random(64);
    $this->invite = Invite::factory()->create([
        'email' => 'sturm@example.com',
        'suggested_name' => 'Sturm',
        'token' => hash('sha256', $this->rawToken),
    ]);

    Http::fake();
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

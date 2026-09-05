<?php

use App\Models\Invite;
use Illuminate\Support\Str;

it('renders the acceptance placeholder for a pending token', function () {
    $raw = Str::random(64);
    Invite::factory()->create(['email' => 'sturm@example.com', 'token' => hash('sha256', $raw)]);

    $this->get(route('invites.show', $raw))
        ->assertOk()
        ->assertSee('sturm@example.com')
        ->assertDontSee('no longer valid');
});

it('renders the friendly page with a 200 for a dead token', function (string $state) {
    $raw = Str::random(64);
    Invite::factory()->{$state}()->create(['token' => hash('sha256', $raw)]);

    $this->get(route('invites.show', $raw))
        ->assertOk()
        ->assertSee('no longer valid');
})->with(['expired', 'revoked', 'accepted']);

it('renders the friendly page with a 200 for an unknown well-formed token', function () {
    $this->get(route('invites.show', Str::random(64)))
        ->assertOk()
        ->assertSee('no longer valid');
});

it('404s a malformed token instead of hitting the lookup', function () {
    $this->get('/invites/not-a-token')->assertNotFound();
});

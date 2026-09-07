<?php

use App\Models\Invite;

beforeEach(function () {
    $this->rawToken = Str::random(64);
    Invite::factory()->create([
        'email' => 'sturm@example.com',
        'suggested_name' => 'Sturm',
        'token' => hash('sha256', $this->rawToken),
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

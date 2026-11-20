<?php

use App\Models\User;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('kb.index'));
    $response->assertRedirect(route('login'));
});

test('members can visit the knowledge base', function (): void {
    $this->actingAs(User::factory()->sso()->create())
        ->get(route('kb.index'))
        ->assertOk();
});

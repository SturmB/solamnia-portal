<?php

use App\Models\User;

it('seeds a persona who can sign in with the factory password', function (string $email, bool $isAdmin): void {
    $this->seed();

    $this->post(route('login.store'), [
        'email' => $email,
        'password' => 'password',
    ]);

    $user = User::firstWhere('email', $email);

    $this->assertAuthenticatedAs($user);
    expect($user->is_admin)->toBe($isAdmin);
})->with([
    'admin' => [
        'email' => 'test@example.com',
        'isAdmin' => true,
    ],
    'member' => [
        'email' => 'member@example.com',
        'isAdmin' => false,
    ],
]);

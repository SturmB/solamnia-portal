<?php

use App\Models\User;
use App\Notifications\MediaServerInviteComing;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('an sso member passes the verified middleware', function (): void {
    $member = User::factory()->sso()->create();

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertOk();
});

it('shows the media-server notice, naming the inviter and the Admin when configured', function (?string $inviter, ?string $admin, array $expected): void {
    config([
        'services.plex.inviter_email' => $inviter,
        'app.admin_contact_email' => $admin,
    ]);

    $member = User::factory()->sso()->create();
    $member->notify(new MediaServerInviteComing);

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($expected);
})->with([
    'with an inviter and an Admin address' => [
        'owner@example.com',
        'admin@example.com',
        [
            'from Plex, from owner@example.com',
            'contact the Admin at admin@example.com',
        ],
    ],
    'with neither' => [
        null,
        null,
        [
            'coming from Plex. Accept',
            'contact the Admin.',
        ],
    ],
]);

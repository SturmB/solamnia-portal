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
            'One more email is on its way',
            'from Plex, from owner@example.com',
            'contact the Admin at admin@example.com',
        ],
    ],
    'with neither' => [
        null,
        null,
        [
            'One more email is on its way',
            'coming from Plex. Accept',
            'contact the Admin.',
        ],
    ],
]);

it('hides the notice once the Member dismisses it', function (): void {
    $member = User::factory()->sso()->create();
    $member->notify(new MediaServerInviteComing);

    $notification = $member->notifications()->sole();

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertSee(route('notifications.dismiss', $notification), escape: false);

    $this->post(route('notifications.dismiss', $notification))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($member->fresh())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('One more email is on its way');
});

it("will not let a Member dismiss someone else's notice", function (): void {
    $owner = User::factory()->sso()->create();
    $owner->notify(new MediaServerInviteComing);

    $notification = $owner->notifications()->sole();

    $this->actingAs(User::factory()->sso()->create())
        ->post(route('notifications.dismiss', $notification))
        ->assertNotFound();

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertSee('One more email is on its way');
});

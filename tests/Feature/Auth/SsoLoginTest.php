<?php

use App\Models\User;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as OidcUser;

/**
 * A claim set shaped exactly as the Authelia provider maps it: `id` carries
 * the subject claim, and `groups` is always present — null when the scope
 * was not granted, never absent.
 *
 * @param  array<string, mixed>  $overrides
 */
function autheliaUser(array $overrides = []): OidcUser
{
    return OidcUser::fake([
        'id' => 'authelia-sub-1',
        'email' => 'member@example.com',
        'name' => 'Test Member',
        'preferred_username' => 'testmember',
        'groups' => ['members'],
        ...$overrides,
    ]);
}

test('an existing account is bound by email on first sso login', function () {
    $admin = User::factory()->create([
        'email' => 'admin@example.com',
        'is_admin' => true,
    ]);

    Socialite::fake('authelia', autheliaUser([
        'email' => 'admin@example.com',
    ]));

    $response = $this->get(route('auth.callback'));

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($admin);

    expect(User::count())->toBe(1)
        ->and($admin->refresh()->oidc_sub)->toBe('authelia-sub-1')
        ->and($admin->is_admin)->toBeTrue();
});

test('a login without the members group is refused and creates nothing', function (?array $groups) {
    Socialite::fake('authelia', autheliaUser([
        'groups' => $groups,
    ]));

    $response = $this->get(route('auth.callback'));

    $response->assertForbidden();
    $this->assertGuest();
    expect(User::count())->toBe(0);
})->with([
    'not in the group' => [['lldap_admin']],
    'groups scope missing' => [null],
]);

test('an unknown person in the members group is created just in time', function () {
    Socialite::fake('authelia', autheliaUser());

    $response = $this->get(route('auth.callback'));

    $response->assertRedirect(route('dashboard', absolute: false));

    $member = User::sole();
    $this->assertAuthenticatedAs($member);

    expect($member->oidc_sub)->toBe('authelia-sub-1')
        ->and($member->is_admin)->toBeFalse()
        ->and($member->email_verified_at)->not->toBeNull()
        ->and($member->password)->toBeNull();
});

test('a changed email updates the bound account rather than creating another', function () {
    $member = User::factory()->sso()->create([
        'oidc_sub' => 'authelia-sub-1',
        'email' => 'old@example.com',
    ]);

    Socialite::fake('authelia', autheliaUser([
        'email' => 'new@example.com',
    ]));

    $this->get(route('auth.callback'));

    expect(User::count())->toBe(1)
        ->and($member->refresh()->email)->toBe('new@example.com');
});

test('a member lands where they were going after signing in', function () {
    User::factory()->sso()->create(['oidc_sub' => 'authelia-sub-1']);

    $this->get(route('profile.edit'))->assertRedirect(route('login'));

    Socialite::fake('authelia', autheliaUser());

    $this->get(route('auth.callback'))
        ->assertRedirect(route('profile.edit', absolute: false));
});

test('an email belonging to another bound member is refused, not duplicated', function () {
    User::factory()->sso()->create([
        'oidc_sub' => 'someone-else',
        'email' => 'member@example.com',
    ]);

    Socialite::fake('authelia', autheliaUser());

    $this->get(route('auth.callback'))->assertForbidden();

    $this->assertGuest();
    expect(User::count())->toBe(1);
});

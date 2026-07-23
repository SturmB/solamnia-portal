<?php

use App\Models\User;
use Filament\Facades\Filament;

test('an admin can access the admin panel', function (): void {
    $user = User::factory()->create([
        'is_admin' => true,
    ]);
    $panel = Filament::getPanel('admin');

    expect($user->canAccessPanel($panel))->toBeTrue();
});

test('a non-admin cannot access the admin panel', function (): void {
    $user = User::factory()->create([
        'is_admin' => false,
    ]);
    $panel = Filament::getPanel('admin');

    expect($user->canAccessPanel($panel))->toBeFalse();
});

<?php

use App\Enums\InviteStatus;
use App\Models\Invite;

it('derives status from the timestamps', function (string $state, InviteStatus $expected): void {
    $invite = $state === 'pending'
        ? Invite::factory()->create()
        : Invite::factory()->{$state}()->create();

    expect($invite->status())->toBe($expected);
})->with([
    ['pending', InviteStatus::Pending],
    ['expired', InviteStatus::Expired],
    ['revoked', InviteStatus::Revoked],
    ['accepted', InviteStatus::Accepted],
]);

it('accepts once and refuses a second redemption', function (): void {
    $invite = Invite::factory()->create();

    expect($invite->accept('brightblade'))->toBeTrue();
    expect($invite->accept('brightblade'))->toBeFalse()
        ->and($invite->fresh()->username)->toBe('brightblade');
});

it('revokes a pending Invite', function (): void {
    $invite = Invite::factory()->create();

    $result = $invite->revoke();

    expect($result)->toBeTrue()
        ->and($invite->status())->toBe(InviteStatus::Revoked);
});

it('refuses to revoke an Invite that is no longer pending', function (string $state, InviteStatus $expected): void {
    $invite = Invite::factory()->{$state}()->create();

    $result = $invite->revoke();

    expect($result)->toBeFalse()
        ->and($invite->status())->toBe($expected);
})->with([
    ['accepted', InviteStatus::Accepted],
    ['expired', InviteStatus::Expired],
    ['revoked', InviteStatus::Revoked],
]);

it('refuses to revoke an Invite whose row was deleted underneath it', function (): void {
    $invite = Invite::factory()->create();

    Invite::query()->whereKey($invite)->delete();

    expect($invite->revoke())->toBeFalse();
});

it('reloads a stale Invite when refusing to revoke it', function (): void {
    $invite = Invite::factory()->create();

    Invite::query()->whereKey($invite)->update(['accepted_at' => now()]);

    expect($invite->revoke())->toBeFalse()
        ->and($invite->status())->toBe(InviteStatus::Accepted);
});

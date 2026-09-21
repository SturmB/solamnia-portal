<?php

use App\Enums\InviteStatus;
use App\Models\Invite;
use App\Models\User;

it('derives status from the timestamps', function (string $state, InviteStatus $expected) {
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

it('accepts once and refuses a second redemption', function () {
    $invite = Invite::factory()->create();

    expect($invite->accept('brightblade'))->toBeTrue();
    expect($invite->accept('brightblade'))->toBeFalse()
        ->and($invite->fresh()->username)->toBe('brightblade');
});

it('keeps the raw token recoverable after issuance', function () {
    $invite = Invite::issue('sturm@example.com', 'Sturm', User::factory()->create());

    $refreshedInvite = $invite->fresh();
    expect($refreshedInvite->plain_token)->not->toBeNull()
        ->and(hash('sha256', $refreshedInvite->plain_token))->toBe($invite->token);
});

it('revokes a pending Invite', function () {
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

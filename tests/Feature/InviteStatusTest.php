<?php

use App\Enums\InviteStatus;
use App\Models\Invite;

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

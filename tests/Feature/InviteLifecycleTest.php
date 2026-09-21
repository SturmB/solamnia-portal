<?php

use App\Filament\Resources\Invites\Pages\ListInvites;
use App\Mail\InviteMail;
use App\Models\Invite;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Mail::fake();
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('re-sends the same link without rotating the token or extending expiry', function (): void {
    $invite = Invite::factory()->create();

    livewire(ListInvites::class)
        ->callAction(TestAction::make('resend')->table($invite))
        ->assertNotified();

    Mail::assertSent(InviteMail::class, fn (InviteMail $mail): bool => $mail->hasTo($invite->email)
            && $mail->token === $invite->plain_token);

    $refreshedInvite = $invite->fresh();
    expect($refreshedInvite->token)->toBe($invite->token)
        ->and($refreshedInvite->expires_at)->toEqual($invite->expires_at);
});

it('copies the link without rotating the token or extending expiry', function (): void {
    $invite = Invite::factory()->create();

    livewire(ListInvites::class)
        ->mountAction(TestAction::make('copyLink')->table($invite))
        ->assertMountedActionModalSee(route('invites.show', $invite->plain_token));
});

it('revokes from the panel and the link stops working', function (): void {
    $invite = Invite::factory()->create();

    livewire(ListInvites::class)
        ->callAction(TestAction::make('revoke')->table($invite))
        ->assertNotified();

    $this->get(route('invites.show', $invite->plain_token))
        ->assertOk()
        ->assertSee('no longer valid');
});

it('hides every action once the Invite is no longer pending', function (string $state) {
    $invite = Invite::factory()->{$state}()->create();

    livewire(ListInvites::class)
        ->assertActionHidden(TestAction::make('resend')->table($invite))
        ->assertActionHidden(TestAction::make('copyLink')->table($invite))
        ->assertActionHidden(TestAction::make('revoke')->table($invite));
})->with(['expired', 'revoked', 'accepted']);

it('offers only revoke on a legacy Invite with no raw token', function () {
    $invite = Invite::factory()->create(['plain_token' => null]);

    livewire(ListInvites::class)
        ->assertActionHidden(TestAction::make('resend')->table($invite))
        ->assertActionHidden(TestAction::make('copyLink')->table($invite))
        ->assertActionVisible(TestAction::make('revoke')->table($invite));
});

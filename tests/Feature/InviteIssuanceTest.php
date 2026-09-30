<?php

use App\Enums\InviteStatus;
use App\Filament\Resources\Invites\Pages\CreateInvite;
use App\Filament\Resources\Invites\Pages\ListInvites;
use App\Mail\InviteMail;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Mail::fake();
    // An apostrophe on purpose: the inviter's name is HTML-escaped in the email body.
    $this->admin = User::factory()->admin()->create(['name' => "Chris O'Conner"]);
    $this->actingAs($this->admin);
});

it('issues a pending Invite from the panel and emails the link', function (): void {
    livewire(CreateInvite::class)
        ->fillForm([
            'email' => 'sturm@example.com',
            'suggested_name' => 'Sturm',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $invite = Invite::sole();

    expect($invite)
        ->email->toBe('sturm@example.com')
        ->suggested_name->toBe('Sturm')
        ->invited_by->toBe($this->admin->id)
        ->status()->toBe(InviteStatus::Pending)
        ->and($invite->expires_at->diffInDays(now()->addDays(14)))->toBeLessThan(1);

    Mail::assertSent(InviteMail::class, function (InviteMail $mail) use ($invite): bool {
        $html = $mail->render();

        return $mail->hasTo('sturm@example.com')
            && $invite->token === hash('sha256', $mail->invite->plain_token)
            && str_contains($html, route('invites.show', $invite->plain_token))
            && str_contains($html, e($this->admin->name))
            && str_contains($html, $invite->expires_at->format('F j, Y'));
    });

    livewire(ListInvites::class)
        ->assertCanSeeTableRecords([$invite])
        ->assertSee('Pending');
});

it('stores and mails the email lowercased however the Admin typed it', function (): void {
    livewire(CreateInvite::class)
        ->fillForm(['email' => 'Sturm@Example.com', 'suggested_name' => 'Sturm'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Invite::sole()->email)->toBe('sturm@example.com');

    Mail::assertSent(InviteMail::class, fn (InviteMail $mail): bool => $mail->hasTo('sturm@example.com'));
});

it('refuses an email that belongs to an existing Member', function (string $typed): void {
    User::factory()->create(['email' => 'sturm@example.com']);

    livewire(CreateInvite::class)
        ->fillForm(['email' => $typed, 'suggested_name' => 'Dup'])
        ->call('create')
        ->assertHasFormErrors(['email' => 'This email already belongs to a Member.']);

    expect(Invite::count())->toBe(0);
    Mail::assertNothingSent();
})->with(['sturm@example.com', 'Sturm@Example.com']);

it('refuses an email that already has a pending Invite', function (string $typed): void {
    Invite::factory()->create(['email' => 'sturm@example.com']);

    livewire(CreateInvite::class)
        ->fillForm(['email' => $typed, 'suggested_name' => 'Sturm'])
        ->call('create')
        ->assertHasFormErrors(['email' => 'A pending Invite already exists for this email.']);

    expect(Invite::count())->toBe(1);
})->with(['sturm@example.com', 'Sturm@Example.com']);

it('allows re-issuing once the earlier Invite is no longer pending', function (string $state): void {
    Invite::factory()->{$state}()->create(['email' => 'sturm@example.com']);

    livewire(CreateInvite::class)
        ->fillForm(['email' => 'sturm@example.com', 'suggested_name' => 'Sturm'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Invite::count())->toBe(2);
})->with(['expired', 'revoked', 'accepted']);

it('keeps the raw token recoverable after issuance', function (): void {
    $invite = Invite::issue('sturm@example.com', 'Sturm', User::factory()->create());

    $refreshedInvite = $invite->fresh();
    expect($refreshedInvite->plain_token)->not->toBeNull()
        ->and(hash('sha256', $refreshedInvite->plain_token))->toBe($invite->token);
});

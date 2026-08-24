<?php

use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Filament\Resources\Campaigns\Pages\ViewCampaign;
use App\Models\Campaign;
use App\Models\User;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Filament\Notifications\Notification;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('denies deleting a sent campaign', function () {
    $sent = Campaign::factory()->sent()->create();

    expect(auth()->user()->can('delete', $sent))->toBeFalse();
});

it('still offers delete on the edit page for draft and scheduled campaigns', function () {
    $draft = Campaign::factory()->create();
    $scheduled = Campaign::factory()->create(['scheduled_at' => now()->addDay()]);

    livewire(EditCampaign::class, ['record' => $draft->id])
        ->assertActionVisible('delete');

    livewire(EditCampaign::class, ['record' => $scheduled->id])
        ->assertActionVisible('delete');
});

it('bulk delete removes only non-sent campaigns and notifies about the skipped ones', function () {
    $draft = Campaign::factory()->create();
    $sent = Campaign::factory()->sent()->create();

    livewire(ListCampaigns::class)
        ->selectTableRecords([$draft->id, $sent->id])
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
        // The partial-failure notification: title is Filament's, body is CampaignPolicy's DenyResponse message.
        ->assertNotified(
            Notification::make()
                ->warning()
                ->persistent()
                ->title('Deleted 1 of 2')
                ->body("<p>1 / 2 were not deleted — they've already been sent.</p>"),
        );

    expect(Campaign::query()->pluck('id')->all())->toBe([$sent->id]);
});

it('keeps a sent campaign viewable in the panel', function () {
    $sent = Campaign::factory()->sent()->create();

    livewire(ViewCampaign::class, ['record' => $sent->id])
        ->assertOk()
        ->assertSchemaStateSet([
            'subject' => $sent->subject,
            'body_markdown' => $sent->body_markdown,
        ]);
});

it('denies updating sent campaigns', function () {
    $sent = Campaign::factory()->sent()->create();

    livewire(EditCampaign::class, ['record' => $sent->id])
        ->assertForbidden();
});

it('allows updating a draft campaign', function () {
    $draft = Campaign::factory()->create();

    livewire(EditCampaign::class, ['record' => $draft->id])
        ->fillForm(['subject' => 'Updated subject'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($draft->refresh()->subject)->toBe('Updated subject');
});

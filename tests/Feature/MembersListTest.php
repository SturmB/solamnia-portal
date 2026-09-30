<?php

use App\Filament\Resources\Members\Pages\ListMembers;
use App\Jobs\ShareMediaLibraries;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Queue;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('lists every Member with name and email', function (): void {
    $members = User::factory()->count(2)->create();

    livewire(ListMembers::class)
        ->assertCanSeeTableRecords($members)
        ->assertCanRenderTableColumn('name')
        ->assertCanRenderTableColumn('email');
});

it('dispatches the media-server job for the chosen Member', function (): void {
    Queue::fake([ShareMediaLibraries::class]);
    $member = User::factory()->create();

    livewire(ListMembers::class)
        ->callAction(TestAction::make('retryMediaServer')->table($member))
        ->assertNotified();

    Queue::assertPushed(ShareMediaLibraries::class, fn (ShareMediaLibraries $job): bool => $job->email === $member->email);
});

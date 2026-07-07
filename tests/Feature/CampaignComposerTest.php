<?php

use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\User;
use Filament\Forms\Components\MarkdownEditor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('creates a Campaign from Markdown through the panel', function () {
    livewire(CreateCampaign::class)
        ->fillForm([
            'subject' => 'Spring Update',
            'body_markdown' => '# Hello friends',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Campaign::sole())
        ->subject->toBe('Spring Update')
        ->body_markdown->toBe('# Hello friends');
});

it('shows a preview that reflects the submitted Markdown', function () {
    livewire(CreateCampaign::class)
        ->fillForm([
            'subject' => 'Spring Update',
            'body_markdown' => '# Distinctive Preview Heading',
        ])
        ->assertSee('Distinctive Preview Heading');
});

it('test-send delivers exactly one email, to the Admin', function () {
    Mail::fake();

    $admin = auth()->user();
    $campaign = Campaign::factory()->create();

    livewire(EditCampaign::class, ['record' => $campaign->id])
        ->callAction('testSend');

    Mail::assertSentCount(1);
    Mail::assertSent(CampaignMail::class, fn (CampaignMail $mail): bool => $mail->hasTo($admin->email)
        && $mail->campaign->is($campaign));
});

it('stores an uploaded body image on the public disk with public visibility', function () {
    Storage::fake('public');

    // Livewire mints a genuine temporary upload the way the browser would,
    // landing it at the editor's file-attachment state path.
    $temporary = livewire(CreateCampaign::class)
        ->fillForm([
            'subject' => 'Spring Update',
            'body_markdown' => 'Body copy.',
        ])
        ->upload('componentFileAttachments.data.body_markdown', [UploadedFile::fake()->image('inline.png')])
        ->get('componentFileAttachments.data.body_markdown');

    // Filament then persists it via the editor configured as the form configures it.
    $path = MarkdownEditor::make('body_markdown')
        ->fileAttachmentsDisk('public')
        ->fileAttachmentsDirectory('campaigns')
        ->saveUploadedFileAttachment($temporary);

    Storage::disk('public')->assertExists($path);
    expect(Storage::disk('public')->getVisibility($path))->toBe('public');
});

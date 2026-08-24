<?php

use App\Enums\CampaignStatus;
use App\Mail\CampaignMail;
use App\Mcp\Servers\CampaignServer;
use App\Mcp\Tools\CreateCampaignTool;
use App\Mcp\Tools\DeleteCampaignTool;
use App\Mcp\Tools\GetCampaignTool;
use App\Mcp\Tools\IngestImageTool;
use App\Mcp\Tools\ListCampaignsTool;
use App\Mcp\Tools\TestSendTool;
use App\Mcp\Tools\UpdateCampaignTool;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

it('creates a draft Campaign from subject and Markdown', function () {
    $response = CampaignServer::tool(CreateCampaignTool::class, [
        'subject' => 'Spring Update',
        'body_markdown' => '# Hello friends',
    ]);

    $response->assertOk()->assertSee('"status":"draft"');

    expect(Campaign::sole())
        ->subject->toBe('Spring Update')
        ->body_markdown->toBe('# Hello friends')
        ->status()->toBe(CampaignStatus::Draft);
});

it('renders an MCP-authored Campaign through the branded MJML pipeline', function () {
    CampaignServer::tool(CreateCampaignTool::class, [
        'subject' => 'Spring Update',
        'body_markdown' => '# Distinctive Render Heading',
    ])->assertOk();

    expect(Campaign::sole()->renderHtml())->toContain('Distinctive Render Heading');
});

it('rejects a Campaign without a subject', function () {
    $response = CampaignServer::tool(CreateCampaignTool::class, [
        'body_markdown' => '# Hello',
    ]);

    $response->assertHasErrors();

    expect(Campaign::count())->toBe(0);
});

it('rejects a subject longer than the panel form allows', function () {
    $response = CampaignServer::tool(CreateCampaignTool::class, [
        'subject' => str_repeat('a', 256),
        'body_markdown' => '# Hello',
    ]);

    $response->assertHasErrors();

    expect(Campaign::count())->toBe(0);
});

it('ignores a scheduled_at argument — scheduling is human-only', function () {
    CampaignServer::tool(CreateCampaignTool::class, [
        'subject' => 'Spring Update',
        'body_markdown' => '# Hello',
        'scheduled_at' => now()->addDay()->toIso8601String(),
    ])->assertOk();

    expect(Campaign::sole()->scheduled_at)->toBeNull();
});

it('updates a draft Campaign', function () {
    $draft = Campaign::factory()->create();

    CampaignServer::tool(UpdateCampaignTool::class, [
        'id' => $draft->id,
        'subject' => 'Revised subject',
    ])->assertOk();

    expect($draft->refresh())
        ->subject->toBe('Revised subject');
});

it('refuses to update a sent Campaign', function () {
    $sent = Campaign::factory()->sent()->create();
    $original = $sent->subject;

    CampaignServer::tool(UpdateCampaignTool::class, [
        'id' => $sent->id,
        'subject' => 'Sneaky edit',
    ])->assertHasErrors(["Campaign {$sent->id} has already been sent and is immutable. Create a new Campaign instead."]);

    expect($sent->refresh()->subject)->toBe($original);
});

it('deletes a draft Campaign', function () {
    $draft = Campaign::factory()->create();

    CampaignServer::tool(DeleteCampaignTool::class, ['id' => $draft->id])->assertOk();

    expect(Campaign::count())->toBe(0);
});

it('refuses to delete a sent Campaign', function () {
    $sent = Campaign::factory()->sent()->create();

    CampaignServer::tool(DeleteCampaignTool::class, ['id' => $sent->id])
        ->assertHasErrors(["Campaign {$sent->id} has already been sent. Send history is never deleted."]);

    expect(Campaign::count())->toBe(1);
});

it('errors clearly when a Campaign does not exist', function () {
    CampaignServer::tool(GetCampaignTool::class, ['id' => 999])
        ->assertHasErrors(['No Campaign with id 999.']);
});

it('returns a Campaign with its derived status', function () {
    $scheduled = Campaign::factory()->create(['scheduled_at' => now()->addDay()]);

    CampaignServer::tool(GetCampaignTool::class, ['id' => $scheduled->id])
        ->assertOk()
        ->assertSee('"status":"scheduled"')
        ->assertSee($scheduled->subject);
});

it('lists Campaigns with id, subject, and status', function () {
    $draft = Campaign::factory()->create();
    $sent = Campaign::factory()->sent()->create();

    CampaignServer::tool(ListCampaignsTool::class, [])
        ->assertOk()
        ->assertSee($draft->subject)
        ->assertSee($sent->subject)
        ->assertSee('"status":"sent"');
});

it('ingests an image onto the public disk under campaigns/', function () {
    Storage::fake('public');

    // Keep the UploadedFile in scope — its temp file is deleted on GC.
    $upload = UploadedFile::fake()->image('poster.png', 640, 480);

    $response = CampaignServer::tool(IngestImageTool::class, ['source_path' => $upload->getPathname()]);

    $response->assertOk()->assertSee('/storage/campaigns/');

    $stored = Storage::disk('public')->files('campaigns');
    expect($stored)->toHaveCount(1)
        ->and(Storage::disk('public')->getVisibility($stored[0]))->toBe('public');
});

it('refuses to ingest a file that is not an image', function () {
    Storage::fake('public');

    $source = tempnam(sys_get_temp_dir(), 'mcp');
    file_put_contents($source, 'just some text');

    CampaignServer::tool(IngestImageTool::class, ['source_path' => $source])
        ->assertHasErrors();

    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

it('test-sends a Campaign to the admin Member through the existing mailable', function () {
    Mail::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->create(); // a non-admin who must not receive it
    $campaign = Campaign::factory()->create();

    CampaignServer::tool(TestSendTool::class, ['id' => $campaign->id])
        ->assertOk()
        ->assertSee($admin->email);

    Mail::assertSentCount(1);
    Mail::assertSent(CampaignMail::class, fn (CampaignMail $mail): bool => $mail->hasTo($admin->email)
        && $mail->campaign->is($campaign));
});

it('errors clearly when no admin exists to test-send to', function () {
    Mail::fake();

    $campaign = Campaign::factory()->create();

    CampaignServer::tool(TestSendTool::class, ['id' => $campaign->id])
        ->assertHasErrors(['No admin user found to receive the test send.']);

    Mail::assertNothingSent();
});

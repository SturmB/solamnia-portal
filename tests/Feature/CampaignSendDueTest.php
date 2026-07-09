<?php

use App\Enums\CampaignStatus;
use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    // Give Pushover credentials so notify() actually fires, and fake the HTTP layer
    // so we can assert the POST without hitting the network.
    config(['services.pushover.token' => 'test-token', 'services.pushover.user' => 'test-user']);
    Http::fake();
});

it('sends a due campaign to every opted-in subscriber and skips opted-out', function () {
    Mail::fake();

    $optedInSubscribers = Subscriber::factory()->count(3)->create(['unsubscribed_at' => null]);
    $optedOutSubscribers = Subscriber::factory()->count(2)->create(['unsubscribed_at' => now()]);

    $campaign = Campaign::factory()->create([
        'scheduled_at' => now()->subMinute(),
        'sent_at' => null,
    ]);

    $this->artisan('campaigns:send-due')->assertSuccessful();

    Mail::assertSentCount(3);

    $optedInSubscribers->each(function (Subscriber $subscriber) {
        Mail::assertSent(CampaignMail::class, fn (CampaignMail $mail): bool => $mail->hasTo($subscriber->email));
    });
    $optedOutSubscribers->each(function (Subscriber $subscriber) {
        Mail::assertNotSent(CampaignMail::class, fn (CampaignMail $mail): bool => $mail->hasTo($subscriber->email));
    });

    $campaign->refresh();
    expect($campaign->recipient_count)->toBe(3)
        ->and($campaign->sent_at)->not->toBeNull()
        ->and($campaign->status())->toBe(CampaignStatus::Sent);

    Http::assertSent(function ($request) {
        $data = $request->data();

        return $data['priority'] === 0 && str_contains($data['title'], 'Campaign sent');
    });
});

it('leaves a not-yet-due scheduled campaign alone', function () {
    Mail::fake();

    $campaign = Campaign::factory()->create(['scheduled_at' => now()->addHour()]);

    $this->artisan('campaigns:send-due')->assertSuccessful();

    Mail::assertNothingSent();
    expect($campaign->refresh()->sent_at)->toBeNull();
});

it('does not send twice when the sweep runs again (idempotency)', function () {
    Mail::fake();

    Campaign::factory()->create(['scheduled_at' => now()->subMinute()]);
    Subscriber::factory()->count(2)->create(['unsubscribed_at' => null]);

    $this->artisan('campaigns:send-due')->assertSuccessful();
    $this->artisan('campaigns:send-due')->assertSuccessful();  // second sweep must add nothing

    Mail::assertSentCount(2);
});

it('fires a failure notification and keeps going when a send throws', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp down'));

    Campaign::factory()->create(['scheduled_at' => now()->subMinute()]);
    Subscriber::factory()->create(['unsubscribed_at' => null]);

    $this->artisan('campaigns:send-due')->assertSuccessful();

    Http::assertSent(fn ($request) => str_contains($request->data()['title'], 'FAILED'));
    Http::assertNotSent(fn ($request) => str_contains($request->data()['title'], 'Campaign sent'));
});

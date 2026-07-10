<?php

use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\Subscriber;
use Illuminate\Support\Facades\URL;

it('embeds per-subscriber signed unsubscribe and view-in-browser links in the rendered email', function () {
    $subscriber = Subscriber::factory()->create();
    $campaign = Campaign::factory()->create();

    $html = $campaign->renderHtml($subscriber);

    expect($html)
        ->toContain(URL::signedRoute('unsubscribe', ['subscriber' => $subscriber]))
        ->toContain(URL::signedRoute('campaigns.view', ['campaign' => $campaign, 'subscriber' => $subscriber]));
});

it('omits the footer links when rendered without a subscriber (test send to self)', function () {
    $campaign = Campaign::factory()->create();

    expect($campaign->renderHtml())->not->toContain('/unsubscribe/');
});

it('sets List-Unsubscribe headers pointing at the signed unsubscribe URL', function () {
    $subscriber = Subscriber::factory()->create();
    $campaign = Campaign::factory()->create();

    $headers = (new CampaignMail($campaign, $subscriber))->headers();

    expect($headers->text)
        ->toHaveKey('List-Unsubscribe', '<'.URL::signedRoute('unsubscribe', ['subscriber' => $subscriber]).'>')
        ->toHaveKey('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
});

it('omits List-Unsubscribe headers for a subscriber-less test send', function () {
    $campaign = Campaign::factory()->create();

    expect((new CampaignMail($campaign))->headers()->text)->toBeEmpty();
});

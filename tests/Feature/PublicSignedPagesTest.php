<?php

use App\Models\Campaign;
use App\Models\Subscriber;
use Illuminate\Support\Facades\URL;

it('opts out a subscriber via a valid signed unsubscribe link, no login', function () {
    $subscriber = Subscriber::factory()->create(['unsubscribed_at' => null]);

    $url = URL::signedRoute('unsubscribe', ['subscriber' => $subscriber]);

    $this->get($url)->assertOk()->assertSee("You've been unsubscribed.", false);

    expect($subscriber->refresh()->unsubscribed_at)->not->toBeNull();
});

it('opts out via a one-click POST to the same signed URL (RFC 8058 List-Unsubscribe)', function () {
    $subscriber = Subscriber::factory()->create(['unsubscribed_at' => null]);

    $url = URL::signedRoute('unsubscribe', ['subscriber' => $subscriber]);

    $this->post($url)->assertNoContent();

    expect($subscriber->refresh()->unsubscribed_at)->not->toBeNull();
});

it('renders campaign HTML in the browser via a valid signed link, unauthenticated', function () {
    $subscriber = Subscriber::factory()->create();
    $campaign = Campaign::factory()->create(['subject' => 'Spring Update']);

    $url = URL::signedRoute('campaigns.view', ['campaign' => $campaign, 'subscriber' => $subscriber]);

    $this->get($url)
        ->assertOk()
        ->assertSee('Spring Update', false);
});

it('rejects re-targeting the unsubscribe link at another subscriber with 403', function () {
    $subscriber = Subscriber::factory()->create();
    $attacker = Subscriber::factory()->create();

    $url = URL::signedRoute('unsubscribe', ['subscriber' => $subscriber]);
    $tampered = str_replace("/unsubscribe/{$subscriber->id}", "/unsubscribe/{$attacker->id}", $url);

    $this->get($tampered)->assertForbidden();

    expect($attacker->refresh()->unsubscribed_at)->toBeNull();
});

it('rejects a tampered signature with 403 on the view-in-browser route', function () {
    $subscriber = Subscriber::factory()->create();
    $campaign = Campaign::factory()->create();

    $url = URL::signedRoute('campaigns.view', ['campaign' => $campaign, 'subscriber' => $subscriber]);

    $this->get($url.'x')->assertForbidden();
});

<?php

use App\Models\Subscriber;
use Illuminate\Support\Facades\URL;

/**
 * In production the app sits behind a Cloudflare Tunnel that terminates TLS and
 * forwards to the container over plain http, signalling the real scheme with
 * X-Forwarded-Proto: https. Signed links are minted over https (from APP_URL);
 * unless the app trusts that header, it reconstructs the URL as http, the
 * signature mismatches, and every unsubscribe / view-in-browser link 403s.
 */
it('validates an https-signed link when it arrives over http with X-Forwarded-Proto: https', function () {
    URL::forceRootUrl('https://solamnia.tv');
    URL::forceScheme('https');

    $subscriber = Subscriber::factory()->create(['unsubscribed_at' => null]);

    $signed = URL::signedRoute('unsubscribe', ['subscriber' => $subscriber]);
    expect($signed)->toStartWith('https://');

    // Simulate the tunnel: same URL, but the connection to the origin is http
    // and the public scheme is announced via the forwarded header.
    $overHttp = str_replace('https://', 'http://', $signed);

    $this->get($overHttp, ['X-Forwarded-Proto' => 'https'])
        ->assertOk()
        ->assertSee("You've been unsubscribed.", false);

    expect($subscriber->refresh()->unsubscribed_at)->not->toBeNull();
});

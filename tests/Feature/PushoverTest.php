<?php

use App\Services\Pushover;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake();
});

it('is a no-op when Pushover credentials are absent', function () {
    config(['services.pushover.token' => null, 'services.pushover.user' => null]);

    app(Pushover::class)->send('Title', 'Message');

    Http::assertNothingSent();
});

it('posts the title, message, and priority when credentials are present', function () {
    config(['services.pushover.token' => 'test-token', 'services.pushover.user' => 'test-user']);

    app(Pushover::class)->send('Campaign sent', 'Sent to 3 subscribers.', priority: 1);

    Http::assertSent(function ($request) {
        $data = $request->data();

        return $request->url() === 'https://api.pushover.net/1/messages.json'
            && $data['token'] === 'test-token'
            && $data['user'] === 'test-user'
            && $data['title'] === 'Campaign sent'
            && $data['message'] === 'Sent to 3 subscribers.'
            && $data['priority'] === 1;
    });
});

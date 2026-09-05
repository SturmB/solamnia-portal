<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class Pushover
{
    public function send(string $title, string $message, int $priority = 0): void
    {
        $token = config('services.pushover.token');
        $user = config('services.pushover.user');

        if (! $token || ! $user) {
            return;
        }

        Http::post('https://api.pushover.net/1/messages.json', [
            'token' => $token,
            'user' => $user,
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Contracts\MediaServer;
use App\Exceptions\MediaServerConfigurationException;
use App\Services\Pushover;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Dispatched after an Invite is accepted, never in its request: a media-server
 * outage must not stop someone becoming a Member. Retries are the queue's, so
 * the body knows nothing about them; the last failure reaches the Admin by Pushover.
 */
class ShareMediaLibraries implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> seconds before the second and third attempts */
    public array $backoff = [60, 300];

    public function __construct(public readonly string $email) {}

    public function handle(MediaServer $mediaServer): void
    {
        if (! $mediaServer->isConfigured()) {
            return;
        }

        try {
            $mediaServer->share($this->email);
        } catch (MediaServerConfigurationException $e) {
            // Config will not heal between attempts; alert the Admin now rather than after the backoff.
            $this->fail($e);
        }
    }

    public function failed(Throwable $e): void
    {
        app(Pushover::class)->send(
            'Media server invite failed',
            "Could not share the libraries with {$this->email}. Invite them by hand. The error was: {$e->getMessage()}",
            priority: 1,
        );
    }
}

<?php

use App\Contracts\MediaServer;
use App\Exceptions\MediaServerConfigurationException;
use App\Exceptions\MediaServerException;
use App\Jobs\ShareMediaLibraries;
use App\Services\Plex;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::fake(['api.pushover.net/*' => Http::response()]);
    Http::preventStrayRequests();

    config(['services.pushover.token' => 'pushover-token', 'services.pushover.user' => 'pushover-user']);
});

it('resolves Plex as the media server', function (): void {
    expect(app(MediaServer::class))->toBeInstanceOf(Plex::class);
});

it('shares the libraries with the email through the seam', function (): void {
    $this->mock(MediaServer::class, function ($mock): void {
        $mock->shouldReceive('isConfigured')->andReturnTrue();
        $mock->shouldReceive('share')->once()->with('sturm@example.com');
    });

    ShareMediaLibraries::dispatchSync('sturm@example.com');
});

it('is a no-op when the media server is unconfigured', function (): void {
    $this->mock(MediaServer::class, function ($mock): void {
        $mock->shouldReceive('isConfigured')->andReturnFalse();
        $mock->shouldNotReceive('share');
    });

    ShareMediaLibraries::dispatchSync('sturm@example.com');
});

it('gives up at once on a configuration failure, without retrying', function (): void {
    $this->mock(MediaServer::class, function ($mock): void {
        $mock->shouldReceive('isConfigured')->andReturnTrue();
        $mock->shouldReceive('share')->once()->andThrow(new MediaServerConfigurationException('No library ids'));
    });

    rescue(fn () => ShareMediaLibraries::dispatchSync('sturm@example.com'), report: false);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.pushover.net/1/messages.json'
        && str_contains($request['message'], 'No library ids'));
});

it('retries a small fixed number of times with backoff', function (): void {
    $job = new ShareMediaLibraries('sturm@example.com');

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([60, 300]);
});

it('fires Pushover once retries are exhausted', function (): void {
    new ShareMediaLibraries('sturm@example.com')
        ->failed(new MediaServerException('plex.tv responded 500: boom'));

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.pushover.net/1/messages.json'
        && $request['priority'] === 1
        && str_contains($request['message'], 'sturm@example.com')
        && str_contains($request['message'], 'plex.tv responded 500: boom'));
});

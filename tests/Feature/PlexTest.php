<?php

use App\Exceptions\MediaServerException;
use App\Services\Plex;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();

    config(['services.plex' => [
        'token' => 'plex-token',
        'client_identifier' => 'solamnia-portal',
        'machine_identifier' => 'machine-abc',
        'library_section_ids' => '11, 22',
    ]]);
});

it('is unconfigured without an owner token', function () {
    config(['services.plex.token' => null]);

    expect(app(Plex::class)->isConfigured())->toBeFalse();
});

it('shares exactly the configured library sections with the invitee', function () {
    Http::fake(['plex.tv/api/v2/shared_servers' => Http::response(status: 201)]);

    app(Plex::class)->share('sturm@example.com');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://plex.tv/api/v2/shared_servers'
            && $request->hasHeader('X-Plex-Token', 'plex-token')
            && $request->hasHeader('X-Plex-Client-Identifier', 'solamnia-portal')
            && $request['machineIdentifier'] === 'machine-abc'
            && $request['librarySectionIds'] === [11, 22]
            && $request['invitedEmail'] === 'sturm@example.com';
    });
});

it('treats an already-shared 422 as done', function () {
    Http::fake(['plex.tv/*' => Http::response(['message' => 'already shared'], 422)]);

    app(Plex::class)->share('sturm@example.com');
})->throwsNoExceptions();

it('fails on any other non-2xx response', function (int $status) {
    Http::fake(['plex.tv/*' => Http::response(status: $status)]);

    app(Plex::class)->share('sturm@example.com');
})->with([401, 500])->throws(MediaServerException::class);

it('fails when plex.tv is unreachable', function () {
    Http::fake(['plex.tv/*' => Http::failedConnection()]);

    app(Plex::class)->share('sturm@example.com');
})->throws(MediaServerException::class);

it('refuses to call Plex when no library sections are configured', function (?string $ids) {
    config(['services.plex.library_section_ids' => $ids]);
    Http::fake(['plex.tv/*' => Http::response()]);

    expect(fn () => app(Plex::class)->share('sturm@example.com'))
        ->toThrow(MediaServerException::class, 'library');

    Http::assertNothingSent();
})->with(['null' => null, 'empty' => '', 'blank' => ' , ']);

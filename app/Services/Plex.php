<?php

namespace App\Services;

use App\Contracts\MediaServer;
use App\Exceptions\MediaServerConfigurationException;
use App\Exceptions\MediaServerException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Shares the configured libraries through the community-documented plex.tv
 * `shared_servers` endpoint, as the server owner. The endpoint is unofficial
 * (python-plexapi's `inviteFriend` is the reference), so everything about it
 * lives here and in `config('services.plex')`.
 */
class Plex implements MediaServer
{
    public function isConfigured(): bool
    {
        return (bool) config('services.plex.token');
    }

    public function share(string $email): void
    {
        $sectionIds = $this->librarySectionIds();

        try {
            $response = Http::withHeaders([
                'X-Plex-Token' => config('services.plex.token'),
                'X-Plex-Client-Identifier' => config('services.plex.client_identifier'),
            ])->acceptJson()->post('https://plex.tv/api/v2/shared_servers', [
                'machineIdentifier' => config('services.plex.machine_identifier'),
                'librarySectionIds' => $sectionIds,
                'invitedEmail' => $email,
            ]);
        } catch (ConnectionException $e) {
            throw new MediaServerException("plex.tv unreachable: {$e->getMessage()}", previous: $e);
        }

        // 422 is plex.tv's "already shared with this account" - the outcome we wanted.
        if ($response->failed() && $response->status() !== 422) {
            throw new MediaServerException("plex.tv responded {$response->status()}: {$response->body()}");
        }
    }

    /**
     * An empty list means "share every library" to this endpoint, so refuse it
     * rather than over-share by misconfiguration.
     *
     * @return list<int>
     */
    private function librarySectionIds(): array
    {
        $ids = array_values(array_filter(array_map(
            intval(...),
            explode(',', (string) config('services.plex.library_section_ids')),
        )));

        return $ids ?: throw new MediaServerConfigurationException('No Plex library section ids are configured; refusing to share everything.');
    }
}

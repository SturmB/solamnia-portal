<?php

namespace App\Contracts;

use App\Exceptions\MediaServerConfigurationException;
use App\Exceptions\MediaServerException;

/**
 * The portal's one request of whichever media server is in use: give this
 * email access to the libraries. Membership is provider-agnostic (CONTEXT.md),
 * so no code outside the implementation and its config block may know the
 * provider's types, endpoints, or response shapes. Swapping Plex for Jellyfin
 * means one new class, one changed container binding, and the product's name
 * in the acceptance page's copy.
 */
interface MediaServer
{
    public function isConfigured(): bool;

    /**
     * Already having access is not a failure; implementations swallow it.
     *
     * @throws MediaServerConfigurationException when the portal's own config forbids the share; not worth retrying
     * @throws MediaServerException when the share could not be made
     */
    public function share(string $email): void;
}

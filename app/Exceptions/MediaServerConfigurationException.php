<?php

namespace App\Exceptions;

/**
 * The portal's own config forbids the share, so no retry can succeed.
 */
class MediaServerConfigurationException extends MediaServerException {}

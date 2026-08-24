<?php

use App\Mcp\Servers\CampaignServer;
use Laravel\Mcp\Facades\Mcp;

// Stdio only — no web transport. In production the operator's SSH access is
// the authentication boundary (docker compose exec … php artisan mcp:start).
Mcp::local('campaigns', CampaignServer::class);

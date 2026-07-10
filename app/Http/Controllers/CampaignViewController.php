<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Subscriber;

class CampaignViewController extends Controller
{
    /**
     * Render a Campaign's HTML in the browser via a signed, login-free link,
     * for readers whose mail client mangled the formatting. The Subscriber is
     * threaded through so the rendered page carries the same per-subscriber
     * footer (unsubscribe + view links) as the delivered email.
     */
    public function __invoke(Campaign $campaign, Subscriber $subscriber): string
    {
        return $campaign->renderHtml($subscriber);
    }
}

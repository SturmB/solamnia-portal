<?php

namespace App\Http\Controllers;

use App\Contracts\MediaServer;
use App\Enums\InviteStatus;
use App\Models\Invite;
use Illuminate\View\View;

class InviteShowController extends Controller
{
    public function __invoke(string $token, MediaServer $mediaServer): View
    {
        $invite = Invite::findByPlainToken($token);

        if ($invite?->status() === InviteStatus::Pending) {
            return view('invite.accept', [
                'invite' => $invite,
                'token' => $token,
                'mediaServerConfigured' => $mediaServer->isConfigured(),
                'inviterAddress' => config('services.plex.inviter_email'),
            ]);
        }

        return view('invite.invalid');
    }
}

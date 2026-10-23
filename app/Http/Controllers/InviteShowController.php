<?php

namespace App\Http\Controllers;

use App\Contracts\MediaServer;
use App\Enums\InviteStatus;
use App\Models\Invite;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InviteShowController extends Controller
{
    public function __invoke(Request $request, string $token, MediaServer $mediaServer): View
    {
        $invite = Invite::findByPlainToken($token);

        if ($invite?->status() === InviteStatus::Pending) {
            return view('invite.accept', [
                'invite' => $invite,
                'token' => $token,
                'mediaServerConfigured' => $mediaServer->isConfigured(),
                'inviterAddress' => config('services.plex.inviter_email'),
                'suggestedUsername' => $request->query->getString('username') ?: $invite->suggestedUsername(),
            ]);
        }

        return view('invite.invalid');
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\InviteStatus;
use App\Models\Invite;
use Illuminate\View\View;

class InviteShowController extends Controller
{
    public function __invoke(string $token): View
    {
        $invite = Invite::findByPlainTextToken($token);

        if ($invite?->status() === InviteStatus::Pending) {
            return view('invite.accept', ['invite' => $invite]);
        }

        return view('invite.invalid');
    }
}

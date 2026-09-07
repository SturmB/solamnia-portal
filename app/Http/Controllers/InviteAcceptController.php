<?php

namespace App\Http\Controllers;

use App\Enums\InviteStatus;
use App\Models\Invite;
use App\Services\Lldap;
use App\Services\Pushover;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InviteAcceptController extends Controller
{
    public function __invoke(Request $request, string $token, Lldap $lldap, Pushover $pushover): RedirectResponse|View
    {
        $request->merge(['username' => Str::lower($request->string('username'))]);
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'regex:/^[a-z][a-z0-9._-]{2,31}$/',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ], [
            'username.regex' => 'The username must start with a lowercase letter and contain only lowercase letters, numbers, dots, underscores, or hyphens.',
        ]);

        $invite = Invite::findByPlainTextToken($token);
        $username = $validated['username'];
        $name = $validated['name'];

        if ($invite->status() !== InviteStatus::Pending) {
            return view('invite.invalid');
        }

        $lldap->createUser($username, $invite->email, $name);
        $lldap->addUserToGroup($username, config('services.lldap.members_group'));

        DB::transaction(function () use ($invite, $username) {
            $pendingInvite = Invite::pending()->whereKey($invite)->lockForUpdate()->firstOrFail();
            $pendingInvite->accepted_at = now();
            $pendingInvite->username = $username;
            $pendingInvite->save();
        });

        $pushover->send("{$username} accepted their invite.", "{$username} has accepted the invite to join the server.");

        return view('invite.accepted', [
            'username' => $username,
            'resetUrl' => rtrim(config('services.authelia.base_url'), '/').'/reset-password/step1',
        ]);
    }
}

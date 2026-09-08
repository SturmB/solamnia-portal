<?php

namespace App\Http\Controllers;

use App\Enums\InviteStatus;
use App\Exceptions\LldapException;
use App\Models\Invite;
use App\Services\Lldap;
use App\Services\Pushover;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InviteAcceptController extends Controller
{
    public function __invoke(Request $request, string $token, Lldap $lldap, Pushover $pushover): View
    {
        $request->merge(['username' => Str::lower($request->string('username'))]);

        $invite = Invite::findByPlainTextToken($token);
        if ($invite?->status() !== InviteStatus::Pending) {
            return view('invite.invalid');
        }

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

        $username = $validated['username'];
        $name = $validated['name'];

        try {
            $existingUser = $lldap->findUser($username);

            if ($existingUser !== null && strcasecmp($existingUser['email'], $invite->email) !== 0) {
                throw ValidationException::withMessages([
                    'username' => 'The username is taken. Please choose another.',
                ]);
            }

            if ($existingUser === null) {
                $lldap->createUser($username, $invite->email, $name);
            }

            $lldap->addUserToGroup($username, Lldap::MEMBERS_GROUP);
        } catch (LldapException $e) {
            if ($e->isDuplicateUser()) {
                throw ValidationException::withMessages([
                    'username' => 'The username is taken. Please choose another.',
                ]);
            }
            $pushover->send(
                'Invite provisioning failed',
                "Could not provision {$username} for {$invite->email}. Their link still works. LLDAP said: {$e->getMessage()}",
                priority: 1,
            );

            return view('invite.retry', [
                'invite' => $invite,
                'token' => $token,
            ]);
        }

        if (! $invite->accept($username)) {
            return view('invite.invalid');
        }

        $pushover->send(
            "{$username} accepted their invite",
            "{$invite->email} is now the Member {$username}. They still need to set a password through Authelia.",
        );

        return view('invite.accepted', [
            'username' => $username,
            'resetUrl' => rtrim(config('services.authelia.base_url'), '/').'/reset-password/step1',
        ]);
    }
}

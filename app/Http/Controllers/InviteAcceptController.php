<?php

namespace App\Http\Controllers;

use App\Enums\InviteStatus;
use App\Exceptions\LldapException;
use App\Models\Invite;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\Lldap;
use App\Services\Pushover;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class InviteAcceptController extends Controller
{
    public function __construct(private readonly Pushover $pushover) {}

    public function __invoke(Request $request, string $token, Lldap $lldap): View
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
                throw $this->usernameTaken();
            }

            if ($existingUser === null) {
                try {
                    $lldap->createUser($username, $invite->email, $name);
                } catch (LldapException $e) {
                    throw $e->isDuplicateUser() ? $this->usernameTaken() : $e;
                }
            }

            $lldap->addUserToGroup($username, Lldap::MEMBERS_GROUP);
        } catch (LldapException $e) {
            // ponytail: a user created *and* grouped by an earlier attempt whose stamp then
            // failed lands here on every retry (LLDAP refuses the second group-add). Rare
            // enough to leave to the Pushover; sniff the group-add error too if it ever bites.
            $this->pushover->send(
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

        rescue(
            fn () => User::where('email', $invite->email)->firstOr(fn () => User::forceCreate([
                'name' => $name,
                'email' => $invite->email,
                'email_verified_at' => now(),
            ])),
            fn (Throwable $e) => $this->reportFollowUp(
                "Could not write the users row for {$invite->email} ({$username}). Their first SSO login will create it.",
                $e,
            ),
        );

        rescue(
            fn () => Subscriber::firstOrCreate(['email' => $invite->email], ['name' => $name]),
            fn (Throwable $e) => $this->reportFollowUp(
                "Could not create the Subscriber for {$invite->email} ({$username}). Add them to the newsletter by hand.",
                $e,
            ),
        );

        $this->pushover->send(
            "{$username} accepted their invite",
            "{$invite->email} is now the Member {$username}. They still need to set a password through Authelia.",
        );

        return view('invite.accepted', [
            'username' => $username,
            'resetUrl' => rtrim(config('services.authelia.base_url'), '/').'/reset-password/step1',
        ]);
    }

    private function usernameTaken(): ValidationException
    {
        return ValidationException::withMessages([
            'username' => 'The username is taken. Please choose another.',
        ]);
    }

    private function reportFollowUp(string $message, Throwable $e): void
    {
        $this->pushover->send(
            'Invite follow-up failed',
            "{$message} The error was: {$e->getMessage()}",
            priority: 1
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class SsoCallbackController extends Controller
{
    /**
     * Complete a Member login federated through Authelia.
     *
     * Membership is presence in the LLDAP `members` group; the local row is a
     * shadow of that identity, bound by the OIDC subject claim (ADR-0004).
     * Authorization stays local: is_admin is never written from a claim.
     */
    public function __invoke(): RedirectResponse
    {
        try {
            $claims = Socialite::driver('authelia')->user();
        } catch (InvalidStateException) {
            return redirect()->route('login');
        }

        // Fail closed: without the groups scope the claim arrives as null,
        // which must refuse like any other login outside `members`.
        abort_unless(
            in_array('members', $claims->groups ?? [], true),
            403,
            __('You are not a Member of the portal. Contact the Admin.'),
        );

        $member = User::where('oidc_sub', $claims->getId())->first()
            ?? User::where('email', $claims->getEmail())->first()
            ?? new User;

        // A row bound to a different subject is never re-bound by email —
        // matching by email is a one-time bootstrap, not a standing key —
        // and never duplicated: refuse instead of hitting the unique index.
        abort_if(
            $member->oidc_sub !== null && $member->oidc_sub !== $claims->getId(),
            403,
            __('This email address already belongs to another Member. Contact the Admin.'),
        );

        if (! $member->exists) {
            // Stamped, not confirmed: the address was asserted by the Admin in
            // the directory, a stronger claim than a self-service loop.
            $member->email_verified_at = now();
        }

        $member->fill([
            'name' => $claims->getName(),
            'email' => $claims->getEmail(),
            'oidc_sub' => $claims->getId(),
        ])->save();

        Auth::login($member, remember: true);

        session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}

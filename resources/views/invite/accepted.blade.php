{{-- The bridge between two emails from two senders (ADR-0006): the username shown here is
     what the invitee types into Authelia's reset page, which cannot be pre-filled. --}}
<x-layouts::public title="You're in" heading="You're in">
    <p>Your username on Solamnia is</p>

    <p class="font-display text-ink break-all text-4xl font-semibold tracking-[-0.01em]">{{ $username }}</p>

    <p>
        It's permanent, so keep it somewhere safe. You'll type it once more to set your password.
    </p>

    <h2 class="font-display text-ink mt-2 text-xl font-semibold">What happens next</h2>

    <p>
        There's no password yet. Setting one is done on Solamnia's sign-in service, Authelia, which is
        a separate site with its own emails. Ask it for a password reset, enter the username above, and
        a link arrives from Authelia rather than from the address that invited you.
    </p>

    <p class="mt-2">
        <a href="{{ $resetUrl }}"
            class="bg-violet text-night-deep duration-250 focus-visible:ring-violet-text focus-visible:ring-offset-night inline-flex items-center rounded-full px-8 py-3 font-bold transition hover:-translate-y-px hover:bg-[oklch(0.68_0.20_300)] hover:shadow-[0_0_32px_oklch(0.62_0.21_300/0.45)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-4 motion-reduce:transition-none motion-reduce:hover:translate-y-0">
            Set your password
        </a>
    </p>
</x-layouts::public>

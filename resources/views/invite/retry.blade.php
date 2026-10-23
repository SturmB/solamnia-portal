{{-- A failed attempt leaves the Invite live (CONTEXT.md: single-use means one successful
     redemption). LLDAP may already hold a half-made account; a retry with the same username
     picks it up, so the honest message is "go again", not "nothing happened". --}}
<x-layouts::public title="Something went wrong" heading="Something went wrong on our side">
    <p>
        Your account wasn't finished. Your invite link still works, and trying again with the
        same username picks up where this left off. You'll need to type your password again.
    </p>
    <p>
        Chris has been notified. Give it a moment, then
        <a href="{{ route('invites.show', $token) }}" class="text-violet-text underline underline-offset-4">try again</a>.
    </p>
</x-layouts::public>

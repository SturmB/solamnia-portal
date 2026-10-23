{{-- A failed attempt leaves the Invite live (CONTEXT.md: single-use means one successful
     redemption). LLDAP may already hold a half-made account; a retry with the same username
     picks it up, so the honest message is "go again", not "nothing happened". --}}
<x-layouts::public title="Something went wrong" heading="Something went wrong on our side">
    <p>
        Your account wasn't finished. Your invite link still works, and trying again picks up where this left off. Your
        username is already filled in; you'll need to type your password again.
    </p>
    <p>
        Chris has been notified. Give it a moment, then
        <a href="{{ route('invites.show', [$token, 'username' => $username]) }}"
            class="text-violet-text underline underline-offset-4">try again</a>.
    </p>
</x-layouts::public>

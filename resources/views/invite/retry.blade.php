{{-- A failed provisioning attempt leaves the Invite live (CONTEXT.md: single-use means one
     successful redemption), so the honest message is "nothing happened, go again". --}}
<x-layouts::public title="Something went wrong" heading="Something went wrong on our side">
    <p>
        Your username wasn't set up. Nothing was saved, so there's nothing half-finished to worry
        about, and your invite link still works.
    </p>
    <p>
        Chris has been notified. Give it a moment, then
        <a href="{{ route('invites.show', $token) }}" class="text-violet-text underline underline-offset-4">try again</a>.
    </p>
</x-layouts::public>

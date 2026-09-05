{{-- Placeholder: the username / display-name form lands with the acceptance ticket. --}}
<x-layouts::public title="You're invited" heading="You're invited">
    <p>
        This invite is for <strong class="font-medium">{{ $invite->email }}</strong>.
    </p>
    <p>
        Choosing your username isn't ready quite yet. Hold on to this link — it stays good
        until {{ $invite->expires_at->format('F j, Y') }}.
    </p>
</x-layouts::public>

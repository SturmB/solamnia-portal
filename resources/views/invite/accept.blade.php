{{-- Brand register. Field spec from DESIGN.md §Inputs: darker-than-night well, edge border,
     9px radius; focus is a violet-text outline. Errors are text, never colour alone. --}}
<x-layouts::public title="You're invited" heading="You're invited">
    <p>
        This invite is for <strong class="font-medium">{{ $invite->email }}</strong>. Pick the name
        you'll go by across Solamnia.
    </p>

    <form method="POST" action="{{ route('invites.accept', $token) }}" class="grid gap-[1.15rem]">
        @csrf

        <div class="grid gap-2">
            <label for="username" class="text-[0.95rem] font-medium">Username <span
                    class="text-muted">(permanent)</span></label>
            <input type="text" name="username" id="username" autocomplete="username" required
                value="{{ old('username') }}" aria-describedby="username-hint"
                @error('username') aria-invalid="true" @enderror
                class="border-edge focus-visible:outline-violet-text w-full rounded-[9px] border bg-[oklch(0.11_0.03_252)] px-[0.9rem] py-[0.7rem] text-base focus-visible:border-transparent focus-visible:outline-2 focus-visible:outline-offset-1">
            @error('username')
                <p class="text-[0.85rem]" role="alert">{{ $message }}</p>
            @enderror
            <p id="username-hint" class="text-muted text-[0.85rem]">
                3 to 32 characters, starting with a letter: lowercase letters, digits,
                <strong class="text-ink font-medium">.</strong>
                <strong class="text-ink font-medium">_</strong>
                <strong class="text-ink font-medium">-</strong>.
                You can't change it later.
            </p>
        </div>

        <div class="grid gap-2">
            <label for="name" class="text-[0.95rem] font-medium">Display name</label>
            <input type="text" name="name" id="name" autocomplete="name" required maxlength="255"
                value="{{ old('name', $invite->suggested_name) }}"
                class="border-edge focus-visible:outline-violet-text w-full rounded-[9px] border bg-[oklch(0.11_0.03_252)] px-[0.9rem] py-[0.7rem] text-base focus-visible:border-transparent focus-visible:outline-2 focus-visible:outline-offset-1">
        </div>

        <button type="submit"
            class="bg-violet text-night-deep duration-250 focus-visible:ring-violet-text focus-visible:ring-offset-night mt-2 inline-flex items-center justify-self-start rounded-full px-8 py-3 font-bold transition hover:-translate-y-px hover:bg-[oklch(0.68_0.20_300)] hover:shadow-[0_0_32px_oklch(0.62_0.21_300/0.45)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-4 motion-reduce:transition-none motion-reduce:hover:translate-y-0">
            Choose username
        </button>
    </form>
</x-layouts::public>

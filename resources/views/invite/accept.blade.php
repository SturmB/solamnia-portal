{{-- Brand register. Field spec from DESIGN.md §Inputs: darker-than-night well, edge border,
     9px radius; focus is a violet-text outline. Errors are text, never colour alone. --}}
<x-layouts::public title="You're invited" heading="You're invited">
    <p>
        This invite is for <strong class="font-medium">{{ $invite->email }}</strong>. Choose how you'll sign in to
        Solamnia.
    </p>

    <form method="POST" action="{{ route('invites.accept', $token) }}" class="grid gap-[1.15rem]">
        @csrf

        <div class="grid gap-2">
            <label for="username" class="text-[0.95rem] font-medium">Username <span
                    class="text-muted">(permanent)</span></label>
            <input type="text" name="username" id="username" autocomplete="username" required
                value="{{ old('username', $invite->suggestedUsername()) }}"
                aria-describedby="{{ $errors->has('username') ? 'username-error ' : '' }}username-hint"
                @error('username') aria-invalid="true" @enderror
                class="border-edge focus-visible:outline-violet-text aria-[invalid=true]:border-ink aria-[invalid=true]:ring-ink w-full rounded-[9px] border bg-[oklch(0.11_0.03_252)] px-[0.9rem] py-[0.7rem] text-base focus-visible:border-transparent focus-visible:outline-2 focus-visible:outline-offset-1 aria-[invalid=true]:ring-1">
            @error('username')
                <p id="username-error" role="alert"
                    class="text-ink flex items-start gap-1.5 text-[0.85rem] font-semibold">
                    <flux:icon.exclamation-circle variant="micro" aria-hidden="true" class="mt-0.5" />
                    {{ $message }}
                </p>
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
                @error('name') aria-describedby="name-error" aria-invalid="true" @enderror
                class="border-edge focus-visible:outline-violet-text aria-[invalid=true]:border-ink aria-[invalid=true]:ring-ink w-full rounded-[9px] border bg-[oklch(0.11_0.03_252)] px-[0.9rem] py-[0.7rem] text-base focus-visible:border-transparent focus-visible:outline-2 focus-visible:outline-offset-1 aria-[invalid=true]:ring-1">
            @error('name')
                <p id="name-error" role="alert" class="text-ink flex items-start gap-1.5 text-[0.85rem] font-semibold">
                    <flux:icon.exclamation-circle variant="micro" aria-hidden="true" class="mt-0.5" />
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="password" class="text-[0.95rem] font-medium">Password</label>
            <div class="relative">
                <input type="password" name="password" id="password" autocomplete="new-password" required
                    minlength="12"
                    aria-describedby="{{ $errors->has('password') ? 'password-error ' : '' }}password-hint"
                    @error('password') aria-invalid="true" @enderror
                    class="border-edge focus-visible:outline-violet-text aria-[invalid=true]:border-ink aria-[invalid=true]:ring-ink w-full rounded-[9px] border bg-[oklch(0.11_0.03_252)] py-[0.7rem] pl-[0.9rem] pr-20 text-base focus-visible:border-transparent focus-visible:outline-2 focus-visible:outline-offset-1 aria-[invalid=true]:ring-1">
                <button type="button" data-password-toggle aria-controls="password"
                    class="text-muted hover:text-ink focus-visible:outline-violet-text absolute inset-y-0 right-0 rounded-[9px] px-[0.9rem] text-[0.85rem] font-medium focus-visible:outline-2">
                    <span data-password-toggle-label>Show</span><span class="sr-only"> password</span>
                </button>
            </div>
            @error('password')
                <p id="password-error" role="alert"
                    class="text-ink flex items-start gap-1.5 text-[0.85rem] font-semibold">
                    <flux:icon.exclamation-circle variant="micro" aria-hidden="true" class="mt-0.5" />
                    {{ $message }}
                </p>
            @enderror
            <p id="password-hint" class="text-muted text-[0.85rem]">
                At least 12 characters. Passwords known from data breaches are refused.
            </p>
        </div>

        <button type="submit"
            class="bg-violet text-night-deep duration-250 focus-visible:ring-violet-text focus-visible:ring-offset-night mt-2 inline-flex items-center justify-self-start rounded-full px-8 py-3 font-bold transition hover:-translate-y-px hover:bg-[oklch(0.68_0.20_300)] hover:shadow-[0_0_32px_oklch(0.62_0.21_300/0.45)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-4 motion-reduce:transition-none motion-reduce:hover:translate-y-0">
            Create my account
        </button>
    </form>

    @if ($mediaServerConfigured)
        <p class="mt-2">
            Plex will email you an invite{{ $inviterAddress ? " from {$inviterAddress}" : '' }}. Accept it with this
            same address.
        </p>
    @endif

    <script>
        document.querySelector("[data-password-toggle]").addEventListener("click", (event) => {
            const button = event.currentTarget;
            const input = document.getElementById(button.getAttribute("aria-controls"));
            const showing = input.type === "text";

            input.type = showing ? "password" : "text";
            button.querySelector("[data-password-toggle-label]").textContent = showing ? "Show" : "Hide";
        });
    </script>
</x-layouts::public>

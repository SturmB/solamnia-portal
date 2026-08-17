<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('One account for all of Solamnia — the same sign-in as your photos, books, and recipes.')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <flux:button variant="primary" :href="route('auth.redirect')" class="w-full" data-test="sso-login-button">
            {{ __('Sign in with Solamnia') }}
        </flux:button>
    </div>
</x-layouts::auth>

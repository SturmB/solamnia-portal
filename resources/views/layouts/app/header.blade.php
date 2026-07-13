<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="bg-night text-ink min-h-screen font-sans" data-aurora="0.25">
    {{-- The living sky, rendered by resources/js/aurora.js. @persist keeps the
         one canvas (and its GL context) alive across wire:navigate. --}}
    @persist('aurora')
        <canvas id="aurora" aria-hidden="true"></canvas>
    @endpersist

    {{-- Top nav: the ONE backdrop-blur surface (DESIGN.md "One Blur Rule"),
         a hairline lit edge, the night sky showing faintly through. --}}
    <flux:header container class="border-edge bg-night/70 border-b backdrop-blur-lg">
        <flux:sidebar.toggle class="mr-2 lg:hidden" icon="bars-2" inset="left" />

        <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

        {{-- :current drives the violet underline via the accent token.
             ms-8 keeps the nav off the wordmark's shoulder. --}}
        <flux:navbar class="-mb-px ms-8 max-lg:hidden">
            <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                wire:navigate>
                {{ __('Dashboard') }}
            </flux:navbar.item>
        </flux:navbar>

        <flux:spacer />

        <x-desktop-user-menu />
    </flux:header>

    {{-- Mobile drawer: same destinations, shown only below lg. --}}
    <flux:sidebar collapsible="mobile" sticky class="border-edge bg-panel border-e lg:hidden">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <flux:sidebar.collapse
                class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                wire:navigate>
                {{ __('Dashboard') }}
            </flux:sidebar.item>
        </flux:sidebar.nav>
    </flux:sidebar>

    {{ $slot }}

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>

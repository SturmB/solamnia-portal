<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Solamnia</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @fonts
    @vite(['resources/css/app.css'])
</head>

<body class="min-h-svh bg-screen font-sans text-ink">
    <main class="relative isolate flex min-h-svh flex-col items-center justify-center px-6 py-16 text-center">

        <div aria-hidden="true"
             class="pointer-events-none absolute inset-0 -z-10"
             style="background: radial-gradient(58% 50% at 50% 40%,
                    color-mix(in oklab, var(--color-violet-lit) 26%, transparent), transparent 72%);"></div>

        <img src="{{ asset('images/solamnia-wordmark-white.png') }}"
             alt="Solamnia"
             width="737" height="114"
             class="h-11 w-auto sm:h-14"
             style="filter: drop-shadow(0 0 30px color-mix(in oklab, var(--color-violet-lit) 38%, transparent));">

        <p class="mt-8 font-display-sc text-lg tracking-[0.12em] text-balance text-[oklch(0.82_0.02_296)]">
            Reserved seating for friends of the house.
        </p>

        <div class="mt-10 flex flex-col items-center gap-5">
            @auth
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center rounded-lg bg-violet-deep px-8 py-3 font-medium text-white
                          shadow-[0_18px_55px_-12px_var(--color-violet-deep)]
                          transition duration-150 ease-out
                          hover:shadow-[0_22px_70px_-10px_var(--color-violet-lit)]
                          focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-lit
                          focus-visible:ring-offset-4 focus-visible:ring-offset-screen">
                    Enter
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="inline-flex items-center rounded-lg bg-violet-deep px-8 py-3 font-medium text-white
                          shadow-[0_18px_55px_-12px_var(--color-violet-deep)]
                          transition duration-150 ease-out
                          hover:shadow-[0_22px_70px_-10px_var(--color-violet-lit)]
                          focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-lit
                          focus-visible:ring-offset-4 focus-visible:ring-offset-screen">
                    Log in
                </a>

                <p class="text-sm text-ink-muted">
                    Invited? Your invitation link is waiting in your email.
                </p>
            @endauth
        </div>
    </main>
</body>
</html>

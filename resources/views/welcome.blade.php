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

    <style>
        /* ---- Projector: a designed volumetric beam (no photo, no AI tells) ---- */
        .projector { position: absolute; inset: 0; z-index: -30; overflow: hidden; pointer-events: none; transform-origin: 50% 0; }
        .beam-grow { position: absolute; inset: 0; }

        /* soft outer cone */
        .beam-cone {
            position: absolute; inset: 0;
            background: conic-gradient(at 50% -6%,
                transparent 161deg,
                oklch(0.70 0.15 296 / 0.05) 168deg,
                oklch(0.80 0.15 296 / 0.34) 180deg,
                oklch(0.70 0.15 296 / 0.05) 192deg,
                transparent 199deg);
            -webkit-mask: linear-gradient(to bottom, #000 4%, rgba(0,0,0,0.78) 42%, transparent 90%);
                    mask: linear-gradient(to bottom, #000 4%, rgba(0,0,0,0.78) 42%, transparent 90%);
            filter: blur(7px);
        }
        /* hot inner core — gives the beam depth instead of one flat gradient */
        .beam-core {
            position: absolute; inset: 0;
            background: conic-gradient(at 50% -4%,
                transparent 175deg,
                oklch(0.93 0.11 296 / 0.30) 180deg,
                transparent 185deg);
            -webkit-mask: linear-gradient(to bottom, #000 0%, rgba(0,0,0,0.55) 32%, transparent 72%);
                    mask: linear-gradient(to bottom, #000 0%, rgba(0,0,0,0.55) 32%, transparent 72%);
            filter: blur(11px);
        }
        /* drifting haze/dust caught in the light (background-position animates, see below) */
        .beam-dust {
            position: absolute; inset: 0;
            background-image: url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22180%22 height=%22180%22%3E%3Cfilter id=%22d%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.65%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23d)%22/%3E%3C/svg%3E');
            background-size: 180px;
            mix-blend-mode: screen; opacity: 0.18; filter: contrast(1.4) brightness(1.15);
            -webkit-mask: radial-gradient(52% 46% at 50% 16%, #000, transparent 76%);
                    mask: radial-gradient(52% 46% at 50% 16%, #000, transparent 76%);
        }
        /* projector lens flare + top bloom */
        .beam-lens {
            position: absolute; inset: 0;
            background:
                radial-gradient(62% 34% at 50% -3%, oklch(0.52 0.13 296 / 0.35), transparent 70%),
                radial-gradient(10rem 4rem at 50% -1%, oklch(0.95 0.07 296 / 0.85), transparent 66%);
        }
        /* floor pool where the beam lands */
        .beam-pool {
            position: absolute; left: 0; right: 0; bottom: 0; height: 60%;
            background: radial-gradient(48% 62% at 50% 108%, oklch(0.56 0.13 296 / 0.22), transparent 70%);
        }

        /* ---- CTA material — a lit surface, not a flat rectangle ---- */
        .btn-cta {
            background: linear-gradient(to bottom, oklch(0.40 0.19 296), oklch(0.30 0.17 296));
            border: 1px solid oklch(0.58 0.16 296 / 0.55);
            box-shadow: inset 0 1px 0 oklch(0.88 0.12 296 / 0.35), 0 18px 45px -14px oklch(0.32 0.176 296);
            transition: filter 150ms ease-out, box-shadow 150ms ease-out;
        }
        .btn-cta:hover {
            filter: brightness(1.12);
            box-shadow: inset 0 1px 0 oklch(0.92 0.12 296 / 0.5), 0 22px 60px -12px oklch(0.72 0.15 296 / 0.6);
        }

        /* ---- Motion: one-shot warm-up reveal + subtle continuous life. Brand surface only,
             and strictly gated so the base state is fully visible & still under reduced-motion. ---- */
        @media (prefers-reduced-motion: no-preference) {
            /* one-shot warm-up, then the beam keeps breathing + haze keeps drifting */
            .beam-grow { animation: beam-warm 1900ms cubic-bezier(0.16, 1, 0.3, 1) both,
                                    beam-breathe 5.5s ease-in-out 1900ms infinite; transform-origin: 50% 0; }
            .projector { animation: beam-sway 13s ease-in-out infinite alternate; }
            .beam-dust { animation: dust-drift 12s linear infinite; }
            .beam-lens { animation: lens-flare 1500ms ease-out both; }
            .beam-pool { animation: fx-fade 1200ms ease-out both; }
            .fx-rise { animation: fx-rise 900ms cubic-bezier(0.16, 1, 0.3, 1) both; }
            .d1 { animation-delay: 650ms; } .d2 { animation-delay: 880ms; } .d3 { animation-delay: 1120ms; }

            @keyframes beam-warm { 0% { opacity: 0; transform: scaleY(0.38) scaleX(0.75); } 60% { opacity: 1; } 100% { opacity: 1; transform: none; } }
            @keyframes beam-breathe { 0%, 100% { filter: brightness(0.9); } 50% { filter: brightness(1.18); } }
            @keyframes beam-sway { from { transform: rotate(-0.9deg); } to { transform: rotate(0.9deg); } }
            @keyframes dust-drift { from { background-position: 0 0; } to { background-position: 40px -240px; } }
            @keyframes lens-flare { 0% { opacity: 0; } 28% { opacity: 1; filter: brightness(1.5); } 100% { opacity: 1; filter: brightness(1); } }
            @keyframes fx-fade { from { opacity: 0; } to { opacity: 1; } }
            @keyframes fx-rise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        }
    </style>
</head>

{{-- Tokens directly (bg-screen / text-ink), not dark: variants — this surface is designed dark. --}}
<body class="min-h-svh bg-screen font-sans text-ink">
    <main class="relative isolate flex min-h-svh flex-col items-center justify-center px-6 py-16 text-center">

        {{-- Designed projector beam: soft cone + hot core + drifting haze, a lens flare, and a
             floor pool. It warms up on load and sways slowly forever (motion-safe). --}}
        <div class="projector" aria-hidden="true">
            <div class="beam-grow">
                <div class="beam-cone"></div>
                <div class="beam-core"></div>
                <div class="beam-dust"></div>
            </div>
            <div class="beam-lens fx-lens"></div>
            <div class="beam-pool fx-lens d1"></div>
        </div>

        {{-- Filmic grain over everything — kills CSS banding, adds material texture. --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 opacity-[0.10] mix-blend-soft-light"
             style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22120%22 height=%22120%22%3E%3Cfilter id=%22n%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.8%22 numOctaves=%222%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23n)%22/%3E%3C/svg%3E');background-size:120px;"></div>

        {{-- Legibility scrim: edge vignette + downward wash so lower text stays >=4.5:1. --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-20"
             style="background:
                radial-gradient(135% 115% at 50% 32%, transparent 42%, oklch(0.11 0.02 296 / 0.5) 100%),
                linear-gradient(to bottom, transparent 55%, oklch(0.11 0.02 296 / 0.45));"></div>

        <img src="{{ asset('images/solamnia-wordmark-white.png') }}"
             alt="Solamnia"
             width="737" height="114"
             class="fx-rise d1 h-11 w-auto sm:h-14"
             style="filter: drop-shadow(0 0 30px oklch(0.72 0.15 296 / 0.45));">

        <p class="fx-rise d2 mt-8 font-display-sc text-lg tracking-[0.12em] text-balance text-[oklch(0.82_0.02_296)]">
            Reserved seating for friends of the house.
        </p>

        <div class="fx-rise d3 mt-10 flex flex-col items-center gap-5">
            @auth
                {{-- Already a Member — send them in, don't make them log in again. --}}
                <a href="{{ route('dashboard') }}"
                   class="btn-cta inline-flex items-center rounded-lg px-8 py-3 font-medium text-white
                          focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-lit
                          focus-visible:ring-offset-4 focus-visible:ring-offset-screen">
                    Enter
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="btn-cta inline-flex items-center rounded-lg px-8 py-3 font-medium text-white
                          focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-lit
                          focus-visible:ring-offset-4 focus-visible:ring-offset-screen">
                    Log in
                </a>

                {{-- Invites are single-use, tokenised email links — no public sign-up page to link
                     to, so this is honest plain text, not a dead button. --}}
                <p class="text-sm text-ink-muted">
                    Invited? Your invitation link is waiting in your email.
                </p>
            @endauth
        </div>
    </main>
</body>
</html>

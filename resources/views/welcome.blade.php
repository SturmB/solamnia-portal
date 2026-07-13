<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark antialiased">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Solamnia</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @vite(['resources/css/app.css'])

    <style>
        /* ---- The velvet house: video curtain ground (P10), poster beneath ---- */
        .velvet-poster {
            position: fixed;
            inset: 0;
            z-index: -40;
            background: url('{{ asset('media/velvet-hero.webp') }}') center / cover no-repeat,
                var(--color-night);
        }

        .velvet-video {
            position: fixed;
            inset: 0;
            z-index: -30;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Reduced motion: the drapes hold still — poster only. */
        @media (prefers-reduced-motion: reduce) {
            .velvet-video {
                display: none;
            }
        }

        /* Legibility scrim over the video: edge vignette + downward wash,
           in night tones, so hero text clears 4.5:1 whatever the drapes do. */
        .velvet-scrim {
            position: fixed;
            inset: 0;
            z-index: -20;
            pointer-events: none;
            background:
                radial-gradient(135% 115% at 50% 35%, transparent 30%, oklch(0.10 0.03 250 / 0.62) 100%),
                linear-gradient(180deg, oklch(0.10 0.03 250 / 0.30), transparent 35% 55%, oklch(0.10 0.03 250 / 0.55));
        }

        /* ---- The spotlight (P1): a soft violet pool that lerps after the cursor ---- */
        #beam {
            position: fixed;
            left: 0;
            top: 0;
            z-index: -10;
            width: 80vmin;
            height: 80vmin;
            margin: -40vmin 0 0 -40vmin;
            pointer-events: none;
            border-radius: 50%;
            /* Warm rose-violet, sampled from the lamplight falling on the
               velvet — the beam reads as the same light source. */
            background: radial-gradient(circle, oklch(0.84 0.10 340 / 0.11), transparent 62%);
            will-change: transform;
            opacity: 0;
            transition: opacity 600ms ease-out;
        }

        #beam.on {
            opacity: 1;
        }

        /* Local text protection (Aurora's hero-scrim): a soft pool of night
           bleeding out behind the hero, so ink never fights a bright fold —
           darkness without a hard card edge. */
        .hero-scrim {
            position: relative;
        }

        .hero-scrim::before {
            content: "";
            position: absolute;
            inset: -14% -30%;
            z-index: -1;
            pointer-events: none;
            background: radial-gradient(55% 58% at 50% 48%,
                    oklch(0.10 0.03 250 / 0.78),
                    oklch(0.10 0.03 250 / 0.42) 58%,
                    transparent 80%);
        }
    </style>
</head>

{{-- Brand surface, designed dark: tokens directly, no dark: variants. --}}

<body class="bg-night text-ink min-h-svh font-sans">
    <div class="velvet-poster" aria-hidden="true"></div>
    <video class="velvet-video" aria-hidden="true" autoplay muted loop playsinline
        poster="{{ asset('media/velvet-hero.webp') }}">
        <source src="{{ asset('media/velvet-loop.mp4') }}" type="video/mp4">
    </video>
    <div class="velvet-scrim" aria-hidden="true"></div>
    <div id="beam" aria-hidden="true"></div>

    <main class="relative flex min-h-svh flex-col items-center justify-center px-6 py-16 text-center">
        <div class="hero-scrim flex flex-col items-center gap-6">
            <img src="{{ asset('images/solamnia-emblem.svg') }}" alt="" width="74" height="74"
                class="size-16 sm:size-[74px]">

            <h1>
                <img src="{{ asset('images/solamnia-wordmark-white.png') }}" alt="Solamnia" width="737"
                    height="114" class="h-11 w-auto sm:h-14"
                    style="filter: drop-shadow(0 2px 24px oklch(0.10 0.03 250 / 0.8));">
            </h1>

            <p class="text-ink max-w-[32ch] text-balance text-lg sm:text-xl">
                Reserved seating for friends of the house.
            </p>

            <div class="mt-4 flex flex-col items-center gap-5">
                @auth
                    {{-- Already a Member — send them in, don't make them sign in again. --}}
                    <a href="{{ route('dashboard') }}"
                        class="bg-violet text-night-deep duration-250 focus-visible:ring-violet-text focus-visible:ring-offset-night inline-flex items-center rounded-full px-8 py-3 font-bold transition hover:-translate-y-px hover:bg-[oklch(0.68_0.20_300)] hover:shadow-[0_0_32px_oklch(0.62_0.21_300/0.45)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-4 motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                        Enter
                    </a>
                @else
                    {{-- Today this is the Fortify door; it becomes the Authelia
                     (OIDC) hand-off when the SSO client work lands (ADR-0001). --}}
                    <a href="{{ route('login') }}"
                        class="bg-violet text-night-deep duration-250 focus-visible:ring-violet-text focus-visible:ring-offset-night inline-flex items-center rounded-full px-8 py-3 font-bold transition hover:-translate-y-px hover:bg-[oklch(0.68_0.20_300)] hover:shadow-[0_0_32px_oklch(0.62_0.21_300/0.45)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-4 motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                        Sign in
                    </a>

                    {{-- Invites are single-use, tokenised email links — no public sign-up
                     page to link to, so this is honest plain text, not a dead button. --}}
                    {{-- A notch above --color-muted: this sits over video, not flat night. --}}
                    <p class="text-sm text-[oklch(0.84_0.02_250)]">
                        Invited? Your invitation link is waiting in your email.
                    </p>
                @endauth
            </div>
        </div>
    </main>

    <script>
        /* Data-saver: don't stream 3 MB of drapes — hold the poster. */
        (() => {
            const video = document.querySelector('.velvet-video');
            if (video && navigator.connection && navigator.connection.saveData) {
                video.remove();
            }
        })();

        /* Spotlight: lerps toward the cursor (~140ms lag). Hover-capable,
           motion-tolerant devices only. (P1 "Marquee", retuned to Aurora violet.) */
        (() => {
            const beam = document.getElementById('beam');
            if (!beam || !matchMedia('(hover: hover) and (prefers-reduced-motion: no-preference)').matches) {
                return;
            }

            let tx = innerWidth / 2,
                ty = innerHeight * 0.35;
            let x = tx,
                y = ty;
            let last = performance.now();
            let seen = false;

            addEventListener('pointermove', (e) => {
                tx = e.clientX;
                ty = e.clientY;
                if (!seen) {
                    seen = true;
                    beam.classList.add('on');
                }
            });

            (function tick(now) {
                const dt = Math.min(now - last, 100);
                last = now;
                const k = 1 - Math.exp(-dt / 140);
                x += (tx - x) * k;
                y += (ty - y) * k;
                beam.style.transform = `translate(${x}px, ${y}px)`;
                requestAnimationFrame(tick);
            })(last);
        })();
    </script>
</body>

</html>

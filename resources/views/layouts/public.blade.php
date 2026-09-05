{{-- Brand-register shell for the public Invite pages (PRODUCT.md: the one exception
     alongside the landing page). Same Velvet House ground as the landing (DESIGN.md §5);
     the cursor spotlight is landing-only until the acceptance form gives this page a
     reason to earn it. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark antialiased">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title }} · Solamnia</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @vite(['resources/css/app.css'])
</head>

<body class="bg-night text-ink min-h-svh font-sans">
    <div class="velvet-poster" aria-hidden="true"></div>
    <video class="velvet-video" aria-hidden="true" autoplay muted loop playsinline
        poster="{{ asset('media/velvet-hero.webp') }}">
        <source src="{{ asset('media/velvet-loop.mp4') }}" type="video/mp4">
    </video>
    <div class="velvet-scrim" aria-hidden="true"></div>

    <main class="flex min-h-svh flex-col items-center justify-center px-6 py-16">
        <div class="flex w-full max-w-md flex-col items-center gap-8">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-4">
                <img src="{{ asset('images/solamnia-emblem.svg') }}" alt="" width="56" height="56"
                    class="size-14">
                <img src="{{ asset('images/solamnia-wordmark-white.png') }}" alt="Solamnia" width="737"
                    height="114" class="h-8 w-auto">
            </a>

            <section
                class="bg-panel border-edge w-full rounded-[14px] border p-8 shadow-[inset_0_1px_0_0_var(--color-edge-top)]">
                <h1 class="font-display text-ink text-3xl font-semibold leading-tight tracking-[-0.01em]">
                    {{ $heading }}
                </h1>
                <div class="text-ink mt-4 flex flex-col gap-4 text-[1.0625rem] leading-relaxed">
                    {{ $slot }}
                </div>
            </section>
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
    </script>
</body>

</html>

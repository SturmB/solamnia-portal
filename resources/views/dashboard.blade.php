<x-layouts::app :title="__('Dashboard')">
    <div class="max-w-280 mx-auto flex h-full w-full flex-1 flex-col gap-4">
        @foreach (auth()->user()->unreadNotifications as $notification)
            <flux:callout icon="envelope" :heading="$notification->data['heading'] ?? null"
                :text="$notification->data['message']">
                <x-slot name="controls">
                    <form method="POST" action="{{ route('notifications.dismiss', $notification) }}">
                        @csrf
                        <flux:button type="submit" variant="ghost" size="sm" icon="x-mark"
                            aria-label="Dismiss this notice" />
                    </form>
                </x-slot>
            </flux:callout>
        @endforeach
        <section aria-labelledby="services-heading" class="w-full">
            <h1 id="services-heading"
                class="font-display text-ink mb-6 text-[clamp(1.5rem,1.1rem+1.6vw,2.1rem)] font-semibold leading-tight tracking-[-0.01em]">
                Your services
            </h1>

            <ul role="list"
                class="bg-panel border-edge divide-edge divide-y rounded-[14px] border shadow-[inset_0_1px_0_0_var(--color-edge-top)]">
                @foreach (config('portal.services') as $service)
                    <li
                        class="grid grid-cols-[1fr_auto] items-center gap-x-6 gap-y-1 px-6 py-4 min-[720px]:grid-cols-[10rem_1fr_auto]">
                        <h2 class="font-display text-ink text-lg font-semibold">{{ $service['name'] }}</h2>
                        <p class="text-muted col-span-2 min-[720px]:col-span-1">{{ $service['description'] }}</p>
                        <a href="{{ $service['url'] }}" aria-label="Open {{ $service['name'] }}"
                            class="text-violet-text hover:bg-violet/10 focus-visible:outline-violet-text col-start-2 row-start-1 inline-flex min-h-11 items-center justify-self-end rounded-full px-4 font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 motion-reduce:transition-none min-[720px]:col-start-3">
                            Open →
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</x-layouts::app>

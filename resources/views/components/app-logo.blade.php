@props([
    'sidebar' => false,
])

{{-- Nav brand: the Solamnia emblem + wordmark in Clash Display. The $sidebar
     flag is accepted for call-site compatibility; both variants render the same. --}}
<a {{ $attributes->merge(['class' => 'flex items-center gap-2 font-display text-lg font-semibold text-ink']) }}>
    <img src="{{ asset('images/solamnia-emblem.svg') }}" alt="" class="size-7 shrink-0" />
    <span>Solamnia</span>
</a>

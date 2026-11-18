@props(['compact' => false])

<a href="{{ route('home') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }} aria-label="Вектор — на главную">
    <span class="relative grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-2xl bg-inverse text-white shadow-sm">
        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" aria-hidden="true">
            <path d="M4.5 5.5L12 19l7.5-13.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 5.5L12 11l3-5.5" stroke="#8FA8FF" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
    @unless($compact)
        <span class="text-[17px] font-extrabold tracking-[-0.03em] text-ink">Вектор</span>
    @endunless
</a>

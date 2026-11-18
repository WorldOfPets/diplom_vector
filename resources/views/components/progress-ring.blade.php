@props(['value' => 0, 'size' => 64, 'stroke' => 6, 'color' => '#2D5BFF', 'label' => null])

@php
    $value = max(0, min(100, (float) $value));
    $radius = ($size - $stroke) / 2;
    $circumference = 2 * M_PI * $radius;
    $offset = $circumference * (1 - $value / 100);
    $center = $size / 2;
@endphp

<div class="inline-flex flex-col items-center gap-1">
    <div class="relative inline-grid place-items-center" style="width: {{ $size }}px; height: {{ $size }}px;">
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" class="absolute inset-0" aria-hidden="true">
            <circle cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius }}" fill="none" stroke="var(--color-line)" stroke-width="{{ $stroke }}" />
            <circle cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius }}" fill="none" stroke="{{ $color }}" stroke-width="{{ $stroke }}"
                    stroke-linecap="round" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}"
                    transform="rotate(-90 {{ $center }} {{ $center }})" />
        </svg>
        <span class="relative text-[13px] font-extrabold tabular-nums text-ink">{{ (int) round($value) }}%</span>
    </div>
    @if($label)
        <span class="text-[11px] font-semibold text-muted">{{ $label }}</span>
    @endif
</div>

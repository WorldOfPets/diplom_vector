@props(['label', 'value', 'hint' => null, 'icon' => null, 'tone' => 'ink'])

@php
    $toneClasses = [
        'ink' => 'text-ink',
        'brand' => 'text-brand',
        'mint' => 'text-mint-ink',
        'coral' => 'text-coral-ink',
        'lilac' => 'text-lilac-ink',
        'amber' => 'text-amber-ink',
    ];
    $color = $toneClasses[$tone] ?? $toneClasses['ink'];
@endphp

<div class="card min-w-0 p-4 sm:p-5">
    <div class="flex items-start justify-between gap-2 sm:gap-3">
        <div class="min-w-0 flex-1">
            <p class="break-words text-[11px] font-bold uppercase tracking-[0.16em] text-muted">{{ $label }}</p>
            <p class="mt-2 text-2xl font-extrabold tracking-tight {{ $color }}">{{ $value }}</p>
        </div>
        @if($icon)
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-paper-deep/70 text-ink-soft">
                <x-icon :name="$icon" class="h-4 w-4" />
            </span>
        @endif
    </div>
    @if($hint)
        <p class="mt-2 break-words text-xs leading-relaxed text-muted">{{ $hint }}</p>
    @endif
</div>

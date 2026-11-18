@props(['type' => 'info'])

@php
    $styles = [
        'success' => 'border-mint/25 bg-mint-soft text-mint-ink',
        'error' => 'border-rose/20 bg-rose/5 text-rose',
        'warning' => 'border-amber/25 bg-amber-soft text-amber-ink',
        'info' => 'border-brand/20 bg-brand-soft/60 text-brand-deep',
    ];
    $icons = [
        'success' => 'check-circle',
        'error' => 'warning',
        'warning' => 'warning',
        'info' => 'info',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start gap-2.5 rounded-2xl border px-4 py-3 text-sm '.($styles[$type] ?? $styles['info'])]) }}>
    <x-icon :name="$icons[$type] ?? 'info'" class="mt-0.5 h-4 w-4 shrink-0" />
    <div class="leading-relaxed">{{ $slot }}</div>
</div>

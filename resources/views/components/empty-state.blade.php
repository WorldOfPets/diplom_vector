@props(['icon' => 'sparkles', 'title', 'text' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-3xl border border-dashed border-line bg-surface/50 px-6 py-10 text-center']) }}>
    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-paper-deep text-ink-soft">
        <x-icon :name="$icon" class="h-5 w-5" />
    </span>
    <p class="mt-4 text-base font-bold text-ink">{{ $title }}</p>
    @if($text)
        <p class="mt-1 max-w-sm text-sm leading-relaxed text-muted">{{ $text }}</p>
    @endif
    @if(! $slot->isEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>

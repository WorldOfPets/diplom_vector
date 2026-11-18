@php
    $left = $config['left'] ?? [];
    $right = $config['right'] ?? [];
@endphp

<div class="card p-5 sm:p-6" x-data x-init="$dispatch('response-patch', { pairs: { ...(response.pairs || {}) } })">
    <div class="flex items-start gap-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-lilac-soft text-lilac"><x-icon name="grid" class="h-4 w-4" /></span>
        <div>
            <p class="text-sm font-bold">Сопоставьте</p>
            <p class="mt-1 text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Для каждого пункта слева выберите подходящий вариант справа.' }}</p>
        </div>
    </div>

    <div class="mt-5 space-y-3">
        @foreach($left as $item)
            <div class="grid gap-2 rounded-2xl border border-line bg-surface p-3 sm:grid-cols-[1fr_1fr] sm:items-center sm:gap-4">
                <p class="text-sm font-semibold leading-snug">{{ $item['label'] }}</p>
                <select class="input py-2.5 text-xs" x-model="response.pairs['{{ $item['id'] }}']" aria-label="{{ $item['label'] }}">
                    <option value="">Выберите…</option>
                    @foreach($right as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
    </div>
</div>

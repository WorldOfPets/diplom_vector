@php
    $items = $config['items'] ?? [];
    $budget = (int) ($config['budget'] ?? 100);
    $defaults = collect($items)->mapWithKeys(fn ($item): array => [$item['id'] => 0])->all();
@endphp

<div class="card p-5 sm:p-6" x-data x-init="$dispatch('response-patch', { amounts: { ...@js($defaults), ...(response.amounts || {}) } })">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-amber-soft text-amber-ink"><x-icon name="sparkles" class="h-4 w-4" /></span>
            <div>
                <p class="text-sm font-bold">Распределите ресурс</p>
                <p class="mt-1 max-w-lg text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'У вас есть бюджет. Отдайте его тому, что для вас важнее.' }}</p>
            </div>
        </div>
        <div class="rounded-2xl bg-paper px-4 py-2 text-right">
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted">Осталось</p>
            <p class="text-xl font-extrabold text-ink" x-text="{{ $budget }} - Object.values(response.amounts || {}).reduce((sum, value) => sum + Number(value || 0), 0)"></p>
        </div>
    </div>

    <div class="mt-5 space-y-3">
        @foreach($items as $item)
            <div class="rounded-2xl border border-line bg-surface p-4">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-paper text-lg">{{ $item['emoji'] ?? '✦' }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold">{{ $item['label'] }}</p>
                                @if(! empty($item['description']))
                                    <p class="mt-0.5 text-xs leading-relaxed text-muted">{{ $item['description'] }}</p>
                                @endif
                            </div>
                            <span class="rounded-xl bg-paper px-2.5 py-1 text-xs font-extrabold text-ink" x-text="response.amounts?.['{{ $item['id'] }}'] ?? 0"></span>
                        </div>
                        <input type="range" min="0" max="{{ $budget }}" step="5" x-model.number="response.amounts['{{ $item['id'] }}']" class="mt-3 w-full accent-[#2D5BFF]" aria-label="{{ $item['label'] }}">
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <p class="mt-3 text-[11px] leading-relaxed text-muted">Можно не тратить всё. Если сумма больше бюджета — сервер приведёт её к бюджету.</p>
</div>

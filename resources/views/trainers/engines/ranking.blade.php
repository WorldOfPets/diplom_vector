@php
    $items = collect($config['items'] ?? []);
    $order = $saved['order'] ?? [];
    if (! empty($order)) {
        $items = $items->sortBy(function ($item) use ($order) {
            $index = array_search($item['id'], $order, true);

            return $index === false ? 999 : $index;
        })->values();
    }
@endphp

<div class="card p-5 sm:p-6">
    <div class="flex items-start gap-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-brand-soft text-brand-deep"><x-icon name="grid" class="h-4 w-4" /></span>
        <div>
            <p class="text-sm font-bold">Расставьте по порядку</p>
            <p class="mt-1 text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Перетащите карточки: сверху — то, что для вас важнее всего.' }}</p>
        </div>
    </div>

    <ul x-data x-init="new Sortable($el, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'opacity-40',
            onEnd: () => {
                Array.from($el.children).forEach((li, i) => { const badge = li.querySelector('[data-rank]'); if (badge) badge.textContent = i + 1; });
                $dispatch('response-patch', { order: Array.from($el.children).map((li) => li.dataset.id) });
            }
        });
        $dispatch('response-patch', { order: Array.from($el.children).map((li) => li.dataset.id) });" class="mt-5 space-y-2.5">
        @foreach($items as $item)
            <li data-id="{{ $item['id'] }}" class="flex items-center gap-3 rounded-2xl border border-line bg-surface p-3">
                <span data-rank class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-paper-deep text-xs font-extrabold text-ink-soft">{{ $loop->iteration }}</span>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-paper text-lg">{{ $item['emoji'] ?? '🌳' }}</span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold">{{ $item['label'] }}</p>
                    @if(! empty($item['description']))
                        <p class="mt-0.5 text-xs leading-relaxed text-muted">{{ $item['description'] }}</p>
                    @endif
                </div>
                <button type="button" class="drag-handle grid h-9 w-8 cursor-grab place-items-center rounded-xl text-muted hover:bg-paper active:cursor-grabbing" aria-label="Перетащить">
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true"><circle cx="7" cy="4" r="1.4"/><circle cx="13" cy="4" r="1.4"/><circle cx="7" cy="10" r="1.4"/><circle cx="13" cy="10" r="1.4"/><circle cx="7" cy="16" r="1.4"/><circle cx="13" cy="16" r="1.4"/></svg>
                </button>
            </li>
        @endforeach
    </ul>

    <p class="mt-3 text-[11px] leading-relaxed text-muted">Подсказка: нет «правильного» порядка. Важен ваш выбор, а не скорость.</p>
</div>

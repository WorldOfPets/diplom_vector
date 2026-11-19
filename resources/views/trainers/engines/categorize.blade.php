@php
    $buckets = $config['buckets'] ?? [];
    $items = collect($config['items'] ?? []);
    $assigned = is_array($saved['buckets'] ?? null) ? $saved['buckets'] : [];
    $bucketIds = collect($buckets)->pluck('id')->all();

    $groups = [];
    foreach ($buckets as $bucket) {
        $groups[$bucket['id']] = $items
            ->filter(fn (array $item): bool => ($assigned[$item['id']] ?? null) === $bucket['id'])
            ->values();
    }

    $pool = $items
        ->filter(fn (array $item): bool => ! in_array($assigned[$item['id']] ?? null, $bucketIds, true))
        ->values();

    $counts = collect($buckets)
        ->mapWithKeys(fn (array $bucket): array => [$bucket['id'] => $groups[$bucket['id']]->count()])
        ->all();
@endphp

<div class="card p-5 sm:p-6"
     x-data="categorizeBoard({ buckets: @js($buckets), counts: @js($counts), poolCount: {{ $pool->count() }} })">
    <div class="flex items-start gap-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-lilac-soft text-lilac"><x-icon name="grid" class="h-4 w-4" /></span>
        <div>
            <p class="text-sm font-bold">{{ $config['title'] ?? 'Разложите по категориям' }}</p>
            <p class="mt-1 text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Перетащите карточки или нажмите на карточку, а затем на категорию.' }}</p>
        </div>
    </div>

    <div class="mt-5 rounded-2xl border border-dashed border-line bg-paper/50 p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] font-bold text-muted">
            <span>Карточки</span>
            <span class="flex items-center gap-2">
                <button type="button" x-show="activeItem" x-cloak @click="returnToPool(activeItem)" class="rounded-full border border-line bg-surface px-2.5 py-1 text-[10px] font-extrabold text-muted transition hover:border-brand/30">
                    Вернуть в карточки
                </button>
                <span x-text="activeItem ? 'Теперь выберите категорию' : 'Перетащите или нажмите на карточку'"></span>
            </span>
        </div>

        <div x-ref="pool" data-bucket="" class="mt-3 grid gap-2 sm:grid-cols-2">
            @foreach($pool as $item)
                <button type="button"
                        data-cat-item="{{ $item['id'] }}"
                        @click="select(@js($item['id']))"
                        class="flex items-center gap-3 rounded-2xl border bg-surface p-3 text-left transition"
                        :class="activeItem === @js($item['id']) ? 'border-brand ring-2 ring-brand/20' : 'border-line hover:border-brand/30'">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-paper text-base">{{ $item['emoji'] ?? '•' }}</span>
                    <span class="flex-1 text-sm font-semibold leading-snug">{{ $item['label'] }}</span>
                    <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-muted" />
                </button>
            @endforeach

            <p x-show="poolCount === 0" x-cloak class="rounded-xl bg-surface/70 px-3 py-4 text-xs font-semibold text-muted sm:col-span-2">Все карточки разложены. Можно менять решения в любой момент.</p>
        </div>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-3">
        @foreach($buckets as $bucket)
            <div class="flex flex-col rounded-2xl border bg-surface transition"
                 :class="activeItem ? 'border-brand/30' : 'border-line'">
                <div class="flex items-center justify-between gap-2 border-b border-line/70 px-3.5 py-2.5">
                    <span class="flex items-center gap-2 text-[11px] font-extrabold text-ink-soft">
                        <span class="h-1.5 w-1.5 rounded-full" style="background: {{ \App\Support\Color::safe($bucket['color'] ?? null) }};"></span>
                        {{ $bucket['label'] }}
                    </span>
                    <span class="text-sm font-extrabold" x-text="counts[@js($bucket['id'])] ?? 0"></span>
                </div>

                <button type="button"
                        @click="place(@js($bucket['id']))"
                        x-show="activeItem"
                        x-cloak
                        class="mx-2.5 mt-2.5 rounded-xl border border-brand/30 bg-brand-soft px-2 py-1.5 text-[10px] font-extrabold text-brand-deep">
                    Положить сюда
                </button>

                <div data-bucket="{{ $bucket['id'] }}"
                     class="min-h-24 flex-1 space-y-2 p-2.5">
                    @foreach($groups[$bucket['id']] as $item)
                        <button type="button"
                                data-cat-item="{{ $item['id'] }}"
                                @click="select(@js($item['id']))"
                                class="flex w-full items-center gap-2 rounded-xl border border-line bg-paper/80 p-2.5 text-left transition"
                                :class="activeItem === @js($item['id']) ? 'border-brand ring-2 ring-brand/20' : 'hover:border-brand/30'">
                            <span class="text-base">{{ $item['emoji'] ?? '•' }}</span>
                            <span class="flex-1 text-xs font-semibold leading-snug">{{ $item['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <p class="mt-3 text-[11px] leading-relaxed text-muted">Перетащите карточку в категорию или нажмите на неё и выберите категорию. Категории — не ярлыки: решение можно изменить, а «не моё» означает «пока не моя стратегия».</p>
</div>

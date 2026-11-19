@php
    $items = $config['items'] ?? [];
    $budget = max(1, (int) ($config['budget'] ?? 100));
    $coinValue = 5;
    $coinCount = intdiv($budget, $coinValue);
    $savedAmounts = is_array($saved['amounts'] ?? null) ? $saved['amounts'] : [];

    $counts = [];
    $placed = 0;

    foreach ($items as $item) {
        $amount = max(0, (int) ($savedAmounts[$item['id']] ?? 0));
        $count = min(max(0, $coinCount - $placed), intdiv($amount, $coinValue));
        $counts[$item['id']] = $count;
        $placed += $count;
    }

    $purseCount = max(0, $coinCount - $placed);
    $amounts = collect($items)
        ->mapWithKeys(fn (array $item): array => [$item['id'] => (int) ($savedAmounts[$item['id']] ?? 0)])
        ->all();
@endphp

<div class="card p-5 sm:p-6"
     x-data="coinJar({ budget: {{ $budget }}, coinValue: {{ $coinValue }}, items: @js($items), amounts: @js($amounts) })">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-amber-soft text-amber-ink"><x-icon name="sparkles" class="h-4 w-4" /></span>
            <div>
                <p class="text-sm font-bold">Распределите монеты</p>
                <p class="mt-1 max-w-lg text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Перетащите монеты в то, что для вас важнее. Можно не тратить всё.' }}</p>
            </div>
        </div>
        <div class="rounded-2xl bg-paper px-4 py-2 text-right">
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted">Осталось</p>
            <p class="text-xl font-extrabold" :class="remainingAmount === 0 ? 'text-mint-ink' : 'text-ink'" x-text="remainingAmount"></p>
        </div>
    </div>

    <div class="mt-4 h-2 overflow-hidden rounded-full bg-line">
        <div class="h-full rounded-full bg-amber transition-all" :style="`width: ${Math.min(100, Math.round((spentAmount / {{ $budget }}) * 100))}%`"></div>
    </div>
    <div class="mt-1.5 flex items-center justify-between text-[10px] font-semibold text-muted">
        <span x-text="`Распределено: ${spentAmount} из {{ $budget }}`"></span>
        <button type="button" @click="reset()" class="font-bold underline underline-offset-2 transition hover:text-ink" x-show="spentAmount > 0" x-cloak>Вернуть все монеты</button>
    </div>

    <div class="mt-5 rounded-2xl border border-dashed border-amber/30 bg-amber-soft/20 p-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="text-[11px] font-extrabold text-amber-ink">Кошелёк</span>
            <span class="text-[10px] font-bold text-muted">Перетащите монету в карточку или нажмите +5</span>
        </div>
        <div x-ref="purse" data-coin-purse class="mt-3 flex min-h-12 flex-wrap gap-1.5 rounded-xl bg-paper/60 p-2">
            @for($i = 0; $i < $purseCount; $i++)
                <button type="button" data-coin class="grid h-8 w-8 cursor-grab place-items-center rounded-full border border-amber/40 bg-amber-soft text-[10px] font-extrabold text-amber-ink shadow-sm active:cursor-grabbing" aria-label="Монета {{ $coinValue }}">
                    {{ $coinValue }}
                </button>
            @endfor

            <p x-show="remainingAmount === 0" x-cloak class="self-center px-2 text-[11px] font-semibold text-muted">Все монеты распределены.</p>
        </div>
    </div>

    <div class="mt-4 space-y-3">
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
                            <span class="rounded-xl bg-paper px-2.5 py-1 text-xs font-extrabold text-ink" x-text="amounts[@js($item['id'])] ?? 0"></span>
                        </div>

                        <div data-coin-target="{{ $item['id'] }}" class="mt-3 flex min-h-12 flex-wrap items-center gap-1.5 rounded-xl bg-paper/70 p-2">
                            @for($i = 0; $i < $counts[$item['id']]; $i++)
                                <button type="button" data-coin class="grid h-7 w-7 cursor-grab place-items-center rounded-full border border-amber/40 bg-amber-soft text-[9px] font-extrabold text-amber-ink shadow-sm active:cursor-grabbing" aria-label="Монета {{ $coinValue }}">
                                    {{ $coinValue }}
                                </button>
                            @endfor
                            <span class="px-1 text-[10px] font-semibold text-muted" x-show="(amounts[@js($item['id'])] ?? 0) === 0">Пока пусто</span>
                        </div>

                        <div class="mt-2 flex items-center gap-2">
                            <button type="button" @click="remove(@js($item['id']))"
                                    class="rounded-full border border-line px-3 py-1.5 text-[10px] font-extrabold text-muted transition hover:bg-paper disabled:opacity-40"
                                    :disabled="(amounts[@js($item['id'])] ?? 0) <= 0">
                                −{{ $coinValue }}
                            </button>
                            <button type="button" @click="add(@js($item['id']))"
                                    class="rounded-full border border-line px-3 py-1.5 text-[10px] font-extrabold text-muted transition hover:bg-paper disabled:opacity-40"
                                    :disabled="remainingAmount < {{ $coinValue }}">
                                +{{ $coinValue }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <p class="mt-3 text-[11px] leading-relaxed text-muted">Монеты можно перетаскивать между карточками и кошельком. Ограничение ресурса помогает увидеть реальную иерархию мотивов.</p>
</div>

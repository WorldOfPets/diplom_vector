@php
    $pattern = $config['pattern'] ?? [
        ['phase' => 'Вдох', 'seconds' => 4],
        ['phase' => 'Задержка', 'seconds' => 2],
        ['phase' => 'Выдох', 'seconds' => 6],
    ];
    $cycles = (int) ($config['cycles'] ?? 4);
@endphp

<div class="card p-5 sm:p-6"
     x-data="breathing({
        pattern: @js($pattern),
        cycles: {{ $cycles }},
        before: {{ (int) ($saved['before'] ?? 6) }},
        after: {{ (int) ($saved['after'] ?? 6) }}
     })">
    <div class="flex items-start gap-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-mint-soft text-mint-ink"><x-icon name="heart" class="h-4 w-4" /></span>
        <div>
            <p class="text-sm font-bold">Дыхание {{ $cycles }} циклов</p>
            <p class="mt-1 text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Дышите по схеме. Не задерживайте дыхание через силу: если тяжело — вернитесь к обычному ритму.' }}</p>
        </div>
    </div>

    <div class="mt-6 flex flex-col items-center">
        <div class="relative grid h-44 w-44 place-items-center">
            <div class="absolute inset-0 rounded-full bg-mint-soft"></div>
            <div class="absolute inset-4 rounded-full bg-mint/10"></div>
            <div class="relative grid h-28 w-28 place-items-center rounded-full bg-surface shadow-soft transition-transform duration-1000"
                 :style="'transform: scale(' + (phase?.phase === 'Вдох' ? 1.14 : phase?.phase === 'Выдох' ? 0.84 : 1) + ')'">
                <div class="text-center">
                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted" x-text="running ? (phase?.phase ?? '') : (finished ? 'Готово' : 'Готовы?')"></p>
                    <p class="text-2xl font-extrabold text-ink" x-text="running ? remaining : '—'"></p>
                </div>
            </div>
        </div>

        <div class="mt-3 flex items-center gap-2 text-[11px] font-bold text-muted">
            <span x-text="running ? ('Цикл ' + Math.min(cycle + 1, cycles) + ' из ' + cycles) : (finished ? 'Практика завершена' : 'Нажмите «Начать»')"></span>
        </div>

        <div class="mt-4 flex gap-2">
            <button type="button" @click="start()" class="btn-primary px-5 py-2.5 text-xs" :disabled="running || finished">Начать</button>
            <button type="button" @click="stop(false)" class="btn-secondary px-5 py-2.5 text-xs" x-show="running" x-cloak>Остановить</button>
            <button type="button" @click="finished = false; cycle = 0; phaseIndex = 0" class="btn-secondary px-5 py-2.5 text-xs" x-show="finished" x-cloak>Ещё раз</button>
        </div>
    </div>

    <div class="mt-6 rounded-2xl bg-paper/70 p-4">
        <div class="flex items-center justify-between text-xs font-bold">
            <span>Напряжение до</span>
            <span class="text-coral" x-text="before"></span>
        </div>
        <input type="range" min="0" max="10" x-model.number="before" @input="updateAfter()" class="mt-2 w-full accent-[#FF6B4A]" aria-label="Напряжение до">
    </div>

    <div class="mt-3 rounded-2xl bg-mint-soft/60 p-4" x-show="finished" x-cloak>
        <div class="flex items-center justify-between text-xs font-bold">
            <span>Напряжение после</span>
            <span class="text-mint-ink" x-text="after"></span>
        </div>
        <input type="range" min="0" max="10" x-model.number="after" @input="updateAfter()" class="mt-2 w-full accent-[#2BB673]" aria-label="Напряжение после">
        <p class="mt-2 text-[10px] leading-relaxed text-muted">Если напряжение снизилось хотя бы на 1 балл — это уже результат.</p>
    </div>
</div>

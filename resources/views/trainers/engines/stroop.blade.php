@php
    $items = $config['items'] ?? [];
    $options = $config['options'] ?? [];
    $timeLimit = (int) ($config['time_limit_ms'] ?? 2500);
@endphp

<div class="card overflow-hidden p-0"
     x-data="stroopGame({ items: @js($items), options: @js($options), timeLimit: {{ $timeLimit }} })">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line/70 bg-surface/60 px-4 py-3.5 sm:px-5">
        <div class="flex items-start gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-lilac-soft text-lilac"><x-icon name="target" class="h-4 w-4" /></span>
            <div>
                <p class="text-sm font-bold">{{ $config['title'] ?? 'Эмоциональный Струп' }}</p>
                <p class="mt-1 max-w-lg text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Выберите эмоцию, которую показывает лицо. Слово может противоречить — игнорируйте его.' }}</p>
            </div>
        </div>
        <span class="chip" x-text="`${index} / ${items.length}`"></span>
    </div>

    <div class="p-4 sm:p-5">
        <div class="h-1.5 overflow-hidden rounded-full bg-line">
            <div class="h-full rounded-full bg-lilac transition-all" :style="`width: ${progress}%`"></div>
        </div>

        <div x-show="!running && !finished">
            <div class="mt-5 rounded-3xl border border-line bg-paper/60 p-6 text-center">
                <p class="text-sm font-bold">Смотрите на лицо, а не на слово</p>
                <p class="mt-1 text-xs text-muted">{{ count($items) }} раундов · {{ number_format($timeLimit / 1000, 1, ',', ' ') }} с на ответ</p>
                <p class="mx-auto mt-2 max-w-md text-[11px] leading-relaxed text-muted">Слово на экране может называть другую эмоцию. Это и есть упражнение на торможение помехи.</p>
                <button type="button" @click="start()" class="btn-primary mt-4 px-6 py-2.5 text-xs">Начать</button>
            </div>
        </div>

        <div x-show="running" x-cloak>
            <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-line">
                <div class="h-full rounded-full transition-all"
                     :class="remainingPercent < 30 ? 'bg-rose' : 'bg-mint'"
                     :style="`width: ${remainingPercent}%`"></div>
            </div>

            <div class="mt-4 rounded-3xl border border-line bg-paper/60 p-5 text-center">
                <p class="text-7xl leading-none" x-text="current?.face ?? '🙂'"></p>
                <p class="mt-4 inline-block rounded-full bg-surface px-4 py-1.5 text-sm font-extrabold tracking-[0.18em] text-muted" x-text="current?.word ?? ''"></p>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
                <template x-for="option in choices" :key="option.id">
                    <button type="button"
                            @click="answer(option.id)"
                            :disabled="chosen !== null"
                            class="rounded-2xl border border-line bg-surface px-3 py-3 text-xs font-extrabold text-ink-soft transition hover:border-brand/30 disabled:opacity-50">
                        <span x-text="option.label"></span>
                    </button>
                </template>
            </div>

            <p class="mt-3 text-center text-[10px] font-semibold text-muted" x-show="chosen !== null" x-cloak>Принято. Следующее лицо…</p>
        </div>

        <div x-show="finished" x-cloak>
            <div class="mt-4 rounded-3xl border border-mint/20 bg-mint-soft/50 p-5 text-center">
                <p class="text-sm font-bold text-mint-ink">Раунды завершены</p>
                <p class="mt-1 text-xs leading-relaxed text-mint-ink">Нажмите «Завершить упражнение» ниже — результат посчитается на сервере.</p>
                <button type="button" @click="start()" class="btn-secondary mt-4 px-5 py-2.5 text-xs">Ещё раз</button>
            </div>
        </div>

        <p class="mt-3 text-[10px] leading-relaxed text-muted">Это упражнение на внимание и распознавание, а не диагноз. Слово-дистрактор — часть тренировки.</p>
    </div>
</div>

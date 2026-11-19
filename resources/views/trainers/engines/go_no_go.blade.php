@php
    $trials = (int) ($config['trials'] ?? 30);
    $window = (int) ($config['response_window_ms'] ?? 1100);
    $flipAfter = (int) ($config['rule_flip_after'] ?? 0);
@endphp

<div class="card overflow-hidden p-0"
     x-data="goNoGo({
        trials: {{ $trials }},
        go_ratio: {{ (float) ($config['go_ratio'] ?? 0.7) }},
        response_window_ms: {{ $window }},
        min_window_ms: {{ (int) ($config['min_window_ms'] ?? 650) }},
        window_step_ms: {{ (int) ($config['window_step_ms'] ?? 60) }},
        adaptive: {{ ($config['adaptive'] ?? false) ? 'true' : 'false' }},
        rule_flip_after: {{ $flipAfter }},
        distractors: @js($config['distractors'] ?? []),
        go_label: @js($config['go_label'] ?? 'Спокойно'),
        nogo_label: @js($config['nogo_label'] ?? 'Триггер'),
        go_hint: @js($config['go_hint'] ?? 'Нажмите'),
        nogo_hint: @js($config['nogo_hint'] ?? 'Не нажимайте')
     })">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line/70 bg-surface/60 px-4 py-3.5 sm:px-5">
        <div class="flex items-start gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-coral-soft text-coral"><x-icon name="target" class="h-4 w-4" /></span>
            <div>
                <p class="text-sm font-bold">{{ $config['title'] ?? 'Стоп-кран 2.0' }}</p>
                <p class="mt-1 max-w-lg text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Нажимайте на «Спокойно» и не нажимайте на «Триггер».' }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="chip" x-text="`${index} / ${total}`"></span>
            <span class="chip" x-text="`окно ${windowMs} мс`"></span>
            <span class="chip" x-show="combo > 1" x-cloak x-text="`комбо ${combo}`"></span>
        </div>
    </div>

    <div class="p-4 sm:p-5">
        <div class="h-1.5 overflow-hidden rounded-full bg-line">
            <div class="h-full rounded-full bg-coral transition-all" :style="`width: ${total ? Math.round((index / total) * 100) : 0}%`"></div>
        </div>

        <div x-show="!running && !finished">
            <div class="mt-5 rounded-3xl border border-line bg-paper/60 p-6 text-center">
                <p class="text-sm font-bold">Готовы?</p>
                <p class="mt-1 text-xs text-muted">{{ $trials }} проб · стартовое окно {{ $window }} мс · адаптивная сложность</p>
                @if($flipAfter > 0)
                    <div class="mx-auto mt-3 max-w-md rounded-2xl border border-amber/30 bg-amber-soft/50 p-3 text-left text-[11px] leading-relaxed text-amber-ink">
                        <p class="font-extrabold">Как играть</p>
                        <p class="mt-1">«{{ $config['go_label'] ?? 'Спокойно' }}» — нажмите. «{{ $config['nogo_label'] ?? 'Триггер' }}» — не нажимайте.</p>
                        <p class="mt-1">После {{ $flipAfter }}-й пробы правило меняется: нажимать нужно на «{{ $config['nogo_label'] ?? 'Триггер' }}», а «{{ $config['go_label'] ?? 'Спокойно' }}» — пропускать.</p>
                        <p class="mt-1">Под сигналом всегда есть подсказка «Нажмите» или «Не нажимайте» — ориентируйтесь на неё, а не на смысл слова.</p>
                    </div>
                @endif
                <button type="button" @click="start()" class="btn-primary mt-4 px-6 py-2.5 text-xs">Начать игру</button>
            </div>
        </div>

        <div x-show="running" x-cloak>
            <p class="mt-4 rounded-xl border border-line/70 bg-paper/70 px-3 py-1.5 text-center text-[11px] font-extrabold text-ink-soft" x-text="ruleText"></p>

            <div class="relative mt-3">
                <div x-show="announcement" x-cloak
                     class="absolute inset-0 z-10 grid place-items-center rounded-3xl border border-amber/40 bg-amber-soft px-5 text-center text-xs font-extrabold leading-relaxed text-amber-ink"
                     x-text="announcement"></div>

                <div class="grid min-h-44 place-items-center rounded-3xl border border-line bg-paper/60 p-5 transition"
                     :class="lastResult === 'ok' ? 'ring-4 ring-mint/20' : (lastResult === 'miss' ? 'ring-4 ring-rose/20' : '')">
                    <template x-if="showSignal">
                        <div class="flex flex-col items-center gap-2 text-center">
                            <span class="rounded-2xl px-8 py-5 text-2xl font-extrabold sm:text-3xl"
                                  :class="current === 'go' ? 'bg-mint-soft text-mint-ink' : 'bg-rose/10 text-rose'"
                                  x-text="current === 'go' ? goLabel : nogoLabel"></span>
                            <p class="text-[11px] font-semibold text-muted" x-text="current === 'go' ? goHint : nogoHint"></p>
                            <p class="mt-1 min-h-4 text-xs font-semibold tracking-wide text-muted/40" x-text="distractor"></p>
                        </div>
                    </template>
                    <template x-if="!showSignal">
                        <p class="text-xs font-semibold text-muted">Приготовьтесь…</p>
                    </template>
                </div>
            </div>

            <button type="button" @click="react()" class="btn-secondary mt-3 w-full py-3 text-sm">Нажать</button>
            <p class="mt-2 text-center text-[10px] font-semibold text-muted">Можно нажимать пробел. Отвлекающие слова игнорируйте.</p>
        </div>

        <div x-show="finished" x-cloak>
            <div class="mt-4 rounded-3xl border border-mint/20 bg-mint-soft/50 p-5">
                <p class="text-sm font-bold text-mint-ink">Игра завершена</p>
                <p class="mt-1 text-xs leading-relaxed text-mint-ink">Результат ниже. Нажмите «Завершить упражнение», чтобы сохранить его.</p>

                <div class="mt-4 grid grid-cols-2 gap-2 text-center sm:grid-cols-4">
                    <div class="rounded-2xl bg-surface/70 p-3">
                        <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-muted">Точность</p>
                        <p class="mt-1 text-xl font-extrabold text-ink" x-text="`${accuracy}%`"></p>
                    </div>
                    <div class="rounded-2xl bg-surface/70 p-3">
                        <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-muted">Макс. комбо</p>
                        <p class="mt-1 text-xl font-extrabold text-ink" x-text="maxCombo"></p>
                    </div>
                    <div class="rounded-2xl bg-surface/70 p-3">
                        <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-muted">Ложные</p>
                        <p class="mt-1 text-xl font-extrabold text-rose" x-text="falseAlarms"></p>
                    </div>
                    <div class="rounded-2xl bg-surface/70 p-3">
                        <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-muted">Пропуски</p>
                        <p class="mt-1 text-xl font-extrabold text-amber-ink" x-text="misses"></p>
                    </div>
                </div>

                @if($flipAfter > 0)
                    <div class="mt-3 grid grid-cols-2 gap-2 text-center">
                        <div class="rounded-2xl bg-surface/70 px-3 py-2">
                            <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-muted">До смены правил</p>
                            <p class="mt-1 text-base font-extrabold text-ink" x-text="accuracyBefore === null ? '—' : `${accuracyBefore}%`"></p>
                        </div>
                        <div class="rounded-2xl bg-surface/70 px-3 py-2">
                            <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-muted">После смены правил</p>
                            <p class="mt-1 text-base font-extrabold text-ink" x-text="accuracyAfter === null ? '—' : `${accuracyAfter}%`"></p>
                        </div>
                    </div>

                    <p class="mt-3 rounded-2xl border border-amber/30 bg-amber-soft/50 px-3 py-2 text-[11px] leading-relaxed text-amber-ink"
                       x-show="accuracyBefore !== null && accuracyAfter !== null && accuracyAfter + 15 < accuracyBefore" x-cloak>
                        Во второй половине срабатывала старая привычка: рука тянулась нажимать раньше, чем вы читали подсказку. Это нормально для первого раза — навык переключения тренируется повтором.
                    </p>

                    <p class="mt-3 rounded-2xl bg-surface/70 px-3 py-2 text-[11px] leading-relaxed text-muted"
                       x-show="accuracyBefore !== null && accuracyAfter !== null && accuracyAfter + 15 >= accuracyBefore" x-cloak>
                        Вы удержали правило после смены — это и есть переключение, а не только торможение.
                    </p>
                @endif

                <p class="mt-3 text-center text-[11px] font-semibold text-muted" x-show="meanRt" x-cloak>
                    Средняя реакция: <span class="font-extrabold text-ink" x-text="meanRt"></span> мс
                </p>

                <button type="button" @click="start()" class="btn-secondary mt-4 px-5 py-2.5 text-xs">Ещё раз</button>
            </div>
        </div>

        <p class="mt-3 text-[10px] leading-relaxed text-muted">Это не тест на «силу воли» и не диагноз. Результат зависит от усталости, внимания и случайности. Смотрите на динамику.</p>
    </div>
</div>

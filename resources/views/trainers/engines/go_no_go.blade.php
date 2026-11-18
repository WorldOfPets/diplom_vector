@php
    $trials = (int) ($config['trials'] ?? 30);
    $goRatio = (float) ($config['go_ratio'] ?? 0.7);
    $window = (int) ($config['response_window_ms'] ?? 1100);
@endphp

<div class="card overflow-hidden p-5 sm:p-6"
     x-data="goNoGo({
        trials: {{ $trials }},
        go_ratio: {{ $goRatio }},
        response_window_ms: {{ $window }},
        go_label: @js($config['go_label'] ?? 'Спокойно'),
        nogo_label: @js($config['nogo_label'] ?? 'Триггер'),
        go_hint: @js($config['go_hint'] ?? 'Нажмите'),
        nogo_hint: @js($config['nogo_hint'] ?? 'Не нажимайте')
     })">
    <div class="flex items-start gap-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-coral-soft text-coral"><x-icon name="target" class="h-4 w-4" /></span>
        <div>
            <p class="text-sm font-bold">{{ $config['title'] ?? 'Игра «Стоп-кран»' }}</p>
            <p class="mt-1 text-xs leading-relaxed text-muted">
                {{ $config['prompt'] ?? 'Нажимайте, когда видите «Спокойно». Не нажимайте на «Триггер». Это тренировка торможения импульса.' }}
            </p>
        </div>
    </div>

    <div class="mt-5 rounded-3xl border border-line bg-paper/60 p-6 text-center">
        <template x-if="!running && !finished">
            <div>
                <p class="text-sm font-bold">Готовы?</p>
                <p class="mt-1 text-xs text-muted">{{ $trials }} проб · окно реакции {{ $window }} мс</p>
                <button type="button" @click="start()" class="btn-primary mt-4 px-6 py-2.5 text-xs">Начать игру</button>
            </div>
        </template>

        <template x-if="running">
            <div>
                <div class="flex items-center justify-center gap-2 text-[11px] font-bold text-muted">
                    <span x-text="'Проба ' + index + ' из ' + total"></span>
                </div>

                <div class="mt-4 grid min-h-32 place-items-center">
                    <template x-if="showSignal">
                        <div class="flex flex-col items-center gap-2">
                            <span class="rounded-2xl px-6 py-4 text-xl font-extrabold"
                                  :class="current === 'go' ? 'bg-mint-soft text-mint-ink' : 'bg-rose/10 text-rose'"
                                  x-text="current === 'go' ? goLabel : nogoLabel"></span>
                            <p class="text-[11px] font-semibold text-muted" x-text="current === 'go' ? goHint : nogoHint"></p>
                        </div>
                    </template>
                    <template x-if="!showSignal">
                        <p class="text-xs font-semibold text-muted">Приготовьтесь…</p>
                    </template>
                </div>

                <button type="button" @click="react()" class="btn-secondary mt-2 px-6 py-2.5 text-xs">Нажать</button>
                <p class="mt-2 text-[10px] font-semibold text-muted">На компьютере можно нажать пробел</p>
            </div>
        </template>

        <template x-if="finished">
            <div>
                <p class="text-sm font-bold">Игра завершена</p>
                <p class="mt-1 text-xs text-muted">Нажмите «Завершить упражнение» ниже, чтобы сохранить результат.</p>
                <div class="mt-3 flex justify-center gap-2">
                    <span class="chip">Проб: <span class="font-extrabold" x-text="trials.length"></span></span>
                    <span class="chip">Точность: <span class="font-extrabold" x-text="Math.round((trials.filter(t => (t.type === 'go' && t.reacted) || (t.type === 'nogo' && !t.reacted)).length / Math.max(trials.length, 1)) * 100) + '%'"></span></span>
                </div>
                <button type="button" @click="start()" class="btn-secondary mt-4 px-5 py-2.5 text-xs">Ещё раз</button>
            </div>
        </template>
    </div>

    <p class="mt-3 text-[11px] leading-relaxed text-muted">Это не тест на «силу воли». Результат зависит от усталости, внимания и случайности. Смотрите на динамику.</p>
</div>

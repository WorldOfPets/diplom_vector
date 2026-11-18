@php
    $start = $config['start'] ?? null;
    $nodes = $config['nodes'] ?? [];
@endphp

@if($start === null || $nodes === [])
    <x-empty-state icon="warning" title="Сценарий не настроен" text="В контенте тренажёра нет узлов сценария." />
@else
    <div class="card p-5 sm:p-6" x-data="scenario(@js($config))">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-lilac-soft text-lilac"><x-icon name="chevron-right" class="h-4 w-4" /></span>
                <div>
                    <p class="text-sm font-bold">Развилка</p>
                    <p class="mt-1 text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Выбирайте реакцию — сценарий будет меняться.' }}</p>
                </div>
            </div>
            <span class="chip" x-text="'шаг ' + (choices.length + 1)"></span>
        </div>

        <div class="mt-5" x-show="!finished">
            <div class="rounded-2xl bg-paper/70 p-4 text-sm leading-relaxed text-ink-soft" x-text="node?.text ?? ''"></div>

            <div x-show="feedback" x-cloak class="mt-3 rounded-2xl border border-brand/20 bg-brand-soft/40 px-4 py-3 text-xs leading-relaxed text-brand-deep" x-text="feedback"></div>

            <div class="mt-4 space-y-2">
                <template x-for="(option, index) in (node?.options ?? [])" :key="index">
                    <button type="button" @click="choose(index)" class="w-full rounded-2xl border border-line bg-surface p-4 text-left text-sm leading-relaxed transition hover:border-brand/30 hover:bg-brand-soft/30">
                        <span class="mr-2 inline-grid h-5 w-5 place-items-center rounded-full bg-paper-deep text-[10px] font-extrabold text-ink-soft" x-text="index + 1"></span>
                        <span x-text="option.label"></span>
                    </button>
                </template>
            </div>
        </div>

        <div class="mt-5" x-show="finished" x-cloak>
            <div class="rounded-2xl border border-mint/20 bg-mint-soft/50 p-4 text-sm leading-relaxed text-mint-ink" x-text="outcome ?? 'Сценарий завершён.'"></div>
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" @click="restart()" class="btn-secondary px-4 py-2 text-xs">Пройти заново</button>
                <span class="chip">Выборов: <span class="font-extrabold" x-text="choices.length"></span></span>
            </div>
            <p class="mt-3 text-[11px] leading-relaxed text-muted">Сравните свой путь с альтернативами: что вы сохранили, а что потеряли? Нажмите «Завершить упражнение» ниже.</p>
        </div>
    </div>
@endif

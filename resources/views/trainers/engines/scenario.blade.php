@php
    $start = $config['start'] ?? null;
    $nodes = $config['nodes'] ?? [];
@endphp

@if($start === null || $nodes === [])
    <x-empty-state icon="warning" title="Сценарий не настроен" text="В контенте тренажёра нет узлов сценария." />
@else
    <div class="card overflow-hidden p-0" x-data="scenario(@js($config))">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line/70 bg-surface/60 px-4 py-3.5 sm:px-5">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-full bg-lilac-soft text-xl" x-text="avatar"></span>
                <div>
                    <p class="text-sm font-extrabold" x-text="character"></p>
                    <p class="text-[10px] font-semibold text-muted" x-text="finished ? 'диалог завершён' : 'говорит с вами'"></p>
                </div>
            </div>

            <div class="text-right" x-show="tensionEnabled" x-cloak>
                <p class="text-[9px] font-bold uppercase tracking-[0.14em] text-muted" x-text="tensionLabel"></p>
                <div class="mt-1 flex items-center gap-2">
                    <div class="h-1.5 w-24 overflow-hidden rounded-full bg-line">
                        <div class="h-full rounded-full transition-all" :style="`width: ${tension}%; background-color: ${tensionColor}`"></div>
                    </div>
                    <span class="text-[10px] font-extrabold tabular-nums text-ink-soft" x-text="tension"></span>
                </div>
            </div>
        </div>

        <div x-ref="chat"
             x-effect="messages.length; $nextTick(() => { if ($refs.chat) $refs.chat.scrollTop = $refs.chat.scrollHeight })"
             class="max-h-[26rem] space-y-3 overflow-y-auto bg-paper/40 px-4 py-4 sm:px-5">
            <template x-for="(message, index) in messages" :key="index">
                <div class="flex" :class="message.side === 'me' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[86%] rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed"
                         :class="message.side === 'me'
                            ? 'rounded-br-md bg-brand text-white'
                            : 'rounded-bl-md border border-line bg-surface text-ink-soft'">
                        <p x-text="message.text"></p>
                    </div>
                </div>
            </template>

            <p x-show="feedback" x-cloak class="rounded-xl bg-brand-soft/50 px-3 py-2 text-[11px] font-semibold leading-relaxed text-brand-deep" x-text="feedback"></p>
        </div>

        <div class="border-t border-line/70 p-4 sm:p-5">
            <div x-show="!finished">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted">Ваш ответ</p>
                <div class="mt-2 space-y-2">
                    <template x-for="(option, index) in (node?.options ?? [])" :key="index">
                        <button type="button" @click="choose(index)"
                                class="flex w-full items-start gap-3 rounded-2xl border border-line bg-surface p-3.5 text-left text-sm leading-relaxed transition hover:border-brand/30 hover:bg-brand-soft/30">
                            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-paper-deep text-[10px] font-extrabold text-ink-soft" x-text="index + 1"></span>
                            <span x-text="option.label"></span>
                        </button>
                    </template>
                </div>
                <p class="mt-3 text-[10px] font-semibold text-muted">Выбирайте то, что сделали бы вы, а не «правильный» ответ.</p>
            </div>

            <div x-show="finished" x-cloak>
                <div class="rounded-2xl border border-mint/20 bg-mint-soft/50 p-4 text-sm leading-relaxed text-mint-ink" x-text="outcome ?? 'Диалог завершён.'"></div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button type="button" @click="restart()" class="btn-secondary px-4 py-2 text-xs">Пройти заново</button>
                    <span class="chip">Выборов: <span class="font-extrabold" x-text="choices.length"></span></span>
                    <span class="chip" x-show="tensionEnabled" x-cloak>Напряжение: <span class="font-extrabold" x-text="tension"></span></span>
                </div>
                <p class="mt-3 text-[11px] leading-relaxed text-muted">Сравните свой путь с альтернативами: что вы сохранили, а что потеряли? Нажмите «Завершить упражнение» ниже.</p>
            </div>
        </div>
    </div>
@endif

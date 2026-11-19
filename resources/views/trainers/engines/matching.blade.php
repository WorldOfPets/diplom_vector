@php
    $left = $config['left'] ?? [];
    $right = $config['right'] ?? [];
    $pairs = $saved['pairs'] ?? [];
@endphp

<div class="card p-5 sm:p-6"
     x-data="pairMatcher({ left: @js($left), right: @js($right), pairs: @js($pairs) })">
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-lilac-soft text-lilac"><x-icon name="grid" class="h-4 w-4" /></span>
            <div>
                <p class="text-sm font-bold">Сопоставьте</p>
                <p class="mt-1 text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Нажмите на описание, затем выберите подходящую эмоцию.' }}</p>
            </div>
        </div>
        <span class="chip shrink-0" x-text="`${assignedCount} / ${total}`"></span>
    </div>

    <div class="mt-5 space-y-2.5">
        @foreach($left as $item)
            <div class="rounded-2xl border p-3.5 transition"
                 :class="active === @js($item['id'])
                    ? 'border-brand bg-brand-soft/30 ring-2 ring-brand/20'
                    : (pairs[@js($item['id'])] ? 'border-mint/30 bg-mint-soft/20' : 'border-line bg-surface')">
                <button type="button"
                        @click="chooseLeft(@js($item['id']))"
                        class="flex w-full items-start gap-3 text-left"
                        :aria-pressed="active === @js($item['id'])"
                        aria-label="Выбрать описание: {{ $item['label'] }}">
                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-xl bg-paper text-[11px] font-extrabold text-ink-soft">{{ $loop->iteration }}</span>
                    <span class="flex-1 text-sm font-semibold leading-snug">{{ $item['label'] }}</span>
                    <x-icon name="chevron-right" class="mt-0.5 h-4 w-4 shrink-0 text-muted" />
                </button>

                <div class="mt-2 flex flex-wrap items-center gap-2" x-show="pairs[@js($item['id'])]" x-cloak>
                    <span class="chip border-mint/30 bg-mint-soft text-mint-ink" x-text="labelFor(pairs[@js($item['id'])])"></span>
                    <button type="button" @click="clear(@js($item['id']))" class="text-[10px] font-bold text-muted underline underline-offset-2 transition hover:text-rose">
                        сбросить
                    </button>
                </div>

                <div class="mt-3 flex flex-wrap gap-2 border-t border-line/60 pt-3" x-show="active === @js($item['id'])" x-cloak>
                    @foreach($right as $option)
                        <button type="button"
                                @click="assign(@js($option['id']))"
                                class="rounded-full border px-3 py-2 text-xs font-bold transition"
                                :class="isUsed(@js($option['id']))
                                    ? 'border-line bg-paper text-muted opacity-50'
                                    : 'border-line bg-surface text-ink-soft hover:border-brand/40 hover:bg-brand-soft/40'"
                                :disabled="isUsed(@js($option['id']))">
                            {{ $option['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-5 h-2 overflow-hidden rounded-full bg-line">
        <div class="h-full rounded-full bg-brand transition-all" :style="`width: ${total ? Math.round((assignedCount / total) * 100) : 0}%`"></div>
    </div>

    <p class="mt-3 text-[11px] leading-relaxed text-muted">
        Нажмите на описание, затем на подходящую эмоцию. Один вариант используется один раз — если он занят, сначала сбросьте другую пару.
    </p>
</div>

@extends('layouts.flow')

@section('title', $attempt->test->title)

@php
    $branchLabels = collect(\App\Enums\EiBranch::cases())->mapWithKeys(fn ($branch) => [$branch->value => $branch->label()])->all();
    if ($attempt->test->kind === 'motivation') {
        $branchLabels = $attempt->test->config['subscales'] ?? [
            'internal' => 'Внутренняя мотивация',
            'external' => 'Внешняя мотивация',
            'professional' => 'Профессиональная направленность',
        ];
    }
@endphp

@section('flow-header')
    <span class="chip">{{ $attempt->test->title }}</span>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl"
         x-data="testRunner({
            items: @js($items),
            initial: @js($savedAnswers),
            branchLabels: @js($branchLabels),
            autosaveUrl: '{{ route('tests.autosave', $attempt) }}',
            submitUrl: '{{ route('tests.submit', $attempt) }}',
            csrf: '{{ csrf_token() }}',
            timeLimit: {{ $timeLimit ?? 'null' }}
         })">

        <div class="flex flex-wrap items-center justify-between gap-2">
            <a href="{{ route('tests.show', $attempt->test) }}" @click="saveDraft()" class="text-xs font-bold text-muted transition hover:text-ink">← Выйти</a>
            <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold text-muted">
                <span x-show="saving" x-cloak class="text-brand">Сохраняем…</span>
                <span x-show="!saving" class="inline-flex items-center gap-1"><x-icon name="check" class="h-3 w-3 text-mint-ink" /> автосохранение</span>
                <span class="rounded-full bg-surface px-2.5 py-1 tabular-nums text-ink-soft" x-text="timeLimit ? ('осталось ' + remainingLabel) : ('время ' + elapsedLabel)"></span>
            </div>
        </div>

        <div class="mt-4 h-2 overflow-hidden rounded-full bg-line">
            <div class="h-full rounded-full bg-brand transition-all" :style="`width: ${progress}%`"></div>
        </div>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-[11px] font-semibold text-muted">
            <span class="flex items-center gap-3">
                <span x-text="`Задание ${index + 1} из ${items.length}`"></span>
                <button type="button" @click="navigatorOpen = !navigatorOpen" class="font-bold text-brand underline underline-offset-2" :aria-expanded="navigatorOpen">
                    Все задания
                </button>
            </span>
            <span x-text="`Отвечено: ${answeredCount} из ${items.length}`"></span>
        </div>

        <div x-show="navigatorOpen" x-cloak x-transition class="mt-3 rounded-2xl border border-line bg-surface p-3">
            <div class="grid grid-cols-8 gap-1.5 sm:grid-cols-10">
                <template x-for="(item, itemIndex) in items" :key="item.id">
                    <button type="button"
                            @click="goTo(itemIndex)"
                            class="grid h-8 place-items-center rounded-lg border text-[11px] font-extrabold transition"
                            :class="itemIndex === index
                                ? 'border-brand bg-brand text-white'
                                : (isAnswered(item) ? 'border-mint/30 bg-mint-soft text-mint-ink' : 'border-line bg-paper text-muted hover:border-brand/30')"
                            :aria-label="`Задание ${itemIndex + 1}`"
                            x-text="itemIndex + 1"></button>
                </template>
            </div>
            <p class="mt-2 text-[10px] font-semibold text-muted">С ответом — зелёные, текущее — синее. Клавиши ← → и 1–6 работают без мыши.</p>
        </div>

        <div class="card mt-5 p-5 sm:p-7">
            <div class="flex flex-wrap items-center gap-2">
                <span class="chip" x-text="branchLabel(current.branch)"></span>
                <span class="chip" x-text="typeLabel(current.type)"></span>
                <span class="chip" x-show="!isAnswered(current)" x-cloak>без ответа</span>
            </div>

            <h1 class="mt-4 text-xl font-extrabold leading-snug tracking-tight sm:text-2xl" x-text="current.prompt"></h1>

            <div class="mt-5">
                <template x-if="current.type === 'single'">
                    <div class="space-y-2">
                        <template x-for="(option, optionIndex) in (current.config.options || [])" :key="option.id">
                            <button type="button" @click="select(option.id)"
                                    class="flex w-full items-start gap-3 rounded-2xl border p-4 text-left text-sm leading-relaxed transition"
                                    :class="answers[current.id].value === option.id ? 'border-brand bg-brand-soft/40' : 'border-line bg-surface hover:border-brand/30'">
                                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full border text-[10px] font-extrabold"
                                      :class="answers[current.id].value === option.id ? 'border-brand bg-brand text-white' : 'border-line text-muted'"
                                      x-text="String.fromCharCode(65 + optionIndex)"></span>
                                <span x-text="option.label"></span>
                            </button>
                        </template>
                    </div>
                </template>

                <template x-if="current.type === 'multi'">
                    <div class="space-y-2">
                        <p class="mb-2 text-xs font-semibold text-muted" x-text="`Выберите до ${current.config.max || 3} вариантов`"></p>
                        <template x-for="option in (current.config.options || [])" :key="option.id">
                            <button type="button" @click="toggleArray('values', option.id, current.config.max || 3)"
                                    class="flex w-full items-start gap-3 rounded-2xl border p-4 text-left text-sm leading-relaxed transition"
                                    :class="(answers[current.id].values || []).includes(option.id) ? 'border-brand bg-brand-soft/40' : 'border-line bg-surface hover:border-brand/30'">
                                <span class="grid h-5 w-5 shrink-0 place-items-center rounded-md border"
                                      :class="(answers[current.id].values || []).includes(option.id) ? 'border-brand bg-brand text-white' : 'border-line'">
                                    <svg x-show="(answers[current.id].values || []).includes(option.id)" viewBox="0 0 20 20" class="h-3 w-3" fill="currentColor"><path d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.3 3.3 6.8-6.8a1 1 0 011.4 0z"/></svg>
                                </span>
                                <span x-text="option.label"></span>
                            </button>
                        </template>
                    </div>
                </template>

                <template x-if="current.type === 'scene' || current.type === 'image_choice'">
                    <div>
                        <p class="mb-2 text-xs font-semibold text-muted" x-text="`Выберите до ${current.config.max || 3} эмоций`"></p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="emotion in (current.config.emotions || [])" :key="emotion.id">
                                <button type="button" @click="toggleArray('emotions', emotion.id, current.config.max || 3)"
                                        class="rounded-full border px-3 py-2 text-xs font-bold transition"
                                        :class="(answers[current.id].emotions || []).includes(emotion.id) ? 'border-brand/30 bg-brand-soft text-brand-deep' : 'border-line bg-surface text-ink-soft hover:border-brand/20'"
                                        x-text="emotion.label"></button>
                            </template>
                        </div>

                        <div class="mt-5 rounded-2xl bg-paper/70 p-4">
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span>Насколько сильно, по вашему впечатлению?</span>
                                <span class="text-brand" x-text="answers[current.id].intensity || 3"></span>
                            </div>
                            <input type="range" min="1" max="5" x-model.number="answers[current.id].intensity" class="mt-2 w-full accent-[#2D5BFF]" aria-label="Интенсивность">
                            <div class="mt-1 flex justify-between text-[10px] font-semibold text-muted"><span>слабо</span><span>очень сильно</span></div>
                            <p class="mt-2 text-[10px] leading-relaxed text-muted">Это наблюдение для рефлексии. На балл влияет только выбор эмоций.</p>
                        </div>
                    </div>
                </template>

                <template x-if="current.type === 'best_worst'">
                    <div class="space-y-2">
                        <p class="mb-2 text-xs font-semibold text-muted">Отметьте лучшую и худшую реакцию</p>
                        <template x-for="option in (current.config.options || [])" :key="option.id">
                            <div class="rounded-2xl border border-line bg-surface p-4">
                                <p class="text-sm leading-relaxed" x-text="option.label"></p>
                                <div class="mt-2 flex gap-2">
                                    <button type="button" @click="setBest(option.id)"
                                            class="rounded-full border px-3 py-1.5 text-[10px] font-extrabold transition"
                                            :class="answers[current.id].best === option.id ? 'border-mint/30 bg-mint-soft text-mint-ink' : 'border-line text-muted hover:border-mint/30'">лучшая</button>
                                    <button type="button" @click="setWorst(option.id)"
                                            class="rounded-full border px-3 py-1.5 text-[10px] font-extrabold transition"
                                            :class="answers[current.id].worst === option.id ? 'border-rose/30 bg-rose/10 text-rose' : 'border-line text-muted hover:border-rose/30'">худшая</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="current.type === 'ordering'">
                    <div class="space-y-2">
                        <p class="mb-2 text-xs font-semibold text-muted">Расположите в правильном порядке</p>
                        <template x-for="(entry, entryIndex) in orderedItems(current)" :key="entry.id">
                            <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface p-3">
                                <span class="grid h-7 w-7 place-items-center rounded-xl bg-paper-deep text-[11px] font-extrabold text-ink-soft" x-text="entryIndex + 1"></span>
                                <span class="flex-1 text-sm leading-relaxed" x-text="entry.label"></span>
                                <div class="flex gap-1">
                                    <button type="button" @click="move(entryIndex, -1)" class="grid h-8 w-8 place-items-center rounded-xl border border-line text-muted hover:bg-paper" aria-label="Выше">↑</button>
                                    <button type="button" @click="move(entryIndex, 1)" class="grid h-8 w-8 place-items-center rounded-xl border border-line text-muted hover:bg-paper" aria-label="Ниже">↓</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="current.type === 'matching'">
                    <div class="space-y-2.5">
                        <template x-for="left in (current.config.left || [])" :key="left.id">
                            <div class="grid gap-2 rounded-2xl border border-line bg-surface p-3 sm:grid-cols-[1fr_1fr] sm:items-center sm:gap-4">
                                <p class="text-sm font-semibold leading-snug" x-text="left.label"></p>
                                <select class="input py-2.5 text-xs" x-model="answers[current.id].pairs[left.id]" :aria-label="left.label">
                                    <option value="">Выберите…</option>
                                    <template x-for="right in (current.config.right || [])" :key="right.id">
                                        <option :value="right.id" x-text="right.label"></option>
                                    </template>
                                </select>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="current.type === 'scale'">
                    <div class="rounded-2xl bg-paper/70 p-4">
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span x-text="current.config.min_label || 'Минимум'"></span>
                            <span class="text-brand" x-text="answers[current.id].value"></span>
                            <span x-text="current.config.max_label || 'Максимум'"></span>
                        </div>
                        <input type="range" :min="current.config.min || 1" :max="current.config.max || 5" x-model.number="answers[current.id].value" class="mt-2 w-full accent-[#2D5BFF]" aria-label="Шкала">
                    </div>
                </template>

                <template x-if="current.type === 'motivation'">
                    <div>
                        <p class="mb-2 text-xs font-semibold text-muted">Насколько это про вас?</p>
                        <div class="grid grid-cols-5 gap-2">
                            <template x-for="value in [1, 2, 3, 4, 5]" :key="value">
                                <button type="button" @click="select(value)"
                                        class="rounded-2xl border px-2 py-3 text-sm font-extrabold transition"
                                        :class="answers[current.id].value === value ? 'border-amber bg-amber-soft text-amber-ink' : 'border-line bg-surface text-muted hover:border-amber/30'"
                                        x-text="value"></button>
                            </template>
                        </div>
                        <div class="mt-2 flex justify-between text-[10px] font-semibold text-muted"><span>совсем не про меня</span><span>полностью про меня</span></div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="error" x-cloak class="mt-4 rounded-2xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose" x-text="error"></div>

        <div class="mt-5 flex items-center justify-between gap-3">
            <button type="button" @click="prev()" class="btn-secondary min-h-11 px-4 py-2.5 text-xs" :disabled="index === 0">
                <x-icon name="chevron-left" class="h-4 w-4" /> Назад
            </button>
            <button type="button" @click="next()" class="btn-primary min-h-11 px-6 py-2.5 text-xs" :disabled="submitting">
                <span x-show="!submitting" x-text="isLast ? 'Завершить' : 'Дальше'"></span>
                <span x-show="submitting" x-cloak>Сохраняем…</span>
                <x-icon name="chevron-right" class="h-4 w-4" />
            </button>
        </div>

        <p class="mt-3 text-center text-[10px] leading-relaxed text-muted">Ответы сохраняются каждые 12 секунд. Ключи не передаются в браузер. Клавиши ← → и 1–6 работают без мыши.</p>

        <div x-show="confirmSubmit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-inverse/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-line bg-surface p-6 shadow-xl" @click.outside="confirmSubmit = false">
                <p class="text-lg font-extrabold">Есть задания без ответа</p>
                <p class="mt-1 text-sm leading-relaxed text-muted">Без ответа: <span class="font-extrabold text-ink" x-text="unansweredCount"></span>. Они будут оценены в 0. Можно вернуться и заполнить.</p>
                <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                    <button type="button" @click="confirmSubmit = false" class="btn-secondary flex-1 py-2.5 text-xs">Вернуться</button>
                    <button type="button" @click="submit()" class="btn-primary flex-1 py-2.5 text-xs">Завершить всё равно</button>
                </div>
            </div>
        </div>
    </div>
@endsection

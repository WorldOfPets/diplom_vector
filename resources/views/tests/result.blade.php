@extends('layouts.app')

@section('title', 'Результат: '.$test->title)

@php
    $isMotivation = $test->kind === 'motivation';
    $branchCount = $branches->count();
    $useBar = $isMotivation || $branchCount < 3;
    $chartColors = $isMotivation
        ? array_slice(['#D97706', '#F59E0B', '#FBBF24', '#B45309'], 0, max($branchCount, 1))
        : $branches->pluck('color')->all();

    if ($useBar) {
        $chartConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => $branches->pluck('label')->all(),
                'datasets' => [[
                    'label' => 'Профиль',
                    'data' => $branches->pluck('value')->all(),
                    'backgroundColor' => $chartColors,
                    'borderRadius' => 8,
                    'barThickness' => 24,
                ]],
            ],
            'options' => [
                'indexAxis' => 'y',
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'x' => ['beginAtZero' => true, 'max' => 100, 'ticks' => ['color' => '#8B93A3', 'font' => ['size' => 10]], 'grid' => ['color' => 'rgba(139,147,163,0.22)']],
                    'y' => ['grid' => ['display' => false], 'ticks' => ['color' => '#8B93A3', 'font' => ['size' => 11, 'weight' => '600']]],
                ],
            ],
        ];
    } else {
        $chartConfig = [
            'type' => 'radar',
            'data' => [
                'labels' => $branches->pluck('label')->all(),
                'datasets' => [[
                    'label' => 'Ваш профиль',
                    'data' => $branches->pluck('value')->all(),
                    'backgroundColor' => 'rgba(45, 91, 255, 0.16)',
                    'borderColor' => '#2D5BFF',
                    'borderWidth' => 2,
                    'pointBackgroundColor' => '#2D5BFF',
                    'pointRadius' => 3,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'r' => [
                        'beginAtZero' => true,
                        'max' => 100,
                        'ticks' => ['display' => false, 'stepSize' => 25],
                        'grid' => ['color' => 'rgba(139,147,163,0.22)'],
                        'angleLines' => ['color' => 'rgba(139,147,163,0.22)'],
                        'pointLabels' => ['color' => '#8B93A3', 'font' => ['size' => 11, 'weight' => '600']],
                    ],
                ],
            ],
        ];
    }
@endphp

@section('content')
    <nav class="flex items-center gap-1.5 text-[11px] font-bold text-muted" aria-label="Хлебные крошки">
        <a href="{{ route('tests.index') }}" class="hover:text-ink">Диагностика</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <span class="text-ink-soft">{{ $test->title }}</span>
    </nav>

    <div class="mt-4 grid gap-5 lg:grid-cols-[1.45fr_1fr]">
        <div class="space-y-5">
            <section class="card relative overflow-hidden p-6 sm:p-7">
                <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full" style="background: {{ $levelInfo['color'] }}18;"></div>
                <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center">
                    <div class="flex flex-col items-center">
                        <x-progress-ring :value="$attempt->overall" :size="112" :stroke="9" :color="$levelInfo['color']" />
                        @if($isMotivation)
                            <p class="mt-2 max-w-[110px] text-center text-[9px] font-semibold leading-snug text-muted">интерес + профессиональный смысл</p>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="chip">{{ $test->title }}</span>
                            @if($isMotivation)
                                <span class="chip border-amber/20 bg-amber-soft text-amber-ink">профиль</span>
                            @else
                                <x-level-badge :level="$level" />
                            @endif
                            @if($previous)
                                <span class="chip {{ ($delta ?? 0) >= 0 ? 'border-mint/20 bg-mint-soft text-mint-ink' : 'border-rose/20 bg-rose/10 text-rose' }}">
                                    {{ ($delta ?? 0) >= 0 ? '+' : '' }}{{ $delta }} п.п. к прошлому
                                </span>
                            @endif
                        </div>
                        <h1 class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $isMotivation ? 'Профиль мотивации' : 'Профиль эмоционального интеллекта' }}</h1>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $overallText }}</p>
                        <p class="mt-2 text-[11px] font-semibold text-muted">{{ $attempt->finished_at?->translatedFormat('j F Y, H:i') }} · {{ $attempt->duration_sec ? gmdate('i:s', (int) $attempt->duration_sec) : '—' }}</p>
                    </div>
                </div>
            </section>

            <section class="card p-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="section-eyebrow">Профиль</p>
                        <h2 class="mt-1 text-xl font-extrabold">{{ $isMotivation ? 'По шкалам' : 'По ветвям' }}</h2>
                        <p class="mt-1 max-w-md text-[11px] leading-relaxed text-muted">Формулировки описывают выполнение заданий в этой попытке, а не устойчивые черты.</p>
                    </div>
                    @if($isMotivation)
                        <span class="chip border-amber/20 bg-amber-soft text-amber-ink">без «хорошо/плохо»</span>
                    @else
                        <x-level-badge :level="$level" :label="$levelInfo['label']" />
                    @endif
                </div>

                <div class="mt-4 grid gap-5 sm:grid-cols-[0.9fr_1.1fr] sm:items-center">
                    <div class="h-64">
                        <canvas x-data="chartCanvas(@js($chartConfig))"></canvas>
                    </div>
                    <div class="space-y-3">
                        @foreach($branches as $branch)
                            @php
                                $prevValue = $previous?->scores[$branch['key']] ?? null;
                                $branchDelta = $prevValue !== null ? (float) $branch['value'] - (float) $prevValue : null;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between text-xs font-bold">
                                    <span class="text-ink-soft">{{ $branch['label'] }}</span>
                                    <span class="flex items-center gap-1.5">
                                        <span style="color: {{ $branch['color'] }};">{{ (int) $branch['value'] }}%</span>
                                        @if($branchDelta !== null)
                                            <span class="text-[10px] {{ $branchDelta >= 0 ? 'text-mint-ink' : 'text-rose' }}">{{ $branchDelta >= 0 ? '+' : '' }}{{ round($branchDelta, 1) }}</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-paper-deep">
                                    <div class="h-full rounded-full" style="width: {{ (int) $branch['value'] }}%; background: {{ $branch['color'] }};"></div>
                                </div>
                                <p class="mt-1.5 text-[11px] leading-relaxed text-muted">{{ $branch['text'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($isMotivation)
                    <p class="mt-4 rounded-2xl border border-amber/20 bg-amber-soft/40 px-4 py-3 text-[11px] leading-relaxed text-ink-soft">
                        Внешняя мотивация — не «плохая». Требования, оценки и ожидания близких могут поддерживать. Важно проверить, что из этого помогает, а что становится давлением.
                    </p>
                @endif
            </section>

            @if($recommendations !== [])
                <section class="card p-5 sm:p-6">
                    <p class="section-eyebrow">Рекомендации</p>
                    <h2 class="mt-1 text-xl font-extrabold">С чего начать</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach($recommendations as $recommendation)
                            <div class="rounded-2xl border border-line bg-surface p-4">
                                <p class="text-sm font-extrabold">{{ $recommendation['title'] }}</p>
                                <p class="mt-1 text-xs leading-relaxed text-muted">{{ $recommendation['reason'] }}</p>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <a href="{{ route('modules.show', $recommendation['module']) }}" class="chip border-brand/20 bg-brand-soft text-brand-deep transition hover:border-brand">
                                        Модуль →
                                    </a>
                                    @foreach($recommendation['trainers'] as $trainerSlug)
                                        @if($trainerTitles->has($trainerSlug))
                                            <a href="{{ route('trainers.show', $trainerSlug) }}" class="chip bg-paper text-ink-soft transition hover:border-brand/30">
                                                {{ $trainerTitles[$trainerSlug] }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="rounded-3xl border border-line/60 bg-surface/50 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-muted">Важно</p>
                <p class="mt-2 text-xs leading-relaxed text-ink-soft">
                    Это педагогический инструмент, а не психологическая диагностика и не стандартизированная методика. Результат показывает уровень освоения навыков в конкретный момент и не является ярлыком.
                    @if($isMotivation) Мотивация меняется под влиянием среды, целей и опыта — её можно развивать. @endif
                </p>
            </section>
        </div>

        <aside class="space-y-4">
            @if($previous)
                <div class="card p-5">
                    <p class="section-eyebrow">Динамика</p>
                    <h2 class="mt-1 text-lg font-extrabold">С прошлой попытки</h2>
                    <div class="mt-3 flex items-end gap-2">
                        <span class="text-3xl font-extrabold" style="color: {{ $levelInfo['color'] }};">{{ $delta >= 0 ? '+' : '' }}{{ $delta }}</span>
                        <span class="pb-1 text-xs font-bold text-muted">п.п.</span>
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-muted">
                        Прошлый результат: {{ (int) $previous->overall }}% ({{ $previous->finished_at?->translatedFormat('j F') }}).
                        @if($delta > 0) Есть положительный сдвиг. @elseif($delta < 0) Спад — нормальная часть динамики, особенно при усталости. @else Пока без изменений. @endif
                    </p>
                </div>
            @endif

            <div class="card p-5">
                <p class="section-eyebrow">Дальше</p>
                <h2 class="mt-1 text-lg font-extrabold">{{ $isMotivation ? 'Поддержите мотивацию' : 'Закрепите навык' }}</h2>
                <p class="mt-2 text-xs leading-relaxed text-muted">Один тренажёр в день — лучше, чем марафон раз в неделю. Начните с модуля, который рекомендован выше.</p>
                <a href="{{ route('modules.index') }}" class="btn-primary mt-4 w-full py-2.5 text-xs">К модулям</a>
                @if($test->is_active && $attempt->user_id === auth()->id())
                    <a href="{{ route('tests.history', $test) }}" class="btn-secondary mt-2 w-full py-2 text-xs">История и динамика</a>
                @endif
                <a href="{{ route('tests.index') }}" class="btn-ghost mt-2 w-full py-2 text-xs">Все диагностики</a>
            </div>

            @if($quality['too_fast'] || $quality['skipped'] > 0)
                <div class="rounded-3xl border border-amber/20 bg-amber-soft/40 p-5">
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-amber-ink">Как читать результат</p>
                    @if($quality['too_fast'])
                        <p class="mt-2 text-xs leading-relaxed text-amber-ink">
                            Медианное время на задание — {{ number_format($quality['median_ms'] / 1000, 1, ',', ' ') }} с. Очень быстрый темп: результат может быть менее устойчивым. Лучше пройти ещё раз в спокойном темпе.
                        </p>
                    @endif
                    @if($quality['skipped'] > 0)
                        <p class="mt-2 text-xs leading-relaxed text-amber-ink">
                            Без ответа: {{ $quality['skipped'] }}. Такие задания не вошли в расчёт или оценены в 0 — это влияет на балл, но не на вашу ценность.
                        </p>
                    @endif
                </div>
            @endif

            @if($isMotivation)
                <div class="rounded-3xl border border-amber/20 bg-amber-soft/50 p-5">
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-amber-ink">Напоминание</p>
                    <p class="mt-2 text-xs leading-relaxed text-amber-ink">Мотивация — не «или есть, или нет». Это сумма маленьких выборов, среды и смысла. Начните с одного шага.</p>
                </div>
            @endif
        </aside>
    </div>
@endsection

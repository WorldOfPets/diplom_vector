@extends('layouts.app')

@section('title', 'История: '.$test->title)

@php
    $isMotivation = $test->kind === 'motivation';
    $pointsAsc = $points;
    $pointsDesc = array_reverse($points);
    $hasChart = count($points) >= 2;
    $last = $points === [] ? null : $points[count($points) - 1];
    $best = $points === [] ? null : max(array_map(fn (array $point): float => $point['overall'], $points));

    $datasets = [[
        'label' => 'Общий балл',
        'data' => array_map(fn (array $point): float => $point['overall'], $pointsAsc),
        'borderColor' => $isMotivation ? '#92400E' : '#2D5BFF',
        'backgroundColor' => $isMotivation ? 'rgba(146, 64, 14, 0.12)' : 'rgba(45, 91, 255, 0.12)',
        'borderWidth' => 3,
        'tension' => 0.35,
        'pointRadius' => 3,
        'fill' => true,
    ]];

    foreach ($branchKeys as $key) {
        $datasets[] = [
            'label' => $labels[$key] ?? $key,
            'data' => array_map(fn (mixed $value): ?float => $value === null ? null : (float) $value, $series[$key] ?? []),
            'borderColor' => $colors[$key] ?? '#2D5BFF',
            'backgroundColor' => 'transparent',
            'borderWidth' => 1.5,
            'borderDash' => [4, 3],
            'tension' => 0.35,
            'pointRadius' => 2,
            'fill' => false,
            'spanGaps' => true,
        ];
    }

    $chartConfig = [
        'type' => 'line',
        'data' => [
            'labels' => array_map(fn (array $point): string => $point['date']?->format('d.m') ?? '', $pointsAsc),
            'datasets' => $datasets,
        ],
        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'labels' => ['boxWidth' => 10, 'usePointStyle' => true, 'color' => '#667085', 'font' => ['size' => 10]],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => ['color' => '#8B93A3', 'font' => ['size' => 10]],
                    'grid' => ['color' => 'rgba(139,147,163,0.22)'],
                ],
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['color' => '#8B93A3', 'font' => ['size' => 10]],
                ],
            ],
        ],
    ];
@endphp

@section('content')
    <nav class="flex items-center gap-1.5 text-[11px] font-bold text-muted" aria-label="Хлебные крошки">
        <a href="{{ route('tests.index') }}" class="hover:text-ink">Диагностика</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <a href="{{ route('tests.show', $test) }}" class="hover:text-ink">{{ $test->title }}</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <span class="text-ink-soft">История</span>
    </nav>

    <div class="mt-4 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="section-eyebrow">Динамика</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">История: {{ $test->title }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
                Сравнивайте попытки по общему баллу и по шкалам. Один замер — это точка, два и больше — уже динамика.
            </p>
            @if($isMotivation)
                <span class="chip mt-3 inline-flex border-amber/20 bg-amber-soft text-amber-ink">профиль без «хорошо/плохо»</span>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('tests.show', $test) }}" class="btn-secondary px-4 py-2.5 text-xs">К диагностике</a>
            <form method="POST" action="{{ route('tests.start', $test) }}">
                @csrf
                <input type="hidden" name="retake" value="1">
                <button type="submit" class="btn-primary px-4 py-2.5 text-xs">Пройти ещё раз</button>
            </form>
        </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <div class="card p-5">
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted">Попыток</p>
            <p class="mt-1 text-3xl font-extrabold">{{ count($points) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted">{{ $isMotivation ? 'Максимум' : 'Лучший результат' }}</p>
            <p class="mt-1 text-3xl font-extrabold">{{ $best === null ? '—' : (int) $best }}%</p>
        </div>
        <div class="card p-5">
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted">Последний</p>
            <p class="mt-1 text-3xl font-extrabold">{{ $last === null ? '—' : (int) $last['overall'] }}%</p>
        </div>
    </div>

    <section class="card mt-4 p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="section-eyebrow">График</p>
                <h2 class="mt-1 text-xl font-extrabold">{{ $isMotivation ? 'Мотивация по попыткам' : 'Профиль по попыткам' }}</h2>
            </div>
            <span class="chip">{{ count($points) }} {{ \App\Support\Text::plural(count($points), 'замер', 'замера', 'замеров') }}</span>
        </div>

        @if($hasChart)
            <div class="mt-4 h-72">
                <canvas x-data="chartCanvas(@js($chartConfig))"></canvas>
            </div>
        @else
            <div class="mt-4 rounded-2xl border border-dashed border-line bg-paper/50 p-6 text-center">
                <p class="text-sm font-bold">Нужен ещё один замер</p>
                <p class="mx-auto mt-1 max-w-md text-xs leading-relaxed text-muted">После второй завершённой попытки здесь появится график: общий балл и каждая шкала отдельно.</p>
                <form method="POST" action="{{ route('tests.start', $test) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="retake" value="1">
                    <button type="submit" class="btn-primary px-4 py-2.5 text-xs">Пройти сейчас</button>
                </form>
            </div>
        @endif
    </section>

    <section class="card mt-4 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4">
            <div>
                <p class="section-eyebrow">Попытки</p>
                <h2 class="mt-1 text-lg font-extrabold">Все замеры</h2>
            </div>
            @if($last)
                <a href="{{ route('tests.result', $last['id']) }}" class="hidden text-[11px] font-bold text-brand hover:underline sm:block">Открыть последний →</a>
            @endif
        </div>

        @if($pointsDesc === [])
            <div class="border-t border-line/70 px-5 py-8 text-center text-sm text-muted">Пока нет завершённых попыток.</div>
        @else
            <div class="overflow-x-auto border-t border-line/70">
                <table class="w-full min-w-[560px] text-left text-xs">
                    <thead class="bg-paper/60 text-[10px] font-bold uppercase tracking-[0.12em] text-muted">
                        <tr>
                            <th class="px-5 py-3">Дата</th>
                            <th class="px-3 py-3">Балл</th>
                            <th class="px-3 py-3">{{ $isMotivation ? '' : 'Уровень' }}</th>
                            <th class="px-3 py-3">Время</th>
                            <th class="px-3 py-3">Δ</th>
                            <th class="px-5 py-3 text-right">Разбор</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/70">
                        @foreach($pointsDesc as $index => $point)
                            @php
                                $older = $pointsDesc[$index + 1] ?? null;
                                $delta = $older !== null ? round($point['overall'] - $older['overall'], 1) : null;
                            @endphp
                            <tr class="bg-surface/40">
                                <td class="whitespace-nowrap px-5 py-3 font-semibold text-ink-soft">{{ $point['date']?->translatedFormat('j M Y, H:i') ?? '—' }}</td>
                                <td class="px-3 py-3 font-extrabold">{{ (int) $point['overall'] }}%</td>
                                <td class="px-3 py-3">
                                    @if($isMotivation)
                                        <span class="text-muted">—</span>
                                    @else
                                        <x-level-badge :level="$point['level']" />
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-muted">{{ $point['duration'] ? gmdate('i:s', (int) $point['duration']) : '—' }}</td>
                                <td class="px-3 py-3 font-bold {{ $delta === null ? 'text-muted' : ($delta >= 0 ? 'text-mint-ink' : 'text-rose') }}">
                                    {{ $delta === null ? '—' : (($delta >= 0 ? '+' : '').$delta) }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('tests.result', $point['id']) }}" class="font-bold text-brand hover:underline">Открыть</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <p class="mt-4 rounded-3xl border border-line/60 bg-surface/50 p-5 text-xs leading-relaxed text-muted">
        Это тренировочный инструмент, а не клиническая диагностика. Скачки вверх и вниз — нормальная часть динамики: на результат влияют усталость, контекст и опыт. Смотрите на тренд, а не на одну точку. Показаны последние 50 попыток.
    </p>
@endsection

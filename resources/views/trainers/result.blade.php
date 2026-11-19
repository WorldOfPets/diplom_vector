@extends('layouts.app')

@section('title', 'Результат: '.$trainer->title)

@php
    $percent = (int) round($attempt->percent);
    $passed = (bool) $attempt->passed;
    $chartConfig = null;

    if ($chart) {
        $labels = array_column($chart['data'], 'label');
        $values = array_column($chart['data'], 'value');
        $colors = array_column($chart['data'], 'color');
        $chartMax = (int) ($chart['max'] ?? 100);
        $chartUnit = $chart['unit'] ?? 'percent';
        $radarMax = $chart['type'] === 'radar' ? \App\Support\Chart::radarMax($values) : $chartMax;
        $chartConfig = [
            'type' => $chart['type'],
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Ваш профиль',
                    'data' => $values,
                    'backgroundColor' => $chart['type'] === 'radar'
                        ? 'rgba(45, 91, 255, 0.16)'
                        : array_map(fn ($color) => $color.'CC', $colors),
                    'borderColor' => $chart['type'] === 'radar' ? '#2D5BFF' : $colors,
                    'borderWidth' => 2,
                    'pointBackgroundColor' => '#2D5BFF',
                    'pointRadius' => 3,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                ],
                'scales' => $chart['type'] === 'radar' ? [
                    'r' => [
                        'beginAtZero' => true,
                        'max' => $radarMax,
                        'ticks' => ['display' => false, 'stepSize' => max(1, (int) round($radarMax / 4))],
                        'grid' => ['color' => 'rgba(139,147,163,0.22)'],
                        'angleLines' => ['color' => 'rgba(139,147,163,0.22)'],
                        'pointLabels' => ['color' => '#8B93A3', 'font' => ['size' => 11, 'weight' => '600']],
                    ],
                ] : [
                    'x' => ['grid' => ['display' => false], 'ticks' => ['color' => '#8B93A3', 'font' => ['size' => 10]]],
                    'y' => ['beginAtZero' => true, 'max' => $chartMax, 'ticks' => ['display' => false], 'grid' => ['color' => 'rgba(139,147,163,0.22)']],
                ],
            ],
        ];
    }
@endphp

@section('content')
    <nav class="flex items-center gap-1.5 text-[11px] font-bold text-muted" aria-label="Хлебные крошки">
        <a href="{{ route('modules.show', $trainer->module) }}" class="hover:text-ink">{{ $trainer->module->title }}</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <span class="text-ink-soft">{{ $trainer->title }}</span>
    </nav>

    <div class="mt-4 grid gap-5 lg:grid-cols-[1.4fr_1fr]">
        <div class="space-y-5">
            <section class="card relative overflow-hidden p-6 sm:p-7">
                <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full {{ $passed ? 'bg-mint-soft' : 'bg-amber-soft' }}"></div>
                <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center">
                    <x-progress-ring :value="$percent" :size="104" :stroke="9" :color="$passed ? '#2BB673' : '#D97706'" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="chip">{{ $trainer->module->title }}</span>
                            <span class="chip {{ $passed ? 'border-mint/20 bg-mint-soft text-mint-ink' : 'border-amber/20 bg-amber-soft text-amber-ink' }}">
                                {{ $passed ? 'упражнение засчитано' : 'есть что усилить' }}
                            </span>
                            @if($trainer->engine !== 'camera')
                                @php
                                    $earnedUnits = \App\Services\ContentProgressService::trainerUnits((float) $attempt->percent);
                                    $unitsWord = match ($earnedUnits) {
                                        1 => 'единица',
                                        2 => 'единицы',
                                        default => 'единиц',
                                    };
                                @endphp
                                <span class="chip {{ $earnedUnits === 2 ? 'border-mint/20 bg-mint-soft text-mint-ink' : '' }}" title="В общий прогресс идёт лучшая попытка по тренажёру">{{ $earnedUnits }} {{ $unitsWord }} прогресса</span>
                            @endif
                        </div>
                        <h1 class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $trainer->title }}</h1>
                        @if($trainer->engine === 'camera' && isset($attempt->response['completed']))
                            <p class="mt-2 text-sm text-muted">Пройдено эмоций: <strong class="text-ink">{{ (int) $attempt->response['completed'] }} из {{ (int) ($attempt->response['total'] ?? 0) }}</strong> · средняя уверенность модели: {{ (int) ($attempt->metrics['confidence'] ?? 0) }}%</p>
                        @elseif($attempt->metrics['confidence'] ?? null)
                            <p class="mt-2 text-sm text-muted">Уверенность модели: {{ (int) $attempt->metrics['confidence'] }}%</p>
                        @else
                            <p class="mt-2 text-sm text-muted">Результат сохранён {{ $attempt->completed_at?->translatedFormat('j F, H:i') }} · {{ $attempt->duration_sec ? gmdate('i:s', (int) $attempt->duration_sec) : '—' }}</p>
                        @endif
                    </div>
                </div>
            </section>

            @php
                $drawingConfig = $attempt->config_snapshot['drawing'] ?? $trainer->config['drawing'] ?? null;
            @endphp

            @if(! empty($drawingConfig) && ! empty($attempt->response['strokes']))
                @include('trainers.engines.partials.drawing', ['config' => ['drawing' => $drawingConfig], 'saved' => $attempt->response ?? [], 'readonly' => true])
            @endif

            @if($chartConfig)
                <section class="card p-5 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="section-eyebrow">Профиль</p>
                            <h2 class="mt-1 text-lg font-extrabold">Что получилось</h2>
                        </div>
                        <span class="text-[11px] font-bold text-muted">
                            @if($chartUnit === 'points')
                                в баллах 0–{{ $chartMax }}
                            @elseif($chart['type'] === 'radar' && $radarMax < 100)
                                в процентах · шкала 0–{{ $radarMax }}
                            @else
                                в процентах
                            @endif
                        </span>
                    </div>
                    <div class="mt-4 h-80 sm:h-96">
                        <canvas x-data="chartCanvas(@js($chartConfig))"></canvas>
                    </div>
                    <div class="mt-2 grid gap-1.5 sm:grid-cols-2">
                        @foreach($chart['data'] as $row)
                            <div class="flex items-center justify-between rounded-xl bg-paper/60 px-3 py-2 text-xs">
                                <span class="flex items-center gap-2 font-semibold text-ink-soft">
                                    <span class="h-1.5 w-1.5 rounded-full" style="background: {{ \App\Support\Color::safe($row['color'] ?? null) }};"></span>
                                    {{ $row['label'] }}
                                </span>
                                <span class="font-extrabold" style="color: {{ \App\Support\Color::safe($row['color'] ?? null) }};">{{ (int) $row['value'] }}{{ $chartUnit === 'points' ? ' / '.$chartMax : '%' }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($trainer->engine === 'shield')
                @php
                    $snapshot = $attempt->config_snapshot ?? [];
                    $shieldSectors = $snapshot['sectors'] ?? [];
                    $shieldValues = $attempt->response['sectors'] ?? [];
                    $shieldSymbols = $attempt->response['symbols'] ?? [];
                @endphp
                <section class="card p-5 sm:p-6">
                    <p class="section-eyebrow">Ваш герб</p>
                    <h2 class="mt-1 text-lg font-extrabold">Профессиональная опора</h2>
                    <div class="mx-auto mt-4 max-w-sm">
                        <div class="grid grid-cols-2 gap-1 overflow-hidden p-4 pb-12 text-white" style="clip-path: polygon(50% 0, 100% 15%, 92% 82%, 50% 100%, 8% 82%, 0 15%); background: {{ \App\Support\Color::safe($snapshot['color'] ?? null) }}; min-height: 360px;">
                            @foreach($shieldSectors as $sector)
                                <div class="flex flex-col items-center justify-center gap-1 rounded-xl bg-black/15 p-2 text-center">
                                    <span class="text-lg">{{ $shieldSymbols[$sector['key']] ?? $sector['emoji'] ?? '✦' }}</span>
                                    <span class="text-[9px] font-bold uppercase tracking-[0.08em] text-white/70">{{ $sector['label'] }}</span>
                                    <span class="line-clamp-3 text-[10px] font-semibold text-white/95">{{ $shieldValues[$sector['key']] ?? '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @if($trainer->engine === 'timeline')
                @php
                    $snapshot = $attempt->config_snapshot ?? [];
                    $timelineColumns = $snapshot['columns'] ?? [];
                    $timelineValues = $attempt->response['milestones'] ?? [];
                @endphp
                <section class="card p-5 sm:p-6">
                    <p class="section-eyebrow">Маршрут</p>
                    <h2 class="mt-1 text-lg font-extrabold">Ваши ориентиры</h2>
                    <div class="relative mt-4 space-y-3 pl-5">
                        <div class="absolute bottom-2 left-1.5 top-2 w-px bg-line"></div>
                        @foreach($timelineColumns as $index => $column)
                            <div class="relative rounded-2xl border border-line bg-paper/50 p-3">
                                <span class="absolute -left-[1.3rem] top-4 h-3 w-3 rounded-full border-2 border-white bg-brand"></span>
                                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-muted">{{ $column['label'] }}</p>
                                <p class="mt-1 text-xs leading-relaxed text-ink-soft">{{ $timelineValues[$column['key']] ?? '—' }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="card p-5 sm:p-6">
                <p class="section-eyebrow">Разбор</p>
                <h2 class="mt-1 text-lg font-extrabold">Что это значит</h2>
                <div class="mt-3 space-y-2.5 text-sm leading-relaxed text-ink-soft">
                    @foreach(explode("\n", (string) $attempt->feedback) as $line)
                        @if(trim($line) !== '')
                            <p>{{ $line }}</p>
                        @endif
                    @endforeach
                </div>
                @if($passed)
                    <x-alert type="success" class="mt-4">Упражнение засчитано. Следующий шаг — закрепить навык в реальной ситуации.</x-alert>
                @else
                    <x-alert type="warning" class="mt-4">Пока не достигнут порог. Это не провал: вернитесь к упражнению через день и попробуйте снова.</x-alert>
                @endif
            </section>

            <section class="card p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="section-eyebrow">Рефлексия</p>
                        <h2 class="mt-1 text-lg font-extrabold">Чемодан. Корзина. Мясорубка</h2>
                        <p class="mt-1 text-xs leading-relaxed text-muted">Одна минута, чтобы забрать опыт и не тащить лишнее.</p>
                    </div>
                </div>

                @php $reflection = $attempt->reflection; @endphp

                @if($reflection)
                    <div class="mt-4 rounded-2xl border border-mint/20 bg-mint-soft/50 p-4">
                        <p class="text-sm font-bold text-mint-ink">
                            @if($reflection->choice === 'suitcase') 🧳 Забираю с собой
                            @elseif($reflection->choice === 'basket') 🗑️ В корзину
                            @else ⚙️ Прокрутить ещё
                            @endif
                        </p>
                        @if($reflection->note)
                            <p class="mt-1 text-xs leading-relaxed text-mint-ink/80">{{ $reflection->note }}</p>
                        @endif
                        <p class="mt-2 text-[10px] font-semibold text-mint-ink/50">Можно изменить, отправив форму ниже.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('reflections.store', $attempt) }}" class="mt-4 space-y-3">
                    @csrf
                    <div class="grid gap-2 sm:grid-cols-3">
                        <label class="cursor-pointer rounded-2xl border border-line bg-surface p-3 transition hover:border-brand/30">
                            <input type="radio" name="choice" value="suitcase" class="h-4 w-4 text-brand" @checked($reflection?->choice === 'suitcase') required>
                            <span class="mt-2 block text-sm font-bold">🧳 Забираю</span>
                            <span class="mt-0.5 block text-[10px] text-muted">Применю в жизни</span>
                        </label>
                        <label class="cursor-pointer rounded-2xl border border-line bg-surface p-3 transition hover:border-brand/30">
                            <input type="radio" name="choice" value="basket" class="h-4 w-4 text-brand" @checked($reflection?->choice === 'basket')>
                            <span class="mt-2 block text-sm font-bold">🗑️ В корзину</span>
                            <span class="mt-0.5 block text-[10px] text-muted">Отпускаю негатив</span>
                        </label>
                        <label class="cursor-pointer rounded-2xl border border-line bg-surface p-3 transition hover:border-brand/30">
                            <input type="radio" name="choice" value="mincer" class="h-4 w-4 text-brand" @checked($reflection?->choice === 'mincer')>
                            <span class="mt-2 block text-sm font-bold">⚙️ Прокрутить</span>
                            <span class="mt-0.5 block text-[10px] text-muted">Вернусь ещё раз</span>
                        </label>
                    </div>
                    <textarea name="note" rows="2" class="textarea" placeholder="Короткая заметка (необязательно)">{{ $reflection?->note }}</textarea>
                    <button type="submit" class="btn-secondary w-full py-2.5 text-xs">Сохранить рефлексию</button>
                </form>
            </section>
        </div>

        <aside class="space-y-4">
            <div class="card p-5">
                <p class="section-eyebrow">Дальше</p>
                @if($next)
                    <h2 class="mt-1 text-lg font-extrabold">{{ $next->title }}</h2>
                    <p class="mt-1 text-xs leading-relaxed text-muted">{{ $next->module->title }} · {{ $next->duration_min }} минут</p>
                    <a href="{{ route('trainers.show', $next) }}" class="btn-primary mt-4 w-full py-2.5 text-xs">Следующий тренажёр</a>
                @else
                    <h2 class="mt-1 text-lg font-extrabold">Это последний тренажёр</h2>
                    <p class="mt-1 text-xs leading-relaxed text-muted">Посмотрите итоговую динамику в профиле.</p>
                    <a href="{{ route('profile.index') }}" class="btn-primary mt-4 w-full py-2.5 text-xs">Мои результаты</a>
                @endif
                <a href="{{ route('modules.show', $trainer->module) }}" class="btn-ghost mt-2 w-full py-2 text-xs">Вернуться в модуль</a>
            </div>

            <div class="card p-5">
                <p class="section-eyebrow">Заметка</p>
                <p class="mt-2 text-xs leading-relaxed text-ink-soft">Результат тренажёра — не оценка вас. Это снимок навыка в конкретный момент. Он может меняться.</p>
                <a href="{{ route('diary.index') }}" class="btn-secondary mt-3 w-full py-2.5 text-xs">Записать эмоцию</a>
            </div>
        </aside>
    </div>
@endsection

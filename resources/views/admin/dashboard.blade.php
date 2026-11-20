@extends('layouts.admin')

@section('title', 'Обзор')

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="section-eyebrow">Админка</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight">Обзор</h1>
            <p class="mt-1 text-sm text-muted">Сводка по пользователям, диагностике и практике.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.analytics.index') }}" class="btn-secondary px-4 py-2.5 text-xs">Аналитика</a>
            <a href="{{ route('admin.feedback.index') }}" class="btn-secondary px-4 py-2.5 text-xs">Качество</a>
            <a href="{{ route('admin.export.progress') }}" class="btn-primary px-4 py-2.5 text-xs">Выгрузить прогресс</a>
        </div>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Пользователи" :value="$stats['users']" :hint="$stats['students'].' '.\App\Support\Text::plural($stats['students'], 'студент', 'студента', 'студентов')" icon="user" tone="brand" />
        <x-stat label="Активны за 7 дней" :value="$stats['active']" hint="заходили в приложение" icon="sparkles" tone="mint" />
        <x-stat label="Тренажёры" :value="$stats['trainers']" hint="завершённых попыток" icon="grid" tone="coral" />
        <x-stat label="Средний ЭИ" :value="$stats['avg_ei'] ? $stats['avg_ei'].'%' : '—'" hint="по завершённым тестам" icon="chart" tone="lilac" />
    </div>

    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Диагностика" :value="$stats['tests']" :hint="$stats['tests'].' '.\App\Support\Text::plural($stats['tests'], 'тест', 'теста', 'тестов')" icon="target" tone="amber" />
        <x-stat label="Дневник" :value="$stats['diary']" :hint="$stats['diary'].' '.\App\Support\Text::plural($stats['diary'], 'запись', 'записи', 'записей')" icon="calendar" tone="ink" />
        <x-stat label="Сертификаты" :value="$stats['certificates']" hint="выдано студентам" icon="check-circle" tone="mint" />
        <x-stat label="Оценка тренажёров" :value="$stats['feedback'] > 0 ? number_format($stats['feedback_avg'], 2, ',', ' ') : '—'" :hint="$stats['feedback'].' '.\App\Support\Text::plural($stats['feedback'], 'отзыв', 'отзыва', 'отзывов')" icon="star" tone="amber" />
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-[1.5fr_1fr]">
        <section class="card p-5">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="section-eyebrow">Активность</p>
                    <h2 class="mt-1 text-lg font-extrabold">Завершённые действия за 14 дней</h2>
                </div>
                <div class="flex items-center gap-3 text-[10px] font-bold text-muted">
                    <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-brand"></span> тренажёры</span>
                    <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-lilac"></span> тесты</span>
                </div>
            </div>

            @php $peak = max(1, collect($activity)->max('total')); @endphp
            <div class="mt-5 flex h-40 items-end gap-1.5">
                @foreach($activity as $day)
                    <div class="group flex h-full flex-1 flex-col justify-end gap-1" title="{{ $day['date']->translatedFormat('d.m.Y') }}: тренажёры {{ $day['trainers'] }}, тесты {{ $day['tests'] }}">
                        <div class="flex w-full flex-col justify-end gap-0.5" style="height: {{ $day['total'] > 0 ? max(6, round($day['total'] / $peak * 100)) : 2 }}%;">
                            @if($day['trainers'] > 0)
                                <div class="w-full rounded-t bg-brand" style="flex: {{ $day['trainers'] }};"></div>
                            @endif
                            @if($day['tests'] > 0)
                                <div class="w-full rounded-b bg-lilac" style="flex: {{ $day['tests'] }};"></div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-[9px] font-semibold text-muted">
                <span>{{ $activity[0]['label'] }}</span>
                <span>{{ $activity[6]['label'] }}</span>
                <span>{{ $activity[13]['label'] }}</span>
            </div>
            <p class="mt-3 text-[11px] text-muted">Всего за период: <strong class="text-ink">{{ collect($activity)->sum('total') }}</strong> завершённых действий.</p>
        </section>

        <section class="card p-5">
            <p class="section-eyebrow">Воронка</p>
            <h2 class="mt-1 text-lg font-extrabold">От регистрации к сертификату</h2>

            @php
                $funnel = [
                    ['label' => 'Зарегистрированы', 'value' => $stats['students'], 'of' => max(1, $stats['students']), 'color' => 'bg-brand'],
                    ['label' => 'Сделали упражнение', 'value' => $stats['active_students'], 'of' => max(1, $stats['students']), 'color' => 'bg-lilac'],
                    ['label' => 'Дошли до 50%', 'value' => $stageStats['fifty']['users'], 'of' => max(1, $stats['students']), 'color' => 'bg-mint'],
                    ['label' => 'Дошли до 90%', 'value' => $stageStats['ninety']['users'], 'of' => max(1, $stats['students']), 'color' => 'bg-amber'],
                    ['label' => 'Получили сертификат', 'value' => $stats['certificates'], 'of' => max(1, $stats['students']), 'color' => 'bg-coral'],
                ];
            @endphp

            <div class="mt-4 space-y-3">
                @foreach($funnel as $step)
                    <div>
                        <div class="flex items-center justify-between text-[11px] font-bold">
                            <span class="text-ink-soft">{{ $step['label'] }}</span>
                            <span>{{ $step['value'] }}</span>
                        </div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-paper-deep">
                            <div class="h-full rounded-full {{ $step['color'] }}" style="width: {{ min(100, round($step['value'] / $step['of'] * 100)) }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="mt-4 rounded-2xl bg-paper/60 px-3 py-2 text-[10px] leading-relaxed text-muted">Вовлечённость: {{ $stats['involved'] }}% студентов завершили хотя бы одно упражнение.</p>
        </section>
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-[1.4fr_1fr]">
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-line/70 px-5 py-4">
                <div>
                    <p class="section-eyebrow">Модули</p>
                    <h2 class="mt-1 text-lg font-extrabold">Прохождение</h2>
                </div>
                <a href="{{ route('admin.trainers.index') }}" class="text-xs font-bold text-brand">Тренажёры →</a>
            </div>
            <div class="divide-y divide-line/60">
                @foreach($modules as $row)
                    <div class="flex items-center gap-4 px-5 py-3.5">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold">{{ $row['module']->title }}</p>
                            <p class="mt-0.5 text-[11px] font-semibold text-muted">{{ $row['trainers'] }} {{ \App\Support\Text::plural($row['trainers'], 'тренажёр', 'тренажёра', 'тренажёров') }} · {{ $row['students'] }} {{ \App\Support\Text::plural($row['students'], 'студент', 'студента', 'студентов') }}</p>
                        </div>
                        <div class="w-28">
                            <div class="h-1.5 overflow-hidden rounded-full bg-paper-deep">
                                <div class="h-full rounded-full bg-brand" style="width: {{ min(100, $row['completed'] * 4) }}%;"></div>
                            </div>
                        </div>
                        <span class="w-12 text-right text-xs font-extrabold text-ink">{{ $row['completed'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="section-eyebrow">Экспорт</p>
                    <h2 class="mt-1 text-lg font-extrabold">Данные</h2>
                </div>
                <x-icon name="download" class="h-5 w-5 text-muted" />
            </div>
            <div class="mt-4 grid gap-2">
                @foreach([
                    'admin.export.users' => 'Пользователи',
                    'admin.export.progress' => 'Прогресс студентов',
                    'admin.export.trainer-attempts' => 'Попытки тренажёров',
                    'admin.export.test-attempts' => 'Результаты тестов',
                    'admin.export.feedback' => 'Оценки тренажёров',
                    'admin.export.groups' => 'Сводка по группам',
                    'admin.export.theory' => 'Прохождение теории',
                    'admin.export.shorts' => 'Статистика shorts',
                    'admin.export.certificates' => 'Сертификаты',
                ] as $route => $label)
                    <a href="{{ route($route) }}" class="flex items-center justify-between rounded-2xl border border-line bg-surface px-3.5 py-2.5 text-xs font-bold transition hover:border-brand/30">
                        {{ $label }} <span class="text-muted">CSV</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        <section class="card overflow-hidden">
            <div class="border-b border-line/70 px-5 py-4">
                <p class="section-eyebrow">Практика</p>
                <h2 class="mt-1 text-lg font-extrabold">Последние тренажёры</h2>
            </div>
            <div class="divide-y divide-line/60">
                @forelse($recentTrainerAttempts as $attempt)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-paper text-[10px] font-extrabold {{ $attempt->passed ? 'text-mint-ink' : 'text-amber-ink' }}">{{ (int) $attempt->percent }}%</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold">{{ $attempt->user?->name }}</p>
                            <p class="truncate text-[10px] text-muted">{{ $attempt->trainer?->title }} · {{ $attempt->completed_at?->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-6 text-xs text-muted">Пока нет завершённых попыток.</p>
                @endforelse
            </div>
        </section>

        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-line/70 px-5 py-4">
                <div>
                    <p class="section-eyebrow">Отзывы</p>
                    <h2 class="mt-1 text-lg font-extrabold">Последние оценки</h2>
                </div>
                <a href="{{ route('admin.feedback.index') }}" class="text-xs font-bold text-brand">Все →</a>
            </div>
            <div class="divide-y divide-line/60">
                @forelse($recentFeedback as $item)
                    <div class="px-5 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate text-xs font-bold">{{ $item->trainer?->title }}</p>
                            <span class="chip {{ $item->rating < 3 ? 'border-coral/20 bg-coral-soft text-coral-ink' : 'border-mint/20 bg-mint-soft text-mint-ink' }}">{{ $item->rating }} ★</span>
                        </div>
                        <p class="mt-0.5 text-[10px] text-muted">{{ $item->user?->name }} · {{ $item->created_at?->diffForHumans() }}</p>
                        @if($item->comment)
                            <p class="mt-1 line-clamp-2 text-[11px] leading-relaxed text-ink-soft">{{ $item->comment }}</p>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-6 text-xs text-muted">Оценок пока нет.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection

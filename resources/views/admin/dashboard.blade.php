@extends('layouts.admin')

@section('title', 'Обзор')

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="section-eyebrow">Админка</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight">Обзор</h1>
            <p class="mt-1 text-sm text-muted">Сводка по пользователям, диагностике и практике.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.analytics.index') }}" class="btn-secondary px-4 py-2.5 text-xs">Аналитика</a>
            <a href="{{ route('admin.export.users') }}" class="btn-primary px-4 py-2.5 text-xs">Выгрузить CSV</a>
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
            <div class="mt-4 space-y-2">
                <a href="{{ route('admin.export.users') }}" class="flex items-center justify-between rounded-2xl border border-line bg-surface px-3.5 py-3 text-xs font-bold transition hover:border-brand/30">
                    Пользователи <span class="text-muted">CSV</span>
                </a>
                <a href="{{ route('admin.export.trainer-attempts') }}" class="flex items-center justify-between rounded-2xl border border-line bg-surface px-3.5 py-3 text-xs font-bold transition hover:border-brand/30">
                    Попытки тренажёров <span class="text-muted">CSV</span>
                </a>
                <a href="{{ route('admin.export.test-attempts') }}" class="flex items-center justify-between rounded-2xl border border-line bg-surface px-3.5 py-3 text-xs font-bold transition hover:border-brand/30">
                    Результаты тестов <span class="text-muted">CSV</span>
                </a>
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
            <div class="border-b border-line/70 px-5 py-4">
                <p class="section-eyebrow">Диагностика</p>
                <h2 class="mt-1 text-lg font-extrabold">Последние тесты</h2>
            </div>
            <div class="divide-y divide-line/60">
                @forelse($recentTestAttempts as $attempt)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-paper text-[10px] font-extrabold text-brand">{{ (int) $attempt->overall }}%</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold">{{ $attempt->user?->name }}</p>
                            <p class="truncate text-[10px] text-muted">{{ $attempt->test?->title }} · {{ $attempt->finished_at?->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-6 text-xs text-muted">Пока нет завершённых тестов.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection

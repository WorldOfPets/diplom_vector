@extends('layouts.app')

@section('title', 'Профиль')

@php
    $branchValues = $branches->pluck('value')->map(fn ($value) => $value === null ? 0 : (float) $value)->all();
    $radarMax = \App\Support\Chart::radarMax($branchValues);
    $radarConfig = [
        'type' => 'radar',
        'data' => [
            'labels' => $branches->pluck('label')->all(),
            'datasets' => [[
                'label' => 'Профиль',
                'data' => $branchValues,
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
                    'max' => $radarMax,
                    'ticks' => ['display' => false, 'stepSize' => max(1, (int) round($radarMax / 4))],
                    'grid' => ['color' => 'rgba(139,147,163,0.22)'],
                    'angleLines' => ['color' => 'rgba(139,147,163,0.22)'],
                    'pointLabels' => ['color' => '#8B93A3', 'font' => ['size' => 10, 'weight' => '600']],
                ],
            ],
        ],
    ];

    $trendConfig = [
        'type' => 'line',
        'data' => [
            'labels' => $attemptTrend->pluck('label')->all(),
            'datasets' => [[
                'label' => 'Общий балл',
                'data' => $attemptTrend->pluck('value')->all(),
                'borderColor' => '#2BB673',
                'backgroundColor' => 'rgba(43, 182, 115, 0.10)',
                'fill' => true,
                'tension' => 0.35,
                'pointRadius' => 3,
                'pointBackgroundColor' => '#2BB673',
            ]],
        ],
        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['grid' => ['display' => false], 'ticks' => ['color' => '#8B93A3', 'font' => ['size' => 9]]],
                'y' => ['beginAtZero' => true, 'max' => 100, 'ticks' => ['display' => false], 'grid' => ['color' => 'rgba(139,147,163,0.22)']],
            ],
        ],
    ];

    $motivation = $motivationAttempts->first();
@endphp

@section('content')
    @php
        $currentTheme = \App\Support\Preferences::theme($user);
        $currentTone = \App\Support\Preferences::tone($user);
    @endphp

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div class="flex items-center gap-4">
            @if($user->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="" class="h-14 w-14 rounded-3xl object-cover" referrerpolicy="no-referrer">
            @else
                <span class="grid h-14 w-14 place-items-center rounded-3xl bg-brand-soft text-lg font-extrabold text-brand-deep">{{ $user->initials() }}</span>
            @endif
            <div>
                <p class="section-eyebrow">Личный профиль</p>
                <h1 class="mt-1 text-3xl font-extrabold tracking-tight">{{ tone('profile.index.title') }}</h1>
                <p class="mt-1 text-xs font-semibold text-muted">
                    {{ $user->email }}
                    · {{ $user->group?->name ?? 'Без группы' }}{{ $user->course ? ' · '.$user->course.' курс' : '' }}{{ $user->specialty ? ' · '.$user->specialty : '' }}
                </p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('progress.index') }}" class="btn-secondary px-4 py-2.5 text-xs">{{ tone('common.progress') }}</a>
            <a href="{{ route('tests.index') }}" class="btn-secondary px-4 py-2.5 text-xs">{{ tone('common.to_diagnostics') }}</a>
        </div>
    </div>

    <div class="mt-5 overflow-hidden rounded-3xl border border-line/60">
        <x-photo path="media/photos/campus-path.jpg" credit="overlay" class="h-32 sm:h-40" img-class="h-full w-full object-cover" />
    </div>

    <section id="settings" class="mt-7 scroll-mt-24">
        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
            <div>
                <p class="section-eyebrow">Настройки</p>
                <h2 class="mt-1 text-2xl font-extrabold tracking-tight">{{ tone('profile.index.settings_title') }}</h2>
                <p class="mt-1 text-sm leading-relaxed text-muted">{{ tone('profile.index.settings_lead') }}</p>
            </div>
            <span class="chip self-start sm:self-auto">Тема: {{ $currentTheme === 'dark' ? 'тёмная' : 'светлая' }} · Стиль: {{ $currentTone === 'vibe' ? 'вайбовый' : 'профессиональный' }}</span>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <form method="POST" action="{{ route('profile.update') }}" class="card p-5">
                @csrf
                @method('PUT')
                <p class="section-eyebrow">{{ tone('profile.index.personal_title') }}</p>
                <h3 class="mt-1 text-lg font-extrabold">{{ tone('profile.index.personal_heading') }}</h3>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="label" for="name">Как к вам обращаться</label>
                        <input class="input" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="label" for="group_id">Группа <span class="normal-case tracking-normal text-muted">— необязательно</span></label>
                            <select class="input" id="group_id" name="group_id">
                                <option value="">Выберите группу</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}" @selected((int) old('group_id', $user->group_id) === $group->id)>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label" for="course">Курс <span class="normal-case tracking-normal text-muted">— необязательно</span></label>
                            <select class="input" id="course" name="course">
                                <option value="">Выберите курс</option>
                                @for($course = 1; $course <= 4; $course++)
                                    <option value="{{ $course }}" @selected((int) old('course', $user->course) === $course)>{{ $course }} курс</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="label" for="specialty">Специальность <span class="normal-case tracking-normal text-muted">— необязательно</span></label>
                        <input class="input" id="specialty" name="specialty" value="{{ old('specialty', $user->specialty) }}" placeholder="Например, 09.02.07 Информационные системы и программирование">
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-[10px] text-muted">Email привязан к Яндекс ID и не меняется.</p>
                    <button type="submit" class="btn-primary px-4 py-2.5 text-xs">Сохранить данные</button>
                </div>
            </form>

            <form method="POST" action="{{ route('preferences.store') }}" class="card p-5" x-data="{ theme: '{{ $currentTheme }}' }">
                @csrf
                <p class="section-eyebrow">{{ tone('profile.index.appearance_title') }}</p>
                <h3 class="mt-1 text-lg font-extrabold">Тема и стиль текста</h3>
                <p class="mt-1 text-xs leading-relaxed text-muted">{{ tone('preferences.profile.theme_hint') }}</p>

                <fieldset class="mt-4">
                    <legend class="label">Тема</legend>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="theme" value="light" x-model="theme" @change="window.applyVektorTheme($event.target.value)" class="sr-only">
                            <div class="rounded-2xl border p-2 transition" :class="theme === 'light' ? 'border-brand ring-4 ring-brand/10' : 'border-line hover:border-brand/30'">
                                <div data-theme="light" class="rounded-xl border border-line/60 bg-paper p-2.5">
                                    <div class="h-1.5 w-7 rounded-full bg-brand"></div>
                                    <div class="mt-2 h-1 w-full rounded-full bg-line"></div>
                                    <div class="mt-1 h-1 w-2/3 rounded-full bg-line"></div>
                                </div>
                                <p class="mt-2 text-center text-xs font-bold">Светлая</p>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="theme" value="dark" x-model="theme" @change="window.applyVektorTheme($event.target.value)" class="sr-only">
                            <div class="rounded-2xl border p-2 transition" :class="theme === 'dark' ? 'border-brand ring-4 ring-brand/10' : 'border-line hover:border-brand/30'">
                                <div data-theme="dark" class="rounded-xl border border-line/60 bg-paper p-2.5">
                                    <div class="h-1.5 w-7 rounded-full bg-brand"></div>
                                    <div class="mt-2 h-1 w-full rounded-full bg-line"></div>
                                    <div class="mt-1 h-1 w-2/3 rounded-full bg-line"></div>
                                </div>
                                <p class="mt-2 text-center text-xs font-bold">Тёмная</p>
                            </div>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="mt-5">
                    <legend class="label">Стиль текста</legend>
                    <div class="space-y-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border p-3 transition {{ $currentTone === 'vibe' ? 'border-brand bg-brand-soft/40' : 'border-line' }}">
                            <input type="radio" name="tone" value="vibe" class="mt-0.5 h-4 w-4 border-line text-brand focus:ring-brand" @checked($currentTone === 'vibe')>
                            <span>
                                <span class="block text-sm font-bold">Вайбовый</span>
                                <span class="mt-0.5 block text-[11px] leading-relaxed text-muted">Короче, живее и с шутками. Для студентов.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border p-3 transition {{ $currentTone === 'professional' ? 'border-brand bg-brand-soft/40' : 'border-line' }}">
                            <input type="radio" name="tone" value="professional" class="mt-0.5 h-4 w-4 border-line text-brand focus:ring-brand" @checked($currentTone === 'professional')>
                            <span>
                                <span class="block text-sm font-bold">Профессиональный</span>
                                <span class="mt-0.5 block text-[11px] leading-relaxed text-muted">Спокойно, точно и по делу.</span>
                            </span>
                        </label>
                    </div>
                </fieldset>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-[10px] text-muted">{{ tone('preferences.profile.tone_hint') }}</p>
                    <button type="submit" class="btn-primary px-4 py-2.5 text-xs">Сохранить настройки</button>
                </div>
            </form>
        </div>
    </section>

    <div class="mt-7">
        <p class="section-eyebrow">{{ tone('profile.index.results_title') }}</p>
        <h2 class="mt-1 text-2xl font-extrabold tracking-tight">{{ tone('profile.index.results_heading') }}</h2>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-2.5 lg:grid-cols-4">
        <x-stat label="ЭИ сейчас" :value="$latestEi ? ((int) $latestEi->overall).'%' : '—'" hint="{{ $latestEi?->test->title ?? 'Пройдите диагностику' }}" icon="chart" tone="brand" />
        <x-stat label="Тренажёры" :value="$trainerStats['completed']" hint="завершено упражнений" icon="sparkles" tone="mint" />
        <x-stat label="Средний результат" :value="$trainerStats['average'] ? $trainerStats['average'].'%' : '—'" hint="по завершённым" icon="target" tone="coral" />
        <x-stat label="Дневник" :value="$diaryStats['days']" :hint="$diaryStats['days'].' '.\App\Support\Text::plural($diaryStats['days'], 'день', 'дня', 'дней').' с записями'" icon="calendar" tone="amber" />
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-[1.4fr_1fr]">
        <div class="min-w-0 space-y-5">
            <section class="card p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="section-eyebrow">Эмоциональный интеллект</p>
                        <h2 class="mt-1 text-xl font-extrabold">Профиль по ветвям</h2>
                    </div>
                    @if($latestEi)
                        <x-level-badge :level="$latestEi->level ?? 'average'" />
                    @endif
                </div>

                @if(! $latestEi)
                    <x-empty-state icon="chart" title="Диагностика ещё не пройдена" text="Пройдите один из трёх форматов, чтобы увидеть профиль." class="mt-4">
                        <a href="{{ route('tests.index') }}" class="btn-primary px-4 py-2.5 text-xs">Выбрать тест</a>
                    </x-empty-state>
                @else
                    <div class="mt-4 grid gap-5 sm:grid-cols-[0.9fr_1.1fr] sm:items-center">
                        <div>
                            <div class="h-64">
                                <canvas x-data="chartCanvas(@js($radarConfig))"></canvas>
                            </div>
                            <p class="mt-1 text-center text-[10px] font-semibold text-muted">шкала 0–{{ $radarMax }}%</p>
                        </div>
                        <div class="space-y-3">
                            @foreach($branches as $branch)
                                <div>
                                    <div class="flex items-center justify-between text-xs font-bold">
                                        <span class="text-ink-soft">{{ $branch['label'] }}</span>
                                        <span style="color: {{ $branch['color'] }};">{{ $branch['value'] !== null ? (int) $branch['value'].'%' : '—' }}</span>
                                    </div>
                                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-paper-deep">
                                        <div class="h-full rounded-full" style="width: {{ $branch['value'] ?? 0 }}%; background: {{ $branch['color'] }};"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <p class="mt-4 text-[11px] font-semibold text-muted">{{ $latestEi->test->title }} · {{ $latestEi->finished_at?->translatedFormat('j F Y') }}</p>
                    <a href="{{ route('tests.result', $latestEi) }}" class="btn-ghost mt-1 px-0 text-xs text-brand hover:bg-transparent">Открыть разбор →</a>
                @endif
            </section>

            @if($attemptTrend->count() > 1)
                <section class="card p-5 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="section-eyebrow">Динамика</p>
                            <h2 class="mt-1 text-xl font-extrabold">Все попытки</h2>
                        </div>
                        <span class="chip">{{ $attemptTrend->count() }} {{ \App\Support\Text::plural($attemptTrend->count(), 'срез', 'среза', 'срезов') }}</span>
                    </div>
                    <div class="mt-4 h-44">
                        <canvas x-data="chartCanvas(@js($trendConfig))"></canvas>
                    </div>
                </section>
            @endif

            <section class="card p-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="section-eyebrow">Практика</p>
                        <h2 class="mt-1 text-xl font-extrabold">Пройденные тренажёры</h2>
                    </div>
                    <span class="chip">{{ $trainerStats['passed'] }} засчитано</span>
                </div>

                @if($trainerAttempts->isEmpty())
                    <x-empty-state icon="sparkles" title="Пока нет завершённых тренажёров" text="Начните с модуля «Хочу и делаю»." class="mt-4">
                        <a href="{{ route('modules.index') }}" class="btn-primary px-4 py-2.5 text-xs">К модулям</a>
                    </x-empty-state>
                @else
                    <div class="mt-4 space-y-2">
                        @foreach($trainerAttempts as $attempt)
                            <a href="{{ route('trainers.result', $attempt) }}" class="flex items-center gap-3 rounded-2xl border border-line/70 bg-paper/40 px-3.5 py-3 transition hover:border-brand/30">
                                <span class="grid h-9 w-9 place-items-center rounded-xl bg-surface text-[11px] font-extrabold {{ $attempt->passed ? 'text-mint-ink' : 'text-amber-ink' }}">{{ (int) $attempt->percent }}%</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold">{{ $attempt->trainer->title }}</p>
                                    <p class="truncate text-[10px] font-semibold text-muted">{{ $attempt->trainer->module->title }} · {{ $attempt->completed_at?->translatedFormat('j F') }}</p>
                                </div>
                                <x-icon name="chevron-right" class="h-4 w-4 text-muted" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <aside class="min-w-0 space-y-5">
            @if($motivation)
                <section class="card p-5">
                    <div class="flex items-center justify-between">
                        <p class="section-eyebrow">Мотивация</p>
                        <span class="text-sm font-extrabold text-amber-ink">{{ (int) $motivation->overall }}%</span>
                    </div>
                    <h2 class="mt-1 text-lg font-extrabold">{{ $motivation->test->title }}</h2>
                    <div class="mt-3 space-y-2">
                        @foreach(($motivation->scores ?? []) as $key => $value)
                            <div>
                                <div class="flex items-center justify-between text-[11px] font-bold">
                                    <span class="text-ink-soft">{{ $motivation->test->config['subscales'][$key] ?? $key }}</span>
                                    <span class="text-amber-ink">{{ (int) $value }}%</span>
                                </div>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-paper-deep">
                                    <div class="h-full rounded-full bg-amber" style="width: {{ (int) $value }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('tests.result', $motivation) }}" class="btn-ghost mt-2 px-0 text-xs text-amber-ink hover:bg-transparent">Разбор →</a>
                </section>
            @else
                <section class="card p-5">
                    <p class="section-eyebrow">Мотивация</p>
                    <h2 class="mt-1 text-lg font-extrabold">Ещё не измерена</h2>
                    <p class="mt-2 text-xs leading-relaxed text-muted">Пройдите короткий опросник, чтобы увидеть внутреннюю и внешнюю мотивацию.</p>
                    <a href="{{ route('tests.index') }}" class="btn-secondary mt-3 w-full py-2.5 text-xs">Пройти</a>
                </section>
            @endif

            <section class="card p-5">
                <p class="section-eyebrow">Эмоции</p>
                <h2 class="mt-1 text-lg font-extrabold">Что чаще всего</h2>
                @if($emotionCounts->isEmpty())
                    <p class="mt-2 text-xs leading-relaxed text-muted">Пока нет записей в дневнике.</p>
                @else
                    <div class="mt-3 space-y-2">
                        @foreach($emotionCounts as $emotion)
                            <div class="flex items-center justify-between text-xs">
                                <span class="flex items-center gap-2 font-semibold text-ink-soft">
                                    <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $emotion['color'] }};"></span>
                                    {{ $emotion['label'] }}
                                </span>
                                <span class="font-extrabold text-muted">{{ $emotion['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                <a href="{{ route('diary.index') }}" class="btn-ghost mt-2 px-0 text-xs text-brand hover:bg-transparent">Открыть дневник →</a>
            </section>

            <section class="rounded-3xl border border-line/60 bg-surface/50 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-muted">Как читать</p>
                <p class="mt-2 text-xs leading-relaxed text-ink-soft">Проценты — это уровень освоения навыка, а не «качество личности». Смотрите на динамику и на то, что меняется в реальных ситуациях.</p>
            </section>
        </aside>
    </div>
@endsection

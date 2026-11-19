@extends('layouts.app')

@section('title', $test->title)

@php
    $kind = \App\Enums\TestKind::tryFrom($test->kind);
    $isMotivation = $test->kind === 'motivation';
    $cover = [
        'ei_full' => 'media/photos/exam-writing.jpg',
        'ei_situational' => 'media/photos/discussion.jpg',
        'ei_express' => 'media/photos/focus.jpg',
        'motivation' => 'media/photos/planner.jpg',
    ][$test->kind] ?? null;
    $branches = $isMotivation
        ? collect($test->config['subscales'] ?? [
            'internal' => 'Внутренняя мотивация',
            'external' => 'Внешняя мотивация',
            'professional' => 'Профессиональная направленность',
        ])->map(fn ($label) => ['label' => $label])->values()
        : collect($test->config['branches'] ?? array_map(fn ($branch) => $branch->value, \App\Enums\EiBranch::eiBranches()))
            ->map(fn ($key) => \App\Enums\EiBranch::from($key))
            ->map(fn ($branch) => ['label' => $branch->label(), 'color' => $branch->color()])
            ->values();
@endphp

@section('content')
    <nav class="flex items-center gap-1.5 text-[11px] font-bold text-muted" aria-label="Хлебные крошки">
        <a href="{{ route('tests.index') }}" class="hover:text-ink">Диагностика</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <span class="text-ink-soft">{{ $test->title }}</span>
    </nav>

    <div class="mt-4 grid gap-5 lg:grid-cols-[1.55fr_1fr]">
        <div class="space-y-5">
            <section class="card relative overflow-hidden p-6 sm:p-7">
                <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full {{ $isMotivation ? 'bg-amber-soft' : 'bg-brand-soft' }}"></div>
                @if($cover)
                    <div class="-mx-6 -mt-6 mb-5 h-44 overflow-hidden sm:-mx-7 sm:-mt-7">
                        <x-photo :path="$cover" credit="overlay" class="h-full" img-class="h-full w-full object-cover" loading="eager" />
                    </div>
                @endif
                <div class="relative">
                    <span class="chip {{ $isMotivation ? 'border-amber/20 bg-amber-soft text-amber-ink' : 'border-brand/20 bg-brand-soft text-brand-deep' }}">
                        {{ $kind?->label() ?? 'Диагностика' }}
                    </span>
                    <h1 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $test->title }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">{{ $test->subtitle }}</p>
                    @if($test->description)
                        <p class="mt-4 max-w-2xl text-sm leading-relaxed text-ink-soft">{{ $test->description }}</p>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="chip">{{ $itemsCount }} {{ \App\Support\Text::plural($itemsCount, 'задание', 'задания', 'заданий') }}</span>
                        <span class="chip"><x-icon name="clock" class="h-3 w-3" /> {{ $test->time_limit_min }} минут</span>
                        <span class="chip"><x-icon name="shield" class="h-3 w-3 text-mint-ink" /> ключ на сервере</span>
                    </div>

                    <div class="mt-6 flex flex-col gap-2 sm:flex-row">
                        @if($inProgress)
                            <a href="{{ route('tests.take', $inProgress) }}" class="btn-primary">
                                Продолжить <x-icon name="arrow-right" class="h-4 w-4" />
                            </a>
                            <span class="text-xs leading-relaxed text-muted sm:self-center">{{ tone('tests.show.draft') }}</span>
                        @elseif($itemsCount === 0)
                            <span class="chip border-amber/20 bg-amber-soft text-amber-ink">Задания ещё не добавлены</span>
                        @else
                            <form method="POST" action="{{ route('tests.start', $test) }}">
                                @csrf
                                @if($latest)
                                    <input type="hidden" name="retake" value="1">
                                @endif
                                <button type="submit" class="btn-primary">
                                    {{ $latest ? tone('tests.show.retake') : 'Начать' }}
                                    <x-icon name="arrow-right" class="h-4 w-4" />
                                </button>
                            </form>
                        @endif

                        @if($latest)
                            <a href="{{ route('tests.result', $latest) }}" class="btn-secondary">{{ tone('tests.show.last_result') }}</a>
                        @endif
                    </div>
                </div>
            </section>

            @if($latest)
                <section class="card p-5">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="section-eyebrow">Последняя попытка</p>
                            <p class="mt-1 text-lg font-extrabold">{{ (int) $latest->overall }}% · {{ \App\Support\Levels::label($latest->level ?? 'average') }}</p>
                            <p class="mt-1 text-xs text-muted">{{ $latest->finished_at?->translatedFormat('j F, H:i') }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('tests.result', $latest) }}" class="btn-secondary px-4 py-2 text-xs">Разбор</a>
                            <a href="{{ route('tests.history', $test) }}" class="btn-ghost px-3 py-2 text-xs">История</a>
                        </div>
                    </div>
                </section>
            @endif

            <section class="card p-5 sm:p-6">
                <p class="section-eyebrow">Что измеряет</p>
                <h2 class="mt-1 text-xl font-extrabold">Не «насколько вы эмоциональны», а что вы умеете делать</h2>
                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    @foreach($branches as $branch)
                        <div class="flex items-center gap-2 rounded-2xl bg-paper/60 px-3.5 py-3 text-sm font-semibold text-ink-soft">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $branch['color'] ?? ($isMotivation ? '#D97706' : '#2D5BFF') }};"></span>
                            {{ $branch['label'] }}
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-xs leading-relaxed text-muted">
                    {{ $isMotivation ? 'Это самоотчётный опросник: он показывает, как вы сами оцениваете свою мотивацию. Внешняя мотивация не «хуже» внутренней — она может поддерживать, а может давить. Рядом можно смотреть цели, дневник и завершённые тренажёры — это отдельные показатели, они не входят в балл теста.' : 'Это тренировочный тест с экспертным ключом: задания оценивают конкретные навыки, а не мнение о себе. Ключ задаёт автор материалов; это не стандартизированная диагностика и не нормативная шкала.' }}
                </p>
            </section>
        </div>

        <aside class="space-y-4">
            <div class="card p-5">
                <p class="section-eyebrow">Правила</p>
                <ul class="mt-3 space-y-2.5 text-xs leading-relaxed text-ink-soft">
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-mint-ink" /> Отвечайте за себя, а не «как правильно».</li>
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-mint-ink" /> Не ищите подвох: ключ не показывается.</li>
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-mint-ink" /> Можно прерваться — черновик сохраняется.</li>
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-mint-ink" /> Результат — про навыки, не про личность.</li>
                </ul>
            </div>

            <div class="rounded-3xl border border-line/60 bg-surface/50 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-muted">Честно</p>
                <p class="mt-2 text-xs leading-relaxed text-ink-soft">Это тренировочный, а не клинический инструмент. Он проходит пилот и калибровку: если задание кажется неоднозначным — это важный сигнал, а не ваша ошибка.</p>
                <p class="mt-2 text-xs leading-relaxed text-muted">Фотографии — иллюстрации ситуаций, а не стандартизированные диагностические стимулы. Люди на них не являются участниками исследования. Оценка интенсивности не входит в балл: она нужна для рефлексии.</p>
            </div>

            <div class="card p-5">
                <p class="section-eyebrow">После теста</p>
                <p class="mt-2 text-xs leading-relaxed text-ink-soft">Вы получите профиль по ветвям и рекомендации, с какого модуля начать. Без публичных рейтингов.</p>
            </div>
        </aside>
    </div>
@endsection

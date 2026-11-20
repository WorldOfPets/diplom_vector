@extends('layouts.app')

@section('title', 'Главная')

@php
    $user = auth()->user();
    $firstName = \Illuminate\Support\Str::of($user->name)->explode(' ')->first();
    $palette = [
        'blue' => ['soft' => 'bg-brand-soft', 'text' => 'text-brand-deep', 'ring' => '#2D5BFF'],
        'lilac' => ['soft' => 'bg-lilac-soft', 'text' => 'text-lilac', 'ring' => '#7C5CFF'],
        'coral' => ['soft' => 'bg-coral-soft', 'text' => 'text-coral', 'ring' => '#FF6B4A'],
        'mint' => ['soft' => 'bg-mint-soft', 'text' => 'text-mint-ink', 'ring' => '#2BB673'],
        'amber' => ['soft' => 'bg-amber-soft', 'text' => 'text-amber-ink', 'ring' => '#D97706'],
    ];
    $hasEi = $tests->contains(fn ($test) => $test->kind !== 'motivation' && $test->latest_attempt !== null);
    $diagCovers = $tests
        ->mapWithKeys(fn ($test): array => [$test->kind => \App\Support\SlideImages::test($test->kind)])
        ->all();
@endphp

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div>
            <p class="section-eyebrow">{{ now()->translatedFormat('j F, l') }}</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Привет, {{ $firstName }}</h1>
            <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted">
                {{ $hasEi ? tone('dashboard.greeting.has_ei') : tone('dashboard.greeting.new') }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2 rounded-2xl border border-line bg-surface px-3.5 py-2.5">
                <x-icon name="flame" class="h-4 w-4 text-coral" />
                <span class="text-sm font-extrabold">{{ $streak }}</span>
                <span class="text-[11px] font-semibold text-muted">{{ \App\Support\Text::plural($streak, 'день', 'дня', 'дней') }} · {{ tone('dashboard.streak') }}</span>
            </div>
            <a href="{{ route('diary.index') }}" class="grid h-10 w-10 place-items-center rounded-2xl bg-inverse text-white" title="Записать эмоцию">                <x-icon name="plus" class="h-4 w-4" />
            </a>
        </div>
    </div>

    <a href="{{ route('progress.index') }}" class="card mt-5 flex items-center gap-3 p-4 transition hover:border-brand/30 sm:gap-4 sm:p-5">
        <x-progress-ring :value="$contentProgress['percent']" :size="62" :stroke="6" color="#2D5BFF" />
        <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-extrabold">{{ tone('dashboard.progress.title') }}</p>
                <span class="text-sm font-extrabold text-brand">{{ (int) round($contentProgress['percent']) }}%</span>
            </div>
            <p class="mt-1 text-[11px] leading-relaxed text-muted">{{ tone('dashboard.progress.hint', ['done' => $contentProgress['done'], 'total' => $contentProgress['total']]) }}</p>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-paper-deep">
                <div class="h-full rounded-full bg-brand" style="width: {{ $contentProgress['percent'] }}%;"></div>
            </div>
            <p class="mt-1 text-[10px] font-semibold {{ $contentProgress['certificate_ready'] ? 'text-mint-ink' : 'text-muted' }}">
                {{ $contentProgress['certificate_ready'] ? tone('dashboard.progress.ready') : tone('dashboard.progress.remaining', ['remaining' => $contentProgress['remaining']]) }}
            </p>
        </div>
        <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-muted" />
    </a>

    <section id="guide" class="mt-5 scroll-mt-24 overflow-hidden rounded-[2rem] border border-line/70 bg-surface shadow-soft">
        <div class="relative p-5 sm:p-7">
            <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-brand-soft/80"></div>
            <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div class="max-w-2xl">
                    <p class="section-eyebrow">{{ tone('dashboard.guide.eyebrow') }}</p>
                    <h2 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ tone('dashboard.guide.title') }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ tone('dashboard.guide.lead') }}</p>
                </div>
                <a href="{{ route('tests.index') }}" class="btn-primary shrink-0 px-4 py-2.5 text-xs">{{ tone('dashboard.guide.cta') }}</a>
            </div>

            <div class="relative mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach([
                    ['n' => '01', 'icon' => 'chart', 'title' => tone('dashboard.guide.step1.title'), 'text' => tone('dashboard.guide.step1.text'), 'url' => route('tests.index')],
                    ['n' => '02', 'icon' => 'play', 'title' => tone('dashboard.guide.step2.title'), 'text' => tone('dashboard.guide.step2.text'), 'url' => route('shorts.index')],
                    ['n' => '03', 'icon' => 'sparkles', 'title' => tone('dashboard.guide.step3.title'), 'text' => tone('dashboard.guide.step3.text'), 'url' => route('modules.index')],
                    ['n' => '04', 'icon' => 'camera', 'title' => tone('dashboard.guide.step4.title'), 'text' => tone('dashboard.guide.step4.text'), 'url' => route('camera.show')],
                    ['n' => '05', 'icon' => 'calendar', 'title' => tone('dashboard.guide.step5.title'), 'text' => tone('dashboard.guide.step5.text'), 'url' => route('diary.index')],
                    ['n' => '06', 'icon' => 'target', 'title' => tone('dashboard.guide.step6.title'), 'text' => tone('dashboard.guide.step6.text'), 'url' => route('progress.index')],
                ] as $step)
                    <a href="{{ $step['url'] }}" class="group rounded-2xl border border-line/70 bg-paper/50 p-4 transition hover:border-brand/30 hover:bg-brand-soft/30">
                        <div class="flex items-center justify-between gap-2">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-surface text-brand shadow-sm">
                                <x-icon :name="$step['icon']" class="h-4 w-4" />
                            </span>
                            <span class="text-[10px] font-extrabold tracking-[0.16em] text-muted">{{ $step['n'] }}</span>
                        </div>
                        <p class="mt-3 text-sm font-extrabold leading-snug group-hover:text-brand-deep">{{ $step['title'] }}</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-muted">{{ $step['text'] }}</p>
                    </a>
                @endforeach
            </div>

            <div class="relative mt-5 grid gap-2 sm:grid-cols-2">
                @foreach([
                    ['q' => tone('dashboard.guide.faq1.q'), 'a' => tone('dashboard.guide.faq1.a')],
                    ['q' => tone('dashboard.guide.faq2.q'), 'a' => tone('dashboard.guide.faq2.a')],
                    ['q' => tone('dashboard.guide.faq3.q'), 'a' => tone('dashboard.guide.faq3.a')],
                    ['q' => tone('dashboard.guide.faq4.q'), 'a' => tone('dashboard.guide.faq4.a')],
                ] as $faq)
                    <details class="group rounded-2xl border border-line/60 bg-paper/40 px-4 py-3">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-xs font-bold text-ink-soft">
                            {{ $faq['q'] }}
                            <x-icon name="chevron-right" class="h-3.5 w-3.5 shrink-0 text-muted transition group-open:rotate-90" />
                        </summary>
                        <p class="mt-2 text-[11px] leading-relaxed text-muted">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>

            <p class="relative mt-4 text-[10px] font-semibold text-muted">{{ tone('dashboard.guide.hint') }}</p>
        </div>
    </section>

    <div class="mt-7 grid gap-5 lg:grid-cols-[1.55fr_1fr]">
        <div class="space-y-5">
            @if(! $hasEi)
                <section class="relative overflow-hidden rounded-3xl bg-inverse p-6 text-white sm:p-7">
                    <div class="absolute -right-8 -top-10 h-40 w-40 rounded-full bg-brand/30 blur-2xl"></div>
                    <div class="relative">
                        <span class="chip border-white/15 bg-white/10 text-white/70">{{ tone('dashboard.first_step.eyebrow') }}</span>
                        <h2 class="mt-3 text-2xl font-extrabold tracking-tight">{{ tone('dashboard.first_step.title') }}</h2>
                        <p class="mt-2 max-w-md text-sm leading-relaxed text-white/60">{{ tone('dashboard.first_step.text') }}</p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <a href="{{ route('tests.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2.5 text-xs font-extrabold text-slate-900 transition hover:bg-white/90">
                                {{ tone('dashboard.first_step.cta') }} <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                            </a>
                            <span class="rounded-full border border-white/15 px-3 py-2 text-[11px] font-bold text-white/70">12–30 минут</span>
                        </div>
                    </div>
                </section>
            @elseif($nextTrainer)
                <section class="card relative overflow-hidden p-6 sm:p-7">
                    <div class="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-brand-soft"></div>
                    <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="section-eyebrow">{{ tone('dashboard.next.eyebrow') }}</p>
                            <h2 class="mt-2 text-2xl font-extrabold tracking-tight">{{ $nextTrainer->title }}</h2>
                            <p class="mt-1 text-sm text-muted">{{ $nextTrainer->module->title }} · {{ $nextTrainer->duration_min }} минут</p>
                            <p class="mt-3 max-w-md text-sm leading-relaxed text-ink-soft">{{ $nextTrainer->subtitle }}</p>
                        </div>
                        <div class="hidden h-24 w-40 shrink-0 overflow-hidden rounded-2xl md:block">
                            <x-photo :path="\App\Support\SlideImages::module($nextTrainer->module->slug, 1)" credit="none" class="h-full" img-class="h-full w-full object-cover" />
                        </div>
                        <a href="{{ route('trainers.show', $nextTrainer) }}" class="btn-primary shrink-0">
                            {{ tone('dashboard.next.cta') }} <x-icon name="play" class="h-4 w-4" />
                        </a>
                    </div>
                </section>
            @else
                <section class="card p-6 sm:p-7">
                    <p class="section-eyebrow">Всё пройдено</p>
                    <h2 class="mt-2 text-2xl font-extrabold">{{ tone('dashboard.done.title') }}</h2>
                    <p class="mt-2 text-sm text-muted">{{ tone('dashboard.done.text') }}</p>
                    <a href="{{ route('profile.index') }}" class="btn-primary mt-4">{{ tone('dashboard.done.cta') }}</a>
                </section>
            @endif

            @if($featuredShort)
                <a href="{{ route('shorts.index') }}" class="card flex items-center gap-3 overflow-hidden p-3 transition hover:border-brand/30">
                    <div class="h-20 w-14 shrink-0 overflow-hidden rounded-2xl bg-inverse">
                        <img src="{{ asset($featuredShort->poster_path) }}" alt="" class="h-full w-full object-cover" loading="lazy">
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted">{{ tone('dashboard.shorts.eyebrow') }}</p>
                        <p class="mt-1 text-sm font-extrabold leading-snug">{{ $featuredShort->title }}</p>
                        <p class="mt-0.5 text-[11px] text-muted">{{ $featuredShort->subtitle }}</p>
                    </div>
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand text-white">
                        <x-icon name="play" class="h-4 w-4" />
                    </span>
                </a>
            @endif

            <section>
                <div class="mb-3 flex items-end justify-between">
                    <div>
                        <p class="section-eyebrow">{{ tone('dashboard.modules.eyebrow') }}</p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight">{{ tone('dashboard.modules.title') }}</h2>
                    </div>
                    <a href="{{ route('modules.index') }}" class="text-xs font-bold text-brand hover:text-brand-deep">{{ tone('dashboard.modules.all') }}</a>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($modules as $module)
                        @php
                            $c = $palette[$module->color] ?? $palette['blue'];
                            $progress = $module->progress ?? 0;
                        @endphp
                        <a href="{{ route('modules.show', $module) }}" class="card group overflow-hidden p-4 transition hover:-translate-y-0.5 hover:shadow-soft">
                            <div class="-mx-4 -mt-4 mb-3 h-24 overflow-hidden">
                                <x-photo :path="\App\Support\SlideImages::module($module->slug, 1)" credit="none" class="h-full" img-class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl {{ $c['soft'] }} {{ $c['text'] }}">
                                    <x-icon :name="$module->icon ?: 'sparkles'" class="h-[18px] w-[18px]" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-extrabold leading-snug">{{ $module->title }}</p>
                                    <p class="mt-0.5 text-[11px] font-semibold text-muted">{{ $module->completed_count }}/{{ $module->trainers->count() }} тренажёров</p>
                                </div>
                                <x-progress-ring :value="$progress" :size="44" :stroke="4" :color="$c['ring']" />
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-paper-deep">
                                <div class="h-full rounded-full" style="width: {{ $progress }}%; background: {{ $c['ring'] }};"></div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            <section>
                <div class="mb-3 flex items-end justify-between">
                    <div>
                        <p class="section-eyebrow">{{ tone('dashboard.tests.eyebrow') }}</p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight">{{ tone('dashboard.tests.title') }}</h2>
                    </div>
                    <a href="{{ route('tests.index') }}" class="text-xs font-bold text-brand hover:text-brand-deep">{{ tone('dashboard.tests.details') }}</a>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach($tests as $test)
                        <a href="{{ route('tests.show', $test) }}" class="card flex flex-col overflow-hidden p-4 transition hover:border-brand/30">
                            @if(! empty($diagCovers[$test->kind]))
                                <div class="-mx-4 -mt-4 mb-3 h-20 overflow-hidden">
                                    <x-photo :path="$diagCovers[$test->kind]" credit="none" class="h-full" img-class="h-full w-full object-cover" />
                                </div>
                            @endif
                            <div class="flex items-center justify-between gap-2">
                                <span class="chip">{{ $test->kind === 'motivation' ? 'Мотивация' : 'ЭИ' }}</span>
                                @if($test->latest_attempt)
                                    <span class="text-[11px] font-extrabold text-mint-ink">{{ (int) $test->latest_attempt->overall }}%</span>
                                @else
                                    <span class="text-[11px] font-bold text-muted">не пройден</span>
                                @endif
                            </div>
                            <p class="mt-3 text-sm font-extrabold leading-snug">{{ $test->title }}</p>
                            <p class="mt-1 text-[11px] font-semibold text-muted">{{ $test->time_limit_min }} минут</p>
                            <div class="mt-3 flex items-center gap-1 text-[11px] font-bold text-brand">
                                {{ $test->latest_attempt ? 'Результат' : 'Начать' }} <x-icon name="chevron-right" class="h-3 w-3" />
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="section-eyebrow">{{ tone('dashboard.diary.eyebrow') }}</p>
                        <h2 class="mt-1 text-lg font-extrabold">{{ tone('dashboard.diary.title') }}</h2>
                    </div>
                    <a href="{{ route('diary.index') }}" class="grid h-9 w-9 place-items-center rounded-2xl bg-paper text-ink-soft transition hover:bg-paper-deep" title="Открыть дневник">
                        <x-icon name="plus" class="h-4 w-4" />
                    </a>
                </div>

                <div class="mt-4 space-y-2">
                    @foreach(['morning' => 'Утро', 'day' => 'День', 'evening' => 'Вечер'] as $slot => $label)
                        @php $entry = $todayEntries->get($slot); @endphp
                        <div class="flex items-center gap-3 rounded-2xl border border-line/70 bg-paper/50 px-3 py-2.5">
                            <span class="grid h-8 w-8 place-items-center rounded-xl bg-surface text-sm">{{ $entry?->emotion?->emoji ?: '·' }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-muted">{{ $label }}</p>
                                <p class="truncate text-xs font-semibold text-ink-soft">{{ $entry?->emotion?->name ?? tone('dashboard.diary.empty') }}</p>
                            </div>
                            @if($entry)
                                <span class="text-[10px] font-bold text-muted">{{ $entry->intensity }}/5</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <a href="{{ route('diary.index') }}" class="btn-secondary mt-4 w-full py-2.5 text-xs">{{ tone('dashboard.diary.cta') }}</a>
            </section>

            <section class="card p-5">
                <p class="section-eyebrow">{{ tone('dashboard.recent.eyebrow') }}</p>
                <h2 class="mt-1 text-lg font-extrabold">{{ tone('dashboard.recent.title') }}</h2>

                @if($recentAttempts->isEmpty())
                    <p class="mt-3 text-xs leading-relaxed text-muted">{{ tone('dashboard.recent.empty') }}</p>
                @else
                    <div class="mt-4 space-y-2.5">
                        @foreach($recentAttempts as $attempt)
                            <a href="{{ route('trainers.result', $attempt) }}" class="flex items-center gap-3 rounded-2xl border border-line/70 bg-paper/50 px-3 py-2.5 transition hover:border-brand/30">
                                <span class="grid h-8 w-8 place-items-center rounded-xl bg-surface text-[11px] font-extrabold {{ $attempt->passed ? 'text-mint-ink' : 'text-amber-ink' }}">
                                    {{ (int) $attempt->percent }}%
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-bold">{{ $attempt->trainer->title }}</p>
                                    <p class="truncate text-[10px] font-semibold text-muted">{{ $attempt->trainer->module->title }}</p>
                                </div>
                                <x-icon name="chevron-right" class="h-3.5 w-3.5 text-muted" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-3xl border border-line/60 bg-surface/50 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-muted">Напоминание</p>
                <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ tone('dashboard.quote') }}</p>
                <p class="mt-3 text-[11px] font-semibold text-muted">Вектор · педагогический инструмент</p>
            </section>
        </aside>
    </div>
@endsection

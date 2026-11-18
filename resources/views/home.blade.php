@php
    $theme = \App\Support\Preferences::theme();
    $tone = \App\Support\Preferences::tone();
    $palette = [
        'blue' => ['soft' => 'bg-brand-soft', 'text' => 'text-brand-deep', 'dot' => 'bg-brand', 'line' => 'border-brand/20'],
        'lilac' => ['soft' => 'bg-lilac-soft', 'text' => 'text-lilac', 'dot' => 'bg-lilac', 'line' => 'border-lilac/20'],
        'coral' => ['soft' => 'bg-coral-soft', 'text' => 'text-coral', 'dot' => 'bg-coral', 'line' => 'border-coral/20'],
        'mint' => ['soft' => 'bg-mint-soft', 'text' => 'text-mint-ink', 'dot' => 'bg-mint', 'line' => 'border-mint/20'],
        'amber' => ['soft' => 'bg-amber-soft', 'text' => 'text-amber-ink', 'dot' => 'bg-amber', 'line' => 'border-amber/20'],
    ];
    $heroPhoto = \App\Support\PhotoLibrary::get('media/photos/hero-campus.jpg');
@endphp
<!DOCTYPE html>
<html lang="ru" class="h-full scroll-smooth" data-theme="{{ $theme }}" data-tone="{{ $tone }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $theme === 'dark' ? '#0b0e14' : '#f7f5f0' }}">
    <title>Вектор — тренажёр мотивации и эмоционального интеллекта</title>
    <meta name="description" content="Цифровой тренажёр для студентов СПО: диагностика эмоционального интеллекта, 43 интерактивных упражнения и личная динамика мотивации.">
    @include('partials.pwa-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full overflow-x-hidden bg-paper">
<div class="pointer-events-none fixed inset-0 -z-10" aria-hidden="true">
    <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-brand/10 blur-3xl"></div>
    <div class="absolute right-0 top-24 h-80 w-80 rounded-full bg-coral/10 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-mint/10 blur-3xl"></div>
    <div class="absolute inset-0 grain opacity-60"></div>
</div>

<header class="sticky top-0 z-40 border-b border-line/60 bg-paper/85 pt-[env(safe-area-inset-top)] backdrop-blur-xl">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-5 py-3.5 sm:px-8">
        <x-logo />
        <nav class="hidden items-center gap-6 text-sm font-semibold text-ink-soft md:flex">
            <a href="#how" class="transition hover:text-ink">Как работает</a>
            <a href="#modules" class="transition hover:text-ink">Модули</a>
            <a href="#diagnostics" class="transition hover:text-ink">Диагностика</a>
            <a href="#guide" class="transition hover:text-ink">Гайд</a>
            <a href="#principles" class="transition hover:text-ink">Принципы</a>
        </nav>
        <div class="flex items-center gap-2">
            @auth
                <a href="{{ route('dashboard') }}" class="btn-primary px-4 py-2.5 text-xs sm:text-sm">В кабинет</a>
            @else
                <a href="{{ route('login') }}" class="btn-primary px-4 py-2.5 text-xs sm:text-sm">
                    <span class="grid h-5 w-5 place-items-center rounded-md bg-[#FC3F1D] text-[11px] font-extrabold text-white">Я</span>
                    Войти
                </a>
            @endauth
        </div>
    </div>
</header>

<main>
    <section class="mx-auto grid max-w-6xl items-center gap-12 px-5 pb-16 pt-12 sm:px-8 sm:pt-16 lg:grid-cols-[1.05fr_0.95fr] lg:pb-24 lg:pt-24">
        <div>
            <span class="chip border-brand/20 bg-brand-soft/70 text-brand-deep">
                <span class="h-1.5 w-1.5 rounded-full bg-brand"></span>
                {{ tone('home.hero.badge') }}
            </span>

            <h1 class="mt-5 text-balance text-4xl font-extrabold leading-[1.04] tracking-[-0.04em] text-ink sm:text-5xl lg:text-6xl">
                {{ tone('home.hero.title_lead') }}<br>
                <span class="inline-block whitespace-nowrap text-brand">{{ tone('home.hero.title_accent') }}</span>
            </h1>

            <p class="mt-5 max-w-xl text-base leading-relaxed text-ink-soft sm:text-lg">
                {{ tone('home.hero.lead') }}
            </p>

            <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('login') }}" class="btn-primary px-6 py-3.5">
                    {{ tone('home.hero.cta') }}
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
                <a href="#modules" class="btn-secondary px-6 py-3.5">{{ tone('home.hero.secondary') }}</a>
            </div>

            <div class="mt-9 grid max-w-lg grid-cols-3 gap-3">
                <div class="rounded-2xl border border-line/80 bg-surface/60 px-4 py-3">
                    <p class="text-2xl font-extrabold tracking-tight text-ink">5</p>
                    <p class="mt-0.5 text-[11px] font-semibold text-muted">модулей</p>
                </div>
                <div class="rounded-2xl border border-line/80 bg-surface/60 px-4 py-3">
                    <p class="text-2xl font-extrabold tracking-tight text-ink">43</p>
                    <p class="mt-0.5 text-[11px] font-semibold text-muted">тренажёра</p>
                </div>
                <div class="rounded-2xl border border-line/80 bg-surface/60 px-4 py-3">
                    <p class="text-2xl font-extrabold tracking-tight text-ink">3</p>
                    <p class="mt-0.5 text-[11px] font-semibold text-muted">формата ЭИ</p>
                </div>
            </div>
        </div>

        <div class="relative mx-auto w-full max-w-md lg:max-w-none">
            <div class="absolute -inset-4 -z-10 rounded-[2.5rem] bg-gradient-to-br from-brand/10 via-transparent to-coral/10 blur-2xl"></div>

            <div class="card rotate-[-1.2deg] p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-muted">{{ tone('home.preview.progress') }}</p>
                        <p class="mt-1 text-lg font-extrabold tracking-tight">Неделя 2</p>
                    </div>
                    <span class="chip border-mint/20 bg-mint-soft text-mint-ink">
                        <x-icon name="flame" class="h-3.5 w-3.5" /> {{ tone('home.preview.streak') }}
                    </span>
                </div>

                <div class="mt-5 grid grid-cols-3 gap-2">
                    <div class="flex flex-col items-center rounded-2xl bg-paper/80 py-3">
                        <x-progress-ring :value="72" :size="58" :stroke="5" color="#2D5BFF" />
                        <p class="mt-1 text-[10px] font-bold text-muted">Понимание</p>
                    </div>
                    <div class="flex flex-col items-center rounded-2xl bg-paper/80 py-3">
                        <x-progress-ring :value="58" :size="58" :stroke="5" color="#FF6B4A" />
                        <p class="mt-1 text-[10px] font-bold text-muted">Управление</p>
                    </div>
                    <div class="flex flex-col items-center rounded-2xl bg-paper/80 py-3">
                        <x-progress-ring :value="81" :size="58" :stroke="5" color="#2BB673" />
                        <p class="mt-1 text-[10px] font-bold text-muted">Мотивация</p>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-line/80 bg-surface p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-muted">{{ tone('home.preview.today') }}</p>
                            <p class="mt-1 text-sm font-extrabold">{{ tone('home.preview.task') }}</p>
                            <p class="mt-1 text-xs text-muted">{{ tone('home.preview.meta') }}</p>
                        </div>
                        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-brand text-white">
                            <x-icon name="play" class="h-4 w-4" />
                        </span>
                    </div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-paper-deep">
                        <div class="h-full w-2/3 rounded-full bg-brand"></div>
                    </div>
                </div>
            </div>

            <div class="card relative mt-4 w-full max-w-sm rotate-[2deg] p-4 sm:ml-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-muted">{{ tone('home.preview.note') }}</p>
                <p class="mt-1.5 text-xs leading-relaxed text-ink-soft">{{ tone('home.preview.note_text') }}</p>
                <div class="mt-2 flex items-center gap-1.5 text-[10px] font-bold text-mint-ink">
                    <x-icon name="check-circle" class="h-3.5 w-3.5" /> дневник
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-5 pb-4 sm:px-8">
        <div class="overflow-hidden rounded-[2rem] border border-line/60 bg-surface/40">
            <div class="relative">
                <x-photo path="media/photos/hero-campus.jpg" credit="none" class="h-64 sm:h-80" img-class="h-full w-full object-cover" loading="eager" />
                <div class="absolute inset-0 bg-gradient-to-t from-inverse/80 via-inverse/25 to-transparent"></div>
                <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8">
                    <p class="max-w-2xl text-lg font-extrabold leading-snug text-white sm:text-2xl">{{ tone('home.quote') }}</p>
                    @if($heroPhoto)
                        <p class="mt-2 text-[10px] font-semibold text-white/60">
                            Фото:
                            <a href="{{ $heroPhoto['author_url'] }}" target="_blank" rel="noopener noreferrer" class="underline decoration-white/30 underline-offset-2 hover:text-white">{{ $heroPhoto['author'] }}</a>
                            /
                            <a href="{{ $heroPhoto['source_url'] }}" target="_blank" rel="noopener noreferrer" class="underline decoration-white/30 underline-offset-2 hover:text-white">{{ $heroPhoto['source'] }}</a>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section id="how" class="border-y border-line/60 bg-surface/40">
        <div class="mx-auto max-w-6xl px-5 py-14 sm:px-8 sm:py-20">
            <div class="max-w-2xl">
                <p class="section-eyebrow">{{ tone('home.how.eyebrow') }}</p>
                <h2 class="section-title mt-2">{{ tone('home.how.title') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-muted">{{ tone('home.how.lead') }}</p>
            </div>

            <div class="mt-9 grid gap-4 md:grid-cols-3">
                <div class="card overflow-hidden p-6">
                    <div class="-mx-6 -mt-6 mb-5 h-28 overflow-hidden">
                        <x-photo path="media/photos/exam-writing.jpg" credit="none" class="h-full" img-class="h-full w-full object-cover" />
                    </div>
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-soft text-brand-deep"><x-icon name="chart" /></span>
                    <h3 class="mt-4 text-lg font-extrabold">{{ tone('home.how.step1.title') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ tone('home.how.step1.text') }}</p>
                </div>
                <div class="card overflow-hidden p-6">
                    <div class="-mx-6 -mt-6 mb-5 h-28 overflow-hidden">
                        <x-photo path="media/photos/workshop.jpg" credit="none" class="h-full" img-class="h-full w-full object-cover" />
                    </div>
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-coral-soft text-coral"><x-icon name="target" /></span>
                    <h3 class="mt-4 text-lg font-extrabold">{{ tone('home.how.step2.title') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ tone('home.how.step2.text') }}</p>
                </div>
                <div class="card overflow-hidden p-6">
                    <div class="-mx-6 -mt-6 mb-5 h-28 overflow-hidden">
                        <x-photo path="media/photos/notes.jpg" credit="none" class="h-full" img-class="h-full w-full object-cover" />
                    </div>
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-mint-soft text-mint-ink"><x-icon name="sparkles" /></span>
                    <h3 class="mt-4 text-lg font-extrabold">{{ tone('home.how.step3.title') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ tone('home.how.step3.text') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section id="modules" class="mx-auto max-w-6xl px-5 py-14 sm:px-8 sm:py-20">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div class="max-w-2xl">
                <p class="section-eyebrow">{{ tone('home.modules.eyebrow') }}</p>
                <h2 class="section-title mt-2">{{ tone('home.modules.title') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-muted">{{ tone('home.modules.lead') }}</p>
            </div>
            <a href="{{ route('login') }}" class="btn-secondary shrink-0">{{ tone('home.modules.cta') }}</a>
        </div>

        <div class="mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($modules as $module)
                @php $c = $palette[$module->color] ?? $palette['blue']; @endphp
                <article class="card group relative overflow-hidden p-6 transition hover:-translate-y-0.5 hover:shadow-soft">
                    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full {{ $c['soft'] }} opacity-70"></div>
                    @if($module->cover_path)
                        <div class="-mx-6 -mt-6 mb-5 h-32 overflow-hidden">
                            <x-photo :path="$module->cover_path" credit="overlay" class="h-full" img-class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
                        </div>
                    @endif
                    <div class="relative">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl {{ $c['soft'] }} {{ $c['text'] }}">
                            <x-icon :name="$module->icon ?: 'sparkles'" />
                        </span>
                        <h3 class="mt-4 text-lg font-extrabold">{{ $module->title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $module->subtitle }}</p>
                        <div class="mt-5 flex items-center justify-between">
                            <span class="chip">{{ $module->trainers_count }} {{ \App\Support\Text::plural($module->trainers_count, 'тренажёр', 'тренажёра', 'тренажёров') }}</span>
                            <span class="grid h-8 w-8 place-items-center rounded-full border {{ $c['line'] }} {{ $c['text'] }} transition group-hover:bg-surface">
                                <x-icon name="chevron-right" class="h-4 w-4" />
                            </span>
                        </div>
                    </div>
                </article>
            @endforeach

            <article class="relative overflow-hidden rounded-3xl bg-inverse p-6 text-white">
                <div class="absolute inset-0">
                    <x-photo path="media/photos/portrait-neutral.jpg" credit="none" class="h-full" img-class="h-full w-full object-cover opacity-35" />
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-inverse via-inverse/85 to-inverse/30"></div>
                <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white/5"></div>
                <div class="relative">
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-white/10 text-white"><x-icon name="camera" /></span>
                    <h3 class="mt-4 text-lg font-extrabold">{{ tone('home.camera.title') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-white/60">{{ tone('home.camera.text') }}</p>
                    <div class="mt-5 flex items-center gap-2 text-[11px] font-bold text-white/80">
                        <span class="h-1.5 w-1.5 rounded-full bg-mint"></span> {{ tone('home.camera.tag') }}
                    </div>
                </div>
            </article>
        </div>
    </section>

    <section id="diagnostics" class="border-y border-line/60 bg-inverse text-white">
        <div class="mx-auto max-w-6xl px-5 py-14 sm:px-8 sm:py-20">
            <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-white/80">{{ tone('home.diag.eyebrow') }}</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ tone('home.diag.title') }}</h2>
                    <p class="mt-4 text-sm leading-relaxed text-white/80">
                        {{ tone('home.diag.lead') }}
                    </p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <span class="rounded-full border border-white/15 px-3 py-1.5 text-[11px] font-bold text-white/80">без самоотчёта</span>
                        <span class="rounded-full border border-white/15 px-3 py-1.5 text-[11px] font-bold text-white/80">ключ на сервере</span>
                        <span class="rounded-full border border-white/15 px-3 py-1.5 text-[11px] font-bold text-white/80">пилот и анализ заданий</span>
                    </div>
                </div>

                <div class="grid gap-3">
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                        <div class="flex items-center justify-between">
                            <p class="font-extrabold">A. Полный профиль</p>
                            <span class="text-[11px] font-bold text-white/80">44 задания · 25–30 мин</span>
                        </div>
                        <p class="mt-1.5 text-xs leading-relaxed text-white/80">Четыре ветви ЭИ, сцены и смеси эмоций, экспертный ключ. Максимальная глубина.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                        <div class="flex items-center justify-between">
                            <p class="font-extrabold">B. Кейсы СПО</p>
                            <span class="text-[11px] font-bold text-white/80">20 кейсов · 15–20 мин</span>
                        </div>
                        <p class="mt-1.5 text-xs leading-relaxed text-white/80">Практика, мастер, группа, работодатель. Ближе к реальным ситуациям.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                        <div class="flex items-center justify-between">
                            <p class="font-extrabold">C. Экспресс-ЭИ</p>
                            <span class="text-[11px] font-bold text-white/80">24 задания · 12–15 мин</span>
                        </div>
                        <p class="mt-1.5 text-xs leading-relaxed text-white/80">Восприятие и понимание эмоций. Быстрый срез для повторных замеров.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="principles" class="mx-auto max-w-6xl px-5 py-14 sm:px-8 sm:py-20">
        <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
                <p class="section-eyebrow">{{ tone('home.principles.eyebrow') }}</p>
                <h2 class="section-title mt-2">{{ tone('home.principles.title') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-muted">{{ tone('home.principles.lead') }}</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="card p-5">
                    <x-icon name="shield" class="h-5 w-5 text-brand" />
                    <h3 class="mt-3 text-sm font-extrabold">{{ tone('home.principles.1.title') }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-muted">{{ tone('home.principles.1.text') }}</p>
                </div>
                <div class="card p-5">
                    <x-icon name="target" class="h-5 w-5 text-coral" />
                    <h3 class="mt-3 text-sm font-extrabold">{{ tone('home.principles.2.title') }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-muted">{{ tone('home.principles.2.text') }}</p>
                </div>
                <div class="card p-5">
                    <x-icon name="camera" class="h-5 w-5 text-lilac" />
                    <h3 class="mt-3 text-sm font-extrabold">{{ tone('home.principles.3.title') }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-muted">{{ tone('home.principles.3.text') }}</p>
                </div>
                <div class="card p-5">
                    <x-icon name="sparkles" class="h-5 w-5 text-mint-ink" />
                    <h3 class="mt-3 text-sm font-extrabold">{{ tone('home.principles.4.title') }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-muted">{{ tone('home.principles.4.text') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section id="guide" class="scroll-mt-20 border-y border-line/60 bg-surface/40">
        <div class="mx-auto max-w-6xl px-5 py-14 sm:px-8 sm:py-20">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div class="max-w-2xl">
                    <p class="section-eyebrow">{{ tone('home.guide.eyebrow') }}</p>
                    <h2 class="section-title mt-2">{{ tone('home.guide.title') }}</h2>
                    <p class="mt-3 text-sm leading-relaxed text-muted">{{ tone('home.guide.lead') }}</p>
                </div>
                <a href="{{ auth()->check() ? route('tests.index') : route('login') }}" class="btn-primary shrink-0">{{ tone('home.modules.cta') }}</a>
            </div>

            <div class="mt-9 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['n' => '01', 'icon' => 'chart', 'title' => tone('dashboard.guide.step1.title'), 'text' => tone('dashboard.guide.step1.text')],
                    ['n' => '02', 'icon' => 'play', 'title' => tone('dashboard.guide.step2.title'), 'text' => tone('dashboard.guide.step2.text')],
                    ['n' => '03', 'icon' => 'sparkles', 'title' => tone('dashboard.guide.step3.title'), 'text' => tone('dashboard.guide.step3.text')],
                    ['n' => '04', 'icon' => 'camera', 'title' => tone('dashboard.guide.step4.title'), 'text' => tone('dashboard.guide.step4.text')],
                    ['n' => '05', 'icon' => 'calendar', 'title' => tone('dashboard.guide.step5.title'), 'text' => tone('dashboard.guide.step5.text')],
                    ['n' => '06', 'icon' => 'target', 'title' => tone('dashboard.guide.step6.title'), 'text' => tone('dashboard.guide.step6.text')],
                ] as $step)
                    <div class="card p-5">
                        <div class="flex items-center justify-between">
                            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-brand-soft text-brand-deep">
                                <x-icon :name="$step['icon']" class="h-4 w-4" />
                            </span>
                            <span class="text-[10px] font-extrabold tracking-[0.16em] text-muted">{{ $step['n'] }}</span>
                        </div>
                        <p class="mt-3 text-sm font-extrabold">{{ $step['title'] }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-muted">{{ $step['text'] }}</p>
                    </div>
                @endforeach
            </div>

            <p class="mt-5 text-xs font-semibold text-muted">{{ tone('home.guide.note') }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-5 pb-16 sm:px-8 sm:pb-24">
        <div class="relative overflow-hidden rounded-[2rem] bg-brand px-6 py-10 text-white sm:px-10 sm:py-14">
            <div class="absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/10"></div>
            <div class="absolute bottom-0 right-1/4 h-32 w-32 rounded-full bg-white/5"></div>
            <div class="relative max-w-2xl">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-white">{{ tone('home.cta.eyebrow') }}</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ tone('home.cta.title') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-white">{{ tone('home.cta.lead') }}</p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-6 py-3.5 text-sm font-extrabold text-slate-900 transition hover:bg-white/90">
                        {{ tone('home.cta.button') }}
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                    <a href="#modules" class="inline-flex items-center justify-center rounded-full border border-white/25 px-6 py-3.5 text-sm font-bold text-white transition hover:bg-white/10">
                        {{ tone('home.cta.secondary') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="border-t border-line/60 bg-surface/40">
    <div class="mx-auto flex max-w-6xl flex-col gap-4 px-5 py-8 text-xs text-muted sm:flex-row sm:items-center sm:justify-between sm:px-8">
        <div class="flex items-center gap-3">
            <x-logo compact />
            <span>© {{ date('Y') }} Вектор. Педагогический инструмент.</span>
        </div>
        <div class="flex flex-wrap gap-4">
            <a href="{{ route('credits') }}" class="hover:text-ink">Фото и лицензии</a>
            <span>Не является психологической диагностикой</span>
            <span>Данные — на российском хостинге</span>
        </div>
    </div>
</footer>

<x-preference-modal />
</body>
</html>

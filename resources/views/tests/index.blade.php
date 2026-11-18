@extends('layouts.app')

@section('title', 'Диагностика')

@php
    $eiTests = $tests->filter(fn ($test) => $test->kind !== 'motivation');
    $motivationTests = $tests->filter(fn ($test) => $test->kind === 'motivation');
    $diagCovers = [
        'ei_full' => 'media/photos/exam-writing.jpg',
        'ei_situational' => 'media/photos/discussion.jpg',
        'ei_express' => 'media/photos/focus.jpg',
        'motivation' => 'media/photos/planner.jpg',
    ];
@endphp

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="section-eyebrow">Диагностика</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ tone('tests.index.title') }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
                {{ tone('tests.index.lead') }}
            </p>
        </div>
        <span class="chip shrink-0"><x-icon name="shield" class="h-3.5 w-3.5 text-mint-ink" /> без диагнозов</span>
    </div>

    <div class="mt-8 grid gap-4 lg:grid-cols-3">
        @foreach($eiTests as $test)
            @php
                $kind = \App\Enums\TestKind::tryFrom($test->kind);
                $tones = [
                    'ei_full' => 'bg-brand-soft text-brand-deep border-brand/20',
                    'ei_situational' => 'bg-coral-soft text-coral-ink border-coral/20',
                    'ei_express' => 'bg-mint-soft text-mint-ink border-mint/20',
                ];
                $tone = $tones[$test->kind] ?? 'bg-paper text-ink-soft border-line';
            @endphp
            <a href="{{ route('tests.show', $test) }}" class="card group flex flex-col overflow-hidden p-6 transition hover:-translate-y-0.5 hover:shadow-soft">
                @if(! empty($diagCovers[$test->kind]))
                    <div class="-mx-6 -mt-6 mb-5 h-32 overflow-hidden">
                        <x-photo :path="$diagCovers[$test->kind]" credit="overlay" class="h-full" img-class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
                    </div>
                @endif
                <div class="flex items-start justify-between gap-3">
                    <span class="chip {{ $tone }}">{{ $kind?->shortLabel() ?? 'ЭИ' }}</span>
                    @if($test->latest_attempt)
                        <span class="text-[11px] font-extrabold text-mint-ink">{{ (int) $test->latest_attempt->overall }}%</span>
                    @else
                        <span class="text-[11px] font-bold text-muted">не пройден</span>
                    @endif
                </div>

                <h2 class="mt-4 text-xl font-extrabold tracking-tight">{{ $test->title }}</h2>
                <p class="mt-2 flex-1 text-sm leading-relaxed text-muted">{{ $test->subtitle }}</p>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="chip">{{ $test->items_count }} {{ \App\Support\Text::plural($test->items_count, 'задание', 'задания', 'заданий') }}</span>
                    <span class="chip"><x-icon name="clock" class="h-3 w-3" /> {{ $test->time_limit_min }} мин</span>
                </div>

                <div class="mt-5 flex items-center justify-between border-t border-line/70 pt-4">
                    <span class="text-xs font-bold text-brand">{{ $test->latest_attempt ? 'Посмотреть результат' : 'Начать' }}</span>
                    <span class="grid h-8 w-8 place-items-center rounded-full border border-line text-ink-soft transition group-hover:border-brand/30 group-hover:text-brand">
                        <x-icon name="chevron-right" class="h-4 w-4" />
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    <div class="mt-8 grid gap-4 lg:grid-cols-[1.4fr_1fr]">
        <div class="card p-6">
            <p class="section-eyebrow">Как выбрать</p>
            <h2 class="mt-1 text-xl font-extrabold tracking-tight">{{ tone('tests.index.choose_title') }}</h2>
            <div class="mt-4 space-y-3 text-sm leading-relaxed text-ink-soft">
                <p>{{ tone('tests.index.choose_full') }}</p>
                <p>{{ tone('tests.index.choose_situational') }}</p>
                <p>{{ tone('tests.index.choose_express') }}</p>
            </div>
            <x-alert type="info" class="mt-4">{{ tone('tests.index.alert') }}</x-alert>
        </div>

        <div class="space-y-4">
            @foreach($motivationTests as $test)
                <a href="{{ route('tests.show', $test) }}" class="card block overflow-hidden p-6 transition hover:border-amber/30">
                    @if(! empty($diagCovers[$test->kind]))
                        <div class="-mx-6 -mt-6 mb-4 h-20 overflow-hidden">
                            <x-photo :path="$diagCovers[$test->kind]" credit="none" class="h-full" img-class="h-full w-full object-cover" />
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="chip border-amber/20 bg-amber-soft text-amber-ink">Мотивация</span>
                        @if($test->latest_attempt)
                            <span class="text-[11px] font-extrabold text-amber-ink">{{ (int) $test->latest_attempt->overall }}%</span>
                        @endif
                    </div>
                    <h2 class="mt-3 text-lg font-extrabold">{{ $test->title }}</h2>
                    <p class="mt-1 text-xs leading-relaxed text-muted">{{ $test->subtitle }}</p>
                    <p class="mt-3 text-[11px] font-bold text-amber-ink">{{ $test->latest_attempt ? 'Посмотреть результат →' : 'Пройти →' }}</p>
                </a>
            @endforeach

            <div class="rounded-3xl border border-line/60 bg-surface/50 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-muted">Что дальше</p>
                <p class="mt-2 text-xs leading-relaxed text-ink-soft">После диагностики система предложит модуль, с которого лучше начать. Но выбор всегда за вами.</p>
                <a href="{{ route('modules.index') }}" class="btn-ghost mt-2 px-0 text-xs text-brand hover:bg-transparent">К модулям →</a>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Модули')

@php
    $palette = [
        'blue' => ['soft' => 'bg-brand-soft', 'text' => 'text-brand-deep', 'ring' => '#2D5BFF'],
        'lilac' => ['soft' => 'bg-lilac-soft', 'text' => 'text-lilac', 'ring' => '#7C5CFF'],
        'coral' => ['soft' => 'bg-coral-soft', 'text' => 'text-coral', 'ring' => '#FF6B4A'],
        'mint' => ['soft' => 'bg-mint-soft', 'text' => 'text-mint-ink', 'ring' => '#2BB673'],
        'amber' => ['soft' => 'bg-amber-soft', 'text' => 'text-amber-ink', 'ring' => '#D97706'],
    ];
@endphp

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="section-eyebrow">Маршрут</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ tone('modules.index.title') }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
                {{ tone('modules.index.lead') }}
            </p>
        </div>
        <a href="{{ route('tests.index') }}" class="btn-secondary shrink-0">К диагностике</a>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        @foreach($modules as $module)
            @php
                $c = $palette[$module->color] ?? $palette['blue'];
                $progress = $module->progress ?? 0;
            @endphp
            <a href="{{ route('modules.show', $module) }}" class="card group relative overflow-hidden p-6 transition hover:-translate-y-0.5 hover:shadow-soft">
                <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full {{ $c['soft'] }} opacity-70"></div>
                @php $slideCover = \App\Support\SlideImages::module($module->slug); @endphp
                <div class="-mx-6 -mt-6 mb-5 h-36 overflow-hidden">
                    <x-photo :path="$slideCover" credit="overlay" class="h-full" img-class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
                </div>
                <div class="relative">
                    <div class="flex items-start justify-between gap-4">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl {{ $c['soft'] }} {{ $c['text'] }}">
                            <x-icon :name="$module->icon ?: 'sparkles'" class="h-5 w-5" />
                        </span>
                        <x-progress-ring :value="$progress" :size="54" :stroke="5" :color="$c['ring']" />
                    </div>

                    <h2 class="mt-4 text-xl font-extrabold tracking-tight">{{ $module->title }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ $module->subtitle }}</p>

                    <div class="mt-5 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="chip">{{ $module->completed_count }}/{{ $module->trainers->count() }} пройдено</span>
                            @if($progress === 100)
                                <span class="chip border-mint/20 bg-mint-soft text-mint-ink"><x-icon name="check" class="h-3 w-3" /> модуль закрыт</span>
                            @endif
                        </div>
                        <span class="grid h-9 w-9 place-items-center rounded-full border border-line text-ink-soft transition group-hover:border-brand/30 group-hover:text-brand">
                            <x-icon name="chevron-right" class="h-4 w-4" />
                        </span>
                    </div>
                </div>
            </a>
        @endforeach

        <a href="{{ route('camera.show') }}" class="relative overflow-hidden rounded-3xl bg-inverse p-6 text-white transition hover:-translate-y-0.5">
            <div class="absolute inset-0">
                <x-photo path="{{ \App\Support\SlideImages::contentSlide('emotion-perception', 1) }}" credit="none" class="h-full" img-class="h-full w-full object-cover opacity-35" />
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-inverse via-inverse/85 to-inverse/30"></div>
            <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white/5"></div>
            <div class="relative">
                <div class="flex items-start justify-between">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white/10 text-white"><x-icon name="camera" class="h-5 w-5" /></span>
                    <span class="chip border-white/15 bg-white/10 text-white/60">практикум</span>
                </div>
                <h2 class="mt-4 text-xl font-extrabold tracking-tight">Мимика</h2>
                <p class="mt-2 text-sm leading-relaxed text-white/70">{{ tone('modules.index.camera_text') }}</p>
                <div class="mt-5 flex items-center gap-2 text-[11px] font-bold text-white/70">
                    <span class="h-1.5 w-1.5 rounded-full bg-mint"></span> отдельный модуль
                </div>
            </div>
        </a>
    </div>
@endsection

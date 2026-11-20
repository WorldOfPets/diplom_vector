@extends('layouts.app')

@section('title', $module->title)

@php
    $palette = [
        'blue' => ['soft' => 'bg-brand-soft', 'text' => 'text-brand-deep', 'ring' => '#2D5BFF'],
        'lilac' => ['soft' => 'bg-lilac-soft', 'text' => 'text-lilac', 'ring' => '#7C5CFF'],
        'coral' => ['soft' => 'bg-coral-soft', 'text' => 'text-coral', 'ring' => '#FF6B4A'],
        'mint' => ['soft' => 'bg-mint-soft', 'text' => 'text-mint-ink', 'ring' => '#2BB673'],
        'amber' => ['soft' => 'bg-amber-soft', 'text' => 'text-amber-ink', 'ring' => '#D97706'],
    ];
    $c = $palette[$module->color] ?? $palette['blue'];
@endphp

@section('content')
    <nav class="flex items-center gap-1.5 text-[11px] font-bold text-muted" aria-label="Хлебные крошки">
        <a href="{{ route('modules.index') }}" class="hover:text-ink">Модули</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <span class="text-ink-soft">{{ $module->title }}</span>
    </nav>

    <div class="mt-4 grid gap-5 lg:grid-cols-[1.5fr_1fr]">
        <div class="card relative overflow-hidden p-6 sm:p-7">
            <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full {{ $c['soft'] }}"></div>
            @php $moduleSlide = \App\Support\SlideImages::module($module->slug, 1); @endphp
            <div class="-mx-6 -mt-6 mb-5 h-48 overflow-hidden sm:-mx-7 sm:-mt-7">
                <x-photo :path="$moduleSlide" credit="overlay" class="h-full" img-class="h-full w-full object-cover" loading="eager" />
            </div>
            <div class="relative">
                <span class="grid h-12 w-12 place-items-center rounded-2xl {{ $c['soft'] }} {{ $c['text'] }}">
                    <x-icon :name="$module->icon ?: 'sparkles'" class="h-5 w-5" />
                </span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight">{{ $module->title }}</h1>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted">{{ $module->subtitle }}</p>
                @if($module->description)
                    <p class="mt-4 max-w-xl text-sm leading-relaxed text-ink-soft">{{ $module->description }}</p>
                @endif
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <span class="chip">{{ $trainers->count() }} {{ \App\Support\Text::plural($trainers->count(), 'тренажёр', 'тренажёра', 'тренажёров') }}</span>
                    <span class="chip"><x-icon name="clock" class="h-3 w-3" /> 5–10 минут каждый</span>
                    <a href="{{ route('theory.index') }}" class="chip hover:border-brand/30">Теория модуля</a>
                </div>
            </div>
        </div>

        <div class="card flex items-center gap-5 p-6">
            <x-progress-ring :value="$progress" :size="92" :stroke="8" :color="$c['ring']" />
            <div>
                <p class="section-eyebrow">{{ tone('modules.show.progress_title') }}</p>
                <p class="mt-1 text-2xl font-extrabold">{{ $progress }}%</p>
                <p class="mt-1 text-xs leading-relaxed text-muted">{{ tone('modules.show.progress_hint', ['done' => $attempts->count(), 'total' => $trainers->count()]) }}</p>
            </div>
        </div>
    </div>

    <div class="mt-7">
        <div class="mb-3 flex items-end justify-between">
            <h2 class="text-xl font-extrabold tracking-tight">Тренажёры</h2>
            <span class="text-[11px] font-semibold text-muted">{{ tone('modules.show.any_order') }}</span>
        </div>

        <div class="space-y-3">
            @forelse($trainers as $index => $trainer)
                @php
                    $attempt = $attempts->get($trainer->id);
                    $isCamera = $trainer->engine === 'camera';
                    $href = $isCamera ? route('camera.show') : route('trainers.show', $trainer);
                    $resultHref = $attempt ? route('trainers.result', $attempt) : null;
                    $cameraDone = $isCamera ? (int) ($cameraProgress['done'] ?? 0) : 0;
                    $cameraTotal = $isCamera ? (int) ($cameraProgress['total'] ?? 0) : 0;
                    $cameraPartial = $isCamera && $cameraDone > 0 && $cameraDone < $cameraTotal;
                    $cameraComplete = $isCamera && $cameraTotal > 0 && $cameraDone >= $cameraTotal;
                @endphp
                <div class="card p-4 sm:flex sm:items-center sm:gap-4 sm:p-5">
                    <div class="flex items-start gap-3 sm:flex-1 sm:items-center sm:gap-4">
                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-2xl sm:h-16 sm:w-20">
                            <x-photo :path="\App\Support\SlideImages::module($module->slug, $index + 2)" credit="none" class="h-full" img-class="h-full w-full object-cover" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg text-[10px] font-extrabold sm:h-9 sm:w-9 sm:rounded-2xl sm:text-sm {{ $attempt ? 'bg-mint-soft text-mint-ink' : 'bg-paper-deep text-ink-soft' }}">
                                    @if($attempt)
                                        <x-icon name="check" class="h-3 w-3 sm:h-4 sm:w-4" />
                                    @elseif($isCamera)
                                        <x-icon name="camera" class="h-3 w-3 sm:h-4 sm:w-4" />
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </span>
                                <h3 class="text-base font-extrabold">{{ $trainer->title }}</h3>
                                @if($isCamera)
                                    <span class="chip border-lilac/20 bg-lilac-soft text-lilac-ink">камера</span>
                                    <span class="chip {{ $cameraComplete ? 'border-mint/20 bg-mint-soft text-mint-ink' : '' }}">{{ $cameraDone }} из {{ $cameraTotal }} эмоций</span>
                                @endif
                                @if($attempt)
                                    <span class="chip border-mint/20 bg-mint-soft text-mint-ink">{{ (int) $attempt->percent }}%</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm leading-relaxed text-muted">{{ $trainer->subtitle }}</p>
                            @if($cameraPartial)
                                <p class="mt-1 text-[11px] font-semibold text-lilac-ink">Осталось показать: {{ implode(', ', $cameraProgress['remaining']) }}</p>
                            @endif
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-[11px] font-semibold text-muted">
                                <span class="inline-flex items-center gap-1"><x-icon name="clock" class="h-3 w-3" /> {{ $trainer->duration_min }} мин</span>
                                <span class="inline-flex items-center gap-1"><x-icon name="sparkles" class="h-3 w-3" /> {{ $trainer->points }} XP</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex w-full shrink-0 items-stretch gap-2 sm:mt-0 sm:w-auto sm:items-center">
                        @if($resultHref)
                            <a href="{{ $resultHref }}" class="btn-secondary min-h-11 flex-1 px-4 py-2.5 text-xs sm:flex-none">Результат</a>
                        @endif
                        <a href="{{ $href }}" class="btn-primary min-h-11 flex-1 px-4 py-2.5 text-xs sm:flex-none">
                            {{ $attempt ? 'Повторить' : ($cameraPartial ? 'Продолжить' : 'Начать') }}
                            <x-icon name="chevron-right" class="h-3.5 w-3.5" />
                        </a>
                    </div>
                </div>
            @empty
                <x-empty-state icon="sparkles" title="В модуле пока нет тренажёров" text="Администратор ещё наполняет контент." />
            @endforelse
        </div>
    </div>

    <div class="mt-6 rounded-3xl border border-line/60 bg-surface/50 p-5">
        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-muted">После модуля</p>
        <p class="mt-2 text-sm leading-relaxed text-ink-soft">Зайдите в дневник и отметьте, что изменилось в состоянии за время практики. Одна запись — уже данные.</p>
        <a href="{{ route('diary.index') }}" class="btn-ghost mt-2 px-0 text-xs text-brand hover:bg-transparent">Записать эмоцию →</a>
    </div>
@endsection

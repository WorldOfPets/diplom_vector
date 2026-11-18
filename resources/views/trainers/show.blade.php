@extends('layouts.app')

@section('title', $trainer->title)

@section('content')
    <nav class="flex items-center gap-1.5 text-[11px] font-bold text-muted" aria-label="Хлебные крошки">
        <a href="{{ route('modules.index') }}" class="hover:text-ink">Модули</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <a href="{{ route('modules.show', $trainer->module) }}" class="hover:text-ink">{{ $trainer->module->title }}</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <span class="text-ink-soft">{{ $trainer->title }}</span>
    </nav>

    <div class="mt-4 grid gap-5 lg:grid-cols-[1.6fr_1fr]">
        <div class="min-w-0">
            <div class="mb-5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="chip">{{ $engine->label() }}</span>
                    <span class="chip"><x-icon name="clock" class="h-3 w-3" /> {{ $trainer->duration_min }} мин</span>
                    <span class="chip"><x-icon name="sparkles" class="h-3 w-3" /> {{ $trainer->points }} XP</span>
                </div>
                <h1 class="mt-3 break-words text-2xl font-extrabold tracking-tight sm:text-3xl lg:text-4xl">{{ $trainer->title }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">{{ $trainer->description ?: $trainer->subtitle }}</p>
            </div>

            @if($trainer->cover_path)
                <div class="mb-5 overflow-hidden rounded-3xl border border-line">
                    <x-photo :path="$trainer->cover_path" credit="overlay" class="h-52 sm:h-64" img-class="h-full w-full object-cover" loading="eager" />
                </div>
            @endif

            @if($completedAttempt)
                <x-alert type="success" class="mb-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span>Вы уже проходили этот тренажёр: <strong>{{ (int) $completedAttempt->percent }}%</strong>. Можно пройти ещё раз — результат сохранится отдельной попыткой.</span>
                        <a href="{{ route('trainers.result', $completedAttempt) }}" class="font-bold underline underline-offset-4">Открыть результат</a>
                    </div>
                </x-alert>
            @endif

            <form method="POST"
                  action="{{ route('trainers.submit', $trainer) }}"
                  x-data="trainerForm({
                      url: '{{ route('trainers.submit', $trainer) }}',
                      csrf: '{{ csrf_token() }}',
                      attemptId: {{ $attempt->id }},
                      initial: @js($attempt->response ?? [])
                  })"
                  @response-patch.window="response = { ...response, ...$event.detail }"
                  @submit.prevent="submit"
                  class="space-y-4">
                @include('trainers.engines.'.$engine->key(), ['config' => $config, 'saved' => $attempt->response ?? []])

                <div x-show="error" x-cloak class="rounded-2xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose" x-text="error"></div>

                <div class="sticky bottom-[calc(4.75rem+env(safe-area-inset-bottom))] z-20 rounded-3xl border border-line/80 bg-paper/95 p-3 backdrop-blur-xl lg:static lg:mt-2">
                    <button type="submit" class="btn-primary w-full py-3.5" :disabled="submitting">
                        <span x-show="!submitting" class="inline-flex items-center gap-2">
                            {{ tone('trainer.show.submit') }}
                            <x-icon name="check" class="h-4 w-4" />
                        </span>
                        <span x-show="submitting" x-cloak>Сохраняем…</span>
                    </button>
                    <p class="mt-2 text-center text-[10px] font-semibold text-muted">{{ tone('trainer.show.submit_hint') }}</p>
                </div>
            </form>
        </div>

        <aside class="space-y-4">
            <div class="card p-5">
                <p class="section-eyebrow">{{ tone('trainer.show.aside_title') }}</p>
                <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ $trainer->subtitle }}</p>
                <div class="mt-4 space-y-2 text-xs leading-relaxed text-muted">
                    <p>• {{ tone('trainer.show.bullet1') }}</p>
                    <p>• {{ tone('trainer.show.bullet2') }}</p>
                    <p>• {{ tone('trainer.show.bullet3') }}</p>
                    <p>• {{ tone('trainer.show.bullet4') }}</p>
                </div>
            </div>

            <div class="rounded-3xl border border-line/60 bg-surface/50 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-muted">{{ tone('trainer.show.help_title') }}</p>
                <p class="mt-2 text-xs leading-relaxed text-ink-soft">{{ tone('trainer.show.help_text') }}</p>
            </div>
        </aside>
    </div>
@endsection

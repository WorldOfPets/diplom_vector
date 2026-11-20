@extends('layouts.app')

@section('title', $article->title)

@php
    $localVideo = $article->localVideoPath();
    $localPoster = $article->localVideoPoster();
    $embed = $localVideo ? null : $article->rutubeEmbedUrl();
    $task = $article->task;
    $taskAnswers = $task['answers'] ?? [];
    $taskOptions = $task['options'] ?? [];
    $blankIds = array_map('strval', array_keys($taskAnswers));
    $taskParts = $task
        ? preg_split('/(\{\{\d+\}\})/', $task['text'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY)
        : [];
    $firstDone = $article->hasSlides() ? $slidesCompleted : ($article->hasTrackableVideo() ? $videoCompleted : $isRead);
    $hasSlides = $article->hasSlides();
    $slidesCount = count($slides);
@endphp

@section('content')
    <nav class="flex items-center gap-1.5 text-[11px] font-bold text-muted" aria-label="Хлебные крошки">
        <a href="{{ route('theory.index') }}" class="hover:text-ink">Теория</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <span class="text-ink-soft">{{ $article->title }}</span>
    </nav>

    <div class="mx-auto mt-4 max-w-3xl"
         x-data="{
            read: {{ $isRead ? 'true' : 'false' }},
            firstDone: {{ $firstDone ? 'true' : 'false' }},
            sourceDone: {{ $sourceOpened ? 'true' : 'false' }},
            taskDone: {{ $taskCompleted ? 'true' : 'false' }},
            steps: {{ $completedSteps }},
            advance(flag) {
                if (!this[flag]) {
                    this[flag] = true;
                    this.steps = Math.min(3, this.steps + 1);
                }
            }
         }"
         @theory-video-completed.window="advance('firstDone')"
         @theory-slides-completed.window="advance('firstDone')"
         @theory-task-completed.window="advance('taskDone')"
         x-init="if (!read && !{{ $hasSlides ? 'true' : 'false' }}) { setTimeout(async () => { try { await fetch('{{ route('theory.read', $article) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' } }); read = true; if ({{ $article->hasTrackableVideo() ? 'false' : 'true' }}) advance('firstDone'); } catch (e) {} }, 5000); }">
        <div class="mb-3 flex flex-wrap items-center gap-2">
            <span class="chip">{{ $article->reading_minutes }} мин</span>
            <span class="chip"><x-icon name="target" class="h-3 w-3 text-mint-ink" /> <span x-text="steps + '/3 шага'">{{ $completedSteps }}/3 шага</span></span>
            <span class="chip" x-show="read" x-cloak><x-icon name="check" class="h-3 w-3 text-mint" /> прочитано</span>
            @if($article->hasVideo() && ! $hasSlides)
                <span class="chip">видео</span>
            @endif
            @if($hasSlides)
                <span class="chip">слайды · {{ $slidesCount }}</span>
            @endif
            @if($article->hasTask())
                <span class="chip">задание</span>
            @endif
            @if($article->hasTrackableVideo() && $videoCompleted && ! $hasSlides)
                <span class="chip"><x-icon name="check" class="h-3 w-3 text-mint-ink" /> видео 80%</span>
            @endif
        </div>

        <h1 class="text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">{{ $article->title }}</h1>
        @if($article->excerpt)
            <p class="mt-3 text-base leading-relaxed text-muted">{{ $article->excerpt }}</p>
        @endif

        @if($hasSlides)
            <div
                class="mt-6 overflow-hidden rounded-3xl border border-line bg-inverse"
                x-data="theorySlides({
                    slides: @js($slides),
                    viewed: @js($slidesProgress),
                    minSeconds: 10,
                    completed: @js($slidesCompleted),
                    url: '{{ route('theory.slides', $article) }}',
                    csrf: '{{ csrf_token() }}'
                })"
            >
                <div class="relative aspect-video bg-black">
                    <img :src="current" :alt="`Слайд ${index + 1} из ${total}`" class="h-full w-full object-contain">
                </div>
                <div class="border-t border-white/10 px-4 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <button type="button" @click="go(-1)" :disabled="index === 0"
                                class="grid h-9 w-9 place-items-center rounded-xl bg-white/10 text-sm font-extrabold text-white transition disabled:opacity-30" aria-label="Предыдущий слайд">←</button>
                        <div class="text-center">
                            <p class="text-[11px] font-extrabold text-white" x-text="`Слайд ${index + 1} из ${total}`"></p>
                            <p class="text-[10px] font-semibold text-white/60" x-show="!ready" x-cloak x-text="`Ещё ${remaining} с`"></p>
                            <p class="text-[10px] font-semibold text-mint" x-show="ready" x-cloak>слайд засчитан</p>
                        </div>
                        <button type="button" @click="go(1)" :disabled="index === total - 1 || !ready"
                                class="grid h-9 w-9 place-items-center rounded-xl bg-white/10 text-sm font-extrabold text-white transition disabled:opacity-30" aria-label="Следующий слайд">→</button>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/15">
                        <div class="h-full rounded-full bg-brand transition-all" :style="`width: ${(viewedCount / total) * 100}%`"></div>
                    </div>
                    <p class="mt-2 text-[10px] font-semibold text-white/50" x-show="!completed" x-cloak>Каждый слайд — не меньше 10 секунд. Вся презентация = 1 единица прогресса.</p>
                    <p class="mt-2 text-[10px] font-semibold text-mint" x-show="completed" x-cloak>Презентация просмотрена: +1 единица прогресса.</p>
                </div>
            </div>
        @elseif($article->cover_path)
            <x-photo :path="$article->cover_path" credit="caption" class="mt-6 rounded-3xl border border-line" img-class="max-h-[420px] w-full object-cover" loading="eager" />
        @endif

        @if(! $hasSlides && $embed)
            <div
                class="mt-6 overflow-hidden rounded-3xl border border-line bg-inverse"
                x-data="theoryVideo({
                    url: '{{ route('theory.video', $article) }}',
                    csrf: '{{ csrf_token() }}',
                    completed: {{ $videoCompleted ? 'true' : 'false' }},
                    initialPercent: {{ (float) $videoProgress }}
                })"
            >
                <div class="aspect-video">
                    <iframe
                        x-ref="frame"
                        id="theory-video-{{ $article->id }}"
                        src="{{ $embed }}"
                        title="{{ $article->title }}"
                        class="h-full w-full"
                        allow="clipboard-write; autoplay"
                        allowfullscreen
                        loading="lazy"
                    ></iframe>
                </div>
                <div class="border-t border-white/10 px-4 py-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] font-bold">
                        <span class="text-white/70" x-show="!completed">Просмотрено: <span x-text="percent"></span>%</span>
                        <span class="text-mint" x-show="!completed">Нужно 80%</span>
                        <span class="text-mint" x-show="completed" x-cloak>✓ видео просмотрено</span>
                        <span class="text-white/50" x-show="!completed">Засчитывается после 80% и только со звуком</span>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/15" x-show="!completed">
                        <div class="h-full rounded-full bg-brand transition-[width] duration-300" :style="`width: ${percent}%`"></div>
                    </div>
                    <p class="mt-2 text-[10px] font-bold text-amber-200" x-show="!soundOn && !completed" x-cloak>
                        Звук выключен: прогресс не засчитывается. Включите звук в плеере.
                    </p>
                </div>
            </div>
        @elseif(! $hasSlides && $localVideo)
            <div
                class="mt-6 overflow-hidden rounded-3xl border border-line bg-inverse"
                x-data="theoryLocalVideo({
                    url: '{{ route('theory.video', $article) }}',
                    csrf: '{{ csrf_token() }}',
                    completed: {{ $videoCompleted ? 'true' : 'false' }},
                    initialPercent: {{ (float) $videoProgress }}
                })"
            >
                <div class="aspect-video bg-black">
                    <video
                        x-ref="video"
                        class="h-full w-full"
                        controls
                        playsinline
                        preload="metadata"
                        @if($localPoster) poster="{{ asset($localPoster) }}" @endif
                        aria-label="{{ $article->title }}"
                        @timeupdate="onTimeUpdate($event)"
                        @volumechange="onVolumeChange($event)"
                        @seeking="lastTime = null"
                    >
                        <source src="{{ asset($localVideo) }}" type="{{ $article->localVideoMime() }}">
                    </video>
                </div>
                <div class="border-t border-white/10 px-4 py-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] font-bold">
                        <span class="text-white/70" x-show="!completed">Просмотрено: <span x-text="percent"></span>%</span>
                        <span class="text-mint" x-show="!completed">Нужно 80%</span>
                        <span class="text-mint" x-show="completed" x-cloak>✓ видео просмотрено</span>
                        <span class="text-white/50" x-show="!completed">Засчитывается после 80% и только со звуком</span>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/15" x-show="!completed">
                        <div class="h-full rounded-full bg-brand transition-[width] duration-300" :style="`width: ${percent}%`"></div>
                    </div>
                    <p class="mt-2 text-[10px] font-bold text-amber-200" x-show="!soundOn && !completed" x-cloak>
                        Звук выключен: прогресс не засчитывается. Включите звук в плеере.
                    </p>
                </div>
            </div>
        @elseif(! $hasSlides && $article->video_url)
            <x-alert type="info" class="mt-6">
                Видео доступно по ссылке: <a href="{{ $article->video_url }}" target="_blank" rel="noopener" class="font-bold underline">открыть видео</a>.
            </x-alert>
        @endif

        <section class="card mt-5 p-5 sm:p-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="section-eyebrow">Прогресс материала</p>
                    <h2 class="mt-1 text-xl font-extrabold"><span x-text="steps + ' из 3 шагов'">{{ $completedSteps }} из 3 шагов</span></h2>
                </div>
                <span class="chip" x-text="steps + '/3'">{{ $completedSteps }}/3</span>
            </div>

            <div class="mt-4 space-y-2">
                <div class="flex items-center gap-3 rounded-2xl border p-3" :class="firstDone ? 'border-mint/20 bg-mint-soft/50' : 'border-line bg-paper/60'">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl" :class="firstDone ? 'bg-mint-soft text-mint-ink' : 'bg-surface text-muted'">
                        <x-icon :name="$article->hasSlides() ? 'grid' : ($article->hasTrackableVideo() ? 'play' : 'book')" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-extrabold">{{ $article->hasSlides() ? 'Презентация просмотрена' : ($article->hasTrackableVideo() ? 'Видео просмотрено' : 'Статья прочитана') }}</p>
                        <p class="mt-0.5 text-[10px] leading-relaxed text-muted">
                            {{ $article->hasSlides() ? 'Все слайды просмотрены — по 10 секунд каждый.' : ($article->hasTrackableVideo() ? 'Нужно 80% видео и включённый звук.' : 'Первый шаг засчитывается после открытия материала.') }}
                        </p>
                    </div>
                    <span class="text-[11px] font-extrabold" :class="firstDone ? 'text-mint-ink' : 'text-muted'" x-text="firstDone ? '+1' : '0/1'">{{ $firstDone ? '+1' : '0/1' }}</span>
                </div>

                <a href="{{ route('theory.source', $article) }}" target="_blank" rel="noopener"
                   @click="advance('sourceDone')"
                   class="flex items-center gap-3 rounded-2xl border p-3 transition"
                   :class="sourceDone ? 'border-mint/20 bg-mint-soft/50' : 'border-line bg-paper/60 hover:border-brand/30'">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl" :class="sourceDone ? 'bg-mint-soft text-mint-ink' : 'bg-surface text-muted'">
                        <x-icon name="external" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-extrabold">Открыть первоисточник</p>
                        <p class="mt-0.5 text-[10px] leading-relaxed text-muted">Откроется в новой вкладке: {{ $article->source_url ? parse_url($article->source_url, PHP_URL_HOST) : 'внешний источник' }}. Один переход — одна единица.</p>
                    </div>
                    <span class="text-[11px] font-extrabold" :class="sourceDone ? 'text-mint-ink' : 'text-muted'" x-text="sourceDone ? '+1' : '0/1'">{{ $sourceOpened ? '+1' : '0/1' }}</span>
                </a>

                <div class="flex items-center gap-3 rounded-2xl border p-3" :class="taskDone ? 'border-mint/20 bg-mint-soft/50' : 'border-line bg-paper/60'">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl" :class="taskDone ? 'bg-mint-soft text-mint-ink' : 'bg-surface text-muted'">
                        <x-icon name="sparkles" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-extrabold">Задание выполнено</p>
                        <p class="mt-0.5 text-[10px] leading-relaxed text-muted">Засчитывается только при всех словах на своих местах.</p>
                    </div>
                    <span class="text-[11px] font-extrabold" :class="taskDone ? 'text-mint-ink' : 'text-muted'" x-text="taskDone ? '+1' : '0/1'">{{ $taskCompleted ? '+1' : '0/1' }}</span>
                </div>
            </div>

            <p class="mt-3 text-[10px] leading-relaxed text-muted">Каждый шаг даёт 1 единицу прогресса. Всего за материал — 3 единицы.</p>
        </section>

        @if($article->hasTask())
            <section
                class="card mt-5 p-5 sm:p-6"
                x-data="theoryTask({{ Illuminate\Support\Js::from([
                    'url' => route('theory.task', $article),
                    'csrf' => csrf_token(),
                    'options' => $taskOptions,
                    'blankIds' => $blankIds,
                    'completed' => $taskCompleted,
                    'filled' => $taskCompleted ? $taskAnswers : (object) [],
                ]) }})"
            >
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="section-eyebrow">Задание</p>
                        <h2 class="mt-1 text-xl font-extrabold">Соберите краткое содержание</h2>
                    </div>
                    <span class="chip" x-show="completed" x-cloak><x-icon name="check" class="h-3 w-3 text-mint-ink" /> выполнено</span>
                </div>
                <p class="mt-2 text-sm leading-relaxed text-muted">
                    Перетащите недостающие слова в пропуски. Задание засчитывается, только если все слова на своих местах.
                </p>

                <div class="mt-4 rounded-3xl border border-line/70 bg-paper/60 p-4 text-sm leading-9 text-ink-soft sm:p-5">
                    @foreach($taskParts as $part)
                        @if(preg_match('/^\{\{(\d+)\}\}$/', $part, $matches))
                            <button
                                type="button"
                                class="mx-1 inline-flex min-h-9 min-w-24 items-center justify-center rounded-xl border-2 border-dashed px-2 py-0.5 align-middle text-sm font-bold transition"
                                :class="answers['{{ $matches[1] }}'] ? 'border-brand/30 bg-brand-soft text-brand-deep' : 'border-line bg-surface text-muted'"
                                @click="tapBlank('{{ $matches[1] }}')"
                                @dragover.prevent
                                @drop.prevent="dropBlank('{{ $matches[1] }}', $event)"
                                :disabled="completed"
                            >
                                <span x-text="answers['{{ $matches[1] }}'] || 'слово'"></span>
                            </button>
                        @else
                            {{ $part }}
                        @endif
                    @endforeach
                </div>

                <div class="mt-4" x-show="!completed" x-cloak>
                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted">Слова для перетаскивания</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <template x-for="(option, index) in options" :key="index">
                            <button
                                type="button"
                                draggable="true"
                                class="rounded-full border px-3 py-2 text-xs font-bold transition"
                                :class="option.used ? 'border-line bg-paper text-muted/40 line-through' : (selected === option.word ? 'border-brand bg-brand-soft text-brand-deep' : 'border-line bg-surface text-ink-soft hover:border-brand/30')"
                                :disabled="option.used || completed"
                                @dragstart="dragStart(option, $event)"
                                @click="select(option)"
                                x-text="option.word"
                            ></button>
                        </template>
                    </div>
                    <p class="mt-2 text-[10px] leading-relaxed text-muted">
                        Перетащите слово в пропуск или нажмите на слово, а затем на пропуск. Чтобы убрать слово, нажмите на пропуск.
                    </p>
                </div>

                <p class="mt-3 text-xs font-bold text-rose" x-show="error" x-text="error" x-cloak></p>

                <div class="mt-4 rounded-2xl border border-mint/20 bg-mint-soft/60 p-4" x-show="completed" x-cloak>
                    <p class="text-sm font-extrabold text-mint-ink">Задание выполнено</p>
                    <p class="mt-1 text-xs leading-relaxed text-mint-ink/80">Все слова на своих местах. +1 единица прогресса.</p>
                </div>

                <button
                    type="button"
                    class="btn-primary mt-4 w-full py-3 text-xs"
                    @click="check"
                    :disabled="busy || completed || Object.keys(answers).length < blankIds.length"
                >
                    <span x-show="!busy">Проверить задание</span>
                    <span x-show="busy" x-cloak>Проверяем…</span>
                </button>
            </section>
        @endif

        <details class="card mt-5 p-5 sm:p-6">
            <summary class="cursor-pointer text-sm font-extrabold">Полный текст статьи</summary>
            <article class="prose-vektor mt-4">
                @if($article->body)
                    {!! Str::markdown($article->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                @else
                    <p>Полный текст статьи доступен по внешней ссылке.</p>
                @endif
            </article>
        </details>

        @if($article->hasVideo())
            <p class="mt-2 text-[10px] leading-relaxed text-muted">
                Видео — учебный обзор, а не нормативный источник. Оценки вклада IQ и ЭИ в успех зависят от выборки и метода: воспринимайте проценты как порядок величин, а не точную формулу.
            </p>
        @endif

        <div class="mt-8 rounded-3xl border border-line/60 bg-surface/50 p-5">
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-muted">{{ tone('theory.show.practice_title') }}</p>
            <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ tone('theory.show.practice_text') }}</p>
            <a href="{{ route('modules.index') }}" class="btn-primary mt-3 px-4 py-2.5 text-xs">К модулям</a>
        </div>

        @if($next)
            <a href="{{ route('theory.show', $next) }}" class="mt-4 flex items-center justify-between rounded-2xl border border-line bg-surface p-4 text-sm font-bold transition hover:border-brand/30">
                <span>Следующий материал: {{ $next->title }}</span>
                <x-icon name="chevron-right" class="h-4 w-4 text-muted" />
            </a>
        @endif
    </div>
@endsection

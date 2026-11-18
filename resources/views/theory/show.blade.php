@extends('layouts.app')

@section('title', $article->title)

@php
    $localVideo = $article->localVideoPath();
    $localPoster = $article->localVideoPoster();
    $embed = $localVideo ? null : $article->rutubeEmbedUrl();
@endphp

@section('content')
    <nav class="flex items-center gap-1.5 text-[11px] font-bold text-muted" aria-label="Хлебные крошки">
        <a href="{{ route('theory.index') }}" class="hover:text-ink">Теория</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <span class="text-ink-soft">{{ $article->title }}</span>
    </nav>

    <div class="mx-auto mt-4 max-w-3xl"
         x-data="{ read: {{ $isRead ? 'true' : 'false' }} }"
         x-init="if (!read) { setTimeout(async () => { try { await fetch('{{ route('theory.read', $article) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' } }); read = true; } catch (e) {} }, 5000); }">
        <div class="mb-3 flex flex-wrap items-center gap-2">
            <span class="chip">{{ $article->reading_minutes }} мин</span>
            <span class="chip" x-show="read" x-cloak><x-icon name="check" class="h-3 w-3 text-mint" /> прочитано</span>
            @if($article->hasVideo())
                <span class="chip">видео</span>
            @endif
            @if($article->hasTrackableVideo() && $videoCompleted)
                <span class="chip"><x-icon name="check" class="h-3 w-3 text-mint-ink" /> видео 80%</span>
            @endif
        </div>

        <h1 class="text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">{{ $article->title }}</h1>
        @if($article->excerpt)
            <p class="mt-3 text-base leading-relaxed text-muted">{{ $article->excerpt }}</p>
        @endif

        @if($article->cover_path)
            <x-photo :path="$article->cover_path" credit="caption" class="mt-6 rounded-3xl border border-line" img-class="max-h-[420px] w-full object-cover" loading="eager" />
        @endif

        @if($embed)
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
        @elseif($localVideo)
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
        @elseif($article->video_url)
            <x-alert type="info" class="mt-6">
                Видео доступно по ссылке: <a href="{{ $article->video_url }}" target="_blank" rel="noopener" class="font-bold underline">открыть видео</a>.
            </x-alert>
        @endif

        <article class="prose-vektor mt-7">
            @if($article->body)
                {!! Str::markdown($article->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
            @else
                <p>Полный текст статьи доступен по внешней ссылке.</p>
            @endif
        </article>

        @if($article->hasVideo())
            <p class="mt-2 text-[10px] leading-relaxed text-muted">
                Видео — учебный обзор, а не нормативный источник. Оценки вклада IQ и ЭИ в успех зависят от выборки и метода: воспринимайте проценты как порядок величин, а не точную формулу.
            </p>
        @endif

        @if($article->source_url)
            <a href="{{ $article->source_url }}" target="_blank" rel="noopener" class="btn-secondary mt-6">
                Открыть первоисточник
                <x-icon name="external" class="h-4 w-4" />
            </a>
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

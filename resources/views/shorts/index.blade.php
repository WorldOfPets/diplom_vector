@extends('layouts.app')

@section('title', 'Шортсы')

@push('head')
    <style>
        .shorts-feed {
            scrollbar-width: none;
        }

        .shorts-feed::-webkit-scrollbar {
            display: none;
        }
    </style>
@endpush

@section('content')
    <div class="mx-auto max-w-md sm:mx-0 sm:max-w-none">
        <div class="hidden flex-col gap-3 lg:flex">
            <div>
                <p class="section-eyebrow">Мини-видео</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Шортсы</h1>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted">
                    {{ tone('shorts.index.lead') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="chip"><x-icon name="play" class="h-3 w-3" /> {{ $shorts->min('duration_sec') }}–{{ $shorts->max('duration_sec') }} секунд</span>
                <span class="chip"><x-icon name="heart" class="h-3 w-3 text-rose" /> только лайки</span>
                <span class="chip">со звуком и без</span>
                <a href="{{ route('credits') }}" class="chip hover:border-brand/30">фото и лицензии</a>
            </div>
        </div>

        @if($shorts->isEmpty())
            <x-empty-state icon="play" title="Шортсы скоро появятся" :text="tone('shorts.index.empty')" class="mt-8">
                <a href="{{ route('modules.index') }}" class="btn-primary px-4 py-2.5 text-xs">К модулям</a>
            </x-empty-state>
        @else
            <div class="mx-auto mt-0 max-w-md lg:mt-5" x-data="shortsFeed({ count: {{ $shorts->count() }} })">
                <div
                    x-ref="feed"
                    data-shorts-feed
                    class="shorts-feed flex h-[calc(100dvh-10rem)] snap-y snap-mandatory flex-col overflow-y-auto overscroll-contain rounded-[2rem] lg:h-[min(78dvh,44rem)]"
                    aria-label="Лента шортсов"
                    @scroll.passive="onScroll"
                    @short-active.window="active = $event.detail.index"
                >
                    @foreach($shorts as $short)
                        <section class="flex h-full w-full shrink-0 snap-start snap-always items-center justify-center" data-short-slide>
                            <article
                                class="relative mx-auto h-full aspect-[9/16] max-w-full overflow-hidden rounded-[2rem] bg-inverse text-white shadow-soft"
                                x-data="shortCard({
                                    id: '{{ $short->id }}',
                                    index: {{ $loop->index }},
                                    liked: {{ $likedIds->has($short->id) ? 'true' : 'false' }},
                                    likes: {{ (int) $short->likes_count }},
                                    viewed: {{ $viewedIds->has($short->id) ? 'true' : 'false' }},
                                    hasAudio: {{ $short->has_audio ? 'true' : 'false' }},
                                    likeUrl: '{{ route('shorts.like', $short) }}',
                                    viewUrl: '{{ route('shorts.view', $short) }}',
                                    csrf: '{{ csrf_token() }}'
                                })"
                            >
                                <video
                                    x-ref="video"
                                    class="h-full w-full object-cover"
                                    poster="{{ asset($short->poster_path) }}"
                                    muted
                                    :muted="muted"
                                    loop
                                    playsinline
                                    preload="metadata"
                                    aria-label="{{ $short->title }}"
                                    @click="toggle"
                                    @play="playing = true"
                                    @pause="playing = false"
                                    @timeupdate="onTimeUpdate($event)"
                                >
                                    <source src="{{ asset($short->video_path) }}" type="video/mp4">
                                </video>

                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-inverse/45 via-transparent to-inverse/90"></div>
                                <div class="absolute inset-x-0 top-0 h-1 bg-white/15">
                                    <div class="h-full bg-white/80 transition-[width] duration-150" :style="`width: ${progress}%`"></div>
                                </div>

                                <div class="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-between gap-2 p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full border border-white/15 bg-black/25 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.12em] text-white/80 backdrop-blur">{{ $short->topic }}</span>
                                        <span x-show="viewed" x-cloak class="rounded-full border border-mint/30 bg-mint/20 px-2 py-1 text-[10px] font-bold text-mint">✓</span>
                                    </div>
                                    <div class="pointer-events-auto flex items-center gap-2">
                                        <span class="rounded-full border border-white/15 bg-black/25 px-3 py-1.5 text-[10px] font-bold text-white/70 backdrop-blur">{{ $short->duration_sec }} сек</span>
                                        @if($short->has_audio)
                                            <button
                                                type="button"
                                                class="grid h-8 w-8 place-items-center rounded-full border border-white/15 bg-black/25 text-white/80 backdrop-blur transition hover:bg-white/15"
                                                @click.stop="toggleMute"
                                                :aria-label="muted ? 'Включить звук' : 'Выключить звук'"
                                                :aria-pressed="!muted"
                                            >
                                                <x-icon name="volume-x" x-show="muted" class="h-4 w-4" />
                                                <x-icon name="volume" x-show="!muted" x-cloak class="h-4 w-4" />
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <div class="pointer-events-none absolute inset-0 grid place-items-center">
                                    <span x-show="!playing" class="grid h-16 w-16 place-items-center rounded-full border border-white/20 bg-white/15 text-white backdrop-blur-md">
                                        <x-icon name="play" class="h-6 w-6" />
                                    </span>
                                </div>

                                <div class="absolute bottom-20 right-3 flex flex-col items-center gap-2 sm:bottom-24 sm:right-4">
                                    <button
                                        type="button"
                                        class="flex flex-col items-center gap-1 rounded-2xl px-1 py-1 text-white transition active:scale-95"
                                        :class="liked ? 'text-rose-200' : ''"
                                        @click.stop="like"
                                        :aria-pressed="liked"
                                        :aria-label="(liked ? 'Убрать лайк' : 'Поставить лайк') + ', ' + likes"
                                        :disabled="busy"
                                    >
                                        <span class="grid h-11 w-11 place-items-center rounded-full border border-white/15 bg-black/25 backdrop-blur">
                                            <x-icon name="heart" class="h-5 w-5" x-bind:class="liked ? 'fill-current' : ''" />
                                        </span>
                                        <span class="text-[10px] font-extrabold drop-shadow" x-text="likes"></span>
                                    </button>
                                    <a href="{{ route('credits') }}" class="text-[9px] font-semibold text-white/60 underline decoration-white/30 underline-offset-2">credits</a>
                                </div>

                                <div class="pointer-events-none absolute inset-x-0 bottom-0 p-5 pr-16 sm:pr-20">
                                    <h2 class="text-lg font-extrabold leading-tight tracking-tight sm:text-xl" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $short->title }}</h2>
                                    <p class="mt-1 text-xs leading-relaxed text-white/70" style="display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;">{{ $short->subtitle }}</p>
                                </div>
                            </article>
                        </section>
                    @endforeach
                </div>

                <div class="mt-3 hidden items-center justify-between gap-3 lg:flex">
                    <p class="text-[10px] font-semibold text-muted">Свайп вверх или колесо мыши — следующий ролик. Одновременно играет только один.</p>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold tabular-nums text-muted" x-text="`${active + 1} / ${count}`"></span>
                        <button type="button" class="grid h-9 w-9 place-items-center rounded-full border border-line bg-surface text-ink-soft transition hover:border-brand/30 disabled:opacity-40" @click="go(active - 1)" :disabled="active <= 0" aria-label="Предыдущий ролик">
                            <x-icon name="chevron-down" class="h-4 w-4 rotate-180" />
                        </button>
                        <button type="button" class="grid h-9 w-9 place-items-center rounded-full border border-line bg-surface text-ink-soft transition hover:border-brand/30 disabled:opacity-40" @click="go(active + 1)" :disabled="active >= count - 1" aria-label="Следующий ролик">
                            <x-icon name="chevron-down" class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <p class="mt-5 hidden text-center text-[11px] leading-relaxed text-muted sm:text-left lg:block">
                Видео можно смотреть со звуком или без. Комментариев нет. Лайк — не оценка, а сигнал «мне откликается».
            </p>
        @endif
    </div>
@endsection

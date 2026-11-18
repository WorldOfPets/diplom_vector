@extends('layouts.app')

@section('title', 'Шортсы')

@section('content')
    <div class="mx-auto max-w-md sm:mx-0 sm:max-w-none">
        <div class="flex flex-col gap-3">
            <div>
                <p class="section-eyebrow">Мини-видео</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Шортсы</h1>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted">
                    {{ tone('shorts.index.lead') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="chip"><x-icon name="play" class="h-3 w-3" /> 9 секунд</span>
                <span class="chip"><x-icon name="heart" class="h-3 w-3 text-rose" /> только лайки</span>
                <a href="{{ route('credits') }}" class="chip hover:border-brand/30">фото и лицензии</a>
            </div>
        </div>

        @if($shorts->isEmpty())
            <x-empty-state icon="play" title="Шортсы скоро появятся" :text="tone('shorts.index.empty')" class="mt-8">
                <a href="{{ route('modules.index') }}" class="btn-primary px-4 py-2.5 text-xs">К модулям</a>
            </x-empty-state>
        @else
            <div class="mt-6 space-y-5 sm:grid sm:grid-cols-2 sm:items-start sm:gap-4 sm:space-y-0 xl:grid-cols-3">
                @foreach($shorts as $short)
                    <article
                        class="relative overflow-hidden rounded-[2rem] bg-inverse text-white shadow-soft"
                        x-data="shortCard({
                            liked: {{ $likedIds->has($short->id) ? 'true' : 'false' }},
                            likes: {{ (int) $short->likes_count }},
                            viewed: {{ $viewedIds->has($short->id) ? 'true' : 'false' }},
                            likeUrl: '{{ route('shorts.like', $short) }}',
                            viewUrl: '{{ route('shorts.view', $short) }}',
                            csrf: '{{ csrf_token() }}'
                        })"
                    >
                        <video
                            x-ref="video"
                            class="aspect-[9/16] w-full object-cover"
                            poster="{{ asset($short->poster_path) }}"
                            muted
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

                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-inverse/35 via-transparent to-inverse"></div>
                        <div class="absolute inset-x-0 top-0 h-1 bg-white/15">
                            <div class="h-full bg-white/80 transition-[width] duration-150" :style="`width: ${progress}%`"></div>
                        </div>

                        <div class="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-between gap-2 p-4">
                            <div class="flex items-center gap-2">
                                <span class="rounded-full border border-white/15 bg-black/25 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.12em] text-white/80 backdrop-blur" x-text="'{{ $short->topic }}'"></span>
                                <span x-show="viewed" x-cloak class="rounded-full border border-mint/30 bg-mint/20 px-2 py-1 text-[10px] font-bold text-mint">✓</span>
                            </div>
                            <span class="rounded-full border border-white/15 bg-black/25 px-3 py-1.5 text-[10px] font-bold text-white/70 backdrop-blur">{{ $short->duration_sec }} сек · без звука</span>
                        </div>

                        <div class="pointer-events-none absolute inset-0 grid place-items-center">
                            <span x-show="!playing" class="grid h-16 w-16 place-items-center rounded-full border border-white/20 bg-white/15 text-white backdrop-blur-md">
                                <x-icon name="play" class="h-6 w-6" />
                            </span>
                        </div>

                        <div class="pointer-events-none absolute inset-x-0 bottom-0 p-5">
                            <h2 class="text-xl font-extrabold leading-tight tracking-tight" x-text="'{{ $short->title }}'"></h2>
                            <p class="mt-1 text-xs leading-relaxed text-white/65">{{ $short->subtitle }}</p>

                            <div class="mt-4 flex items-center justify-between gap-3">
                                <button
                                    type="button"
                                    class="pointer-events-auto inline-flex min-h-11 items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-extrabold text-white backdrop-blur transition active:scale-95"
                                    :class="liked ? 'border-rose/40 bg-rose/20 text-rose-100' : 'hover:bg-surface/15'"
                                    @click.stop="like"
                                    :aria-pressed="liked"
                                    :aria-label="(liked ? 'Убрать лайк' : 'Поставить лайк') + ', ' + likes"
                                    :disabled="busy"
                                >
                                    <x-icon name="heart" class="h-5 w-5" x-bind:class="liked ? 'fill-current' : ''" />
                                    <span x-text="likes"></span>
                                </button>
                                <a href="{{ route('credits') }}" class="pointer-events-auto text-[10px] font-semibold text-white/70 underline decoration-white/30 underline-offset-2">credits</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <p class="mt-5 text-center text-[11px] leading-relaxed text-muted sm:text-left">
                Видео без звука и без комментариев. Лайк — не оценка, а сигнал «мне откликается».
            </p>
        @endif
    </div>
@endsection

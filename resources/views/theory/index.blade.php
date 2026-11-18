@extends('layouts.app')

@section('title', 'Теория')

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="section-eyebrow">Теория</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ tone('theory.index.title') }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">{{ tone('theory.index.lead') }}</p>
        </div>
        <span class="chip shrink-0">{{ $readIds->count() }}/{{ $articles->count() }} прочитано</span>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        @foreach(['Модели ЭИ', 'Эмоции и тело', 'Дневник', 'Регуляция', 'Эмпатия', 'Коммуникация', 'Стресс', 'Решения', 'Измерения', 'Этика'] as $topic)
            <span class="chip">{{ $topic }}</span>
        @endforeach
    </div>

    @if($articles->isEmpty())
        <x-empty-state icon="book" title="Материалов пока нет" :text="tone('theory.index.empty')" class="mt-8">
            <a href="{{ route('modules.index') }}" class="btn-primary px-4 py-2.5 text-xs">К модулям</a>
        </x-empty-state>
    @else
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($articles as $article)
                @php $isRead = $readIds->contains($article->id); @endphp
                <a href="{{ route('theory.show', $article) }}" class="card group flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-soft">
                    <div class="relative aspect-[16/9] bg-gradient-to-br from-brand-soft via-paper to-lilac-soft">
                        @if($article->cover_path)
                            <x-photo :path="$article->cover_path" credit="overlay" class="h-full" img-class="h-full w-full object-cover" />
                        @else
                            <div class="flex h-full items-center justify-center text-3xl">📖</div>
                        @endif
                        @if($isRead)
                            <span class="absolute right-3 top-3 chip border-mint/20 bg-mint-soft text-mint-ink"><x-icon name="check" class="h-3 w-3" /> прочитано</span>
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.14em] text-muted">
                            <span>{{ $article->reading_minutes }} мин</span>
                            @if($article->hasVideo())
                                <span>· видео</span>
                            @endif
                        </div>
                        <h2 class="mt-2 text-base font-extrabold leading-snug">{{ $article->title }}</h2>
                        <p class="mt-1 flex-1 text-xs leading-relaxed text-muted">{{ $article->excerpt }}</p>
                        <p class="mt-3 text-[11px] font-bold text-brand">Читать →</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection

@extends('layouts.admin')

@section('title', 'Теория')

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="section-eyebrow">Контент</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight">Теория</h1>
            <p class="mt-1 text-sm text-muted">Статьи и видео. Локальные MP4 и Rutube-ссылки встраиваются автоматически.</p>
        </div>
        <a href="{{ route('admin.theory.create') }}" class="btn-primary px-4 py-2.5 text-xs">Добавить материал</a>
    </div>

    <div class="mt-5 space-y-3">
        @forelse($articles as $article)
            <div class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-extrabold">{{ $article->title }}</p>
                        <span class="chip">{{ $article->reading_minutes }} мин</span>
                        @if($article->hasVideo())
                            <span class="chip">{{ $article->video_path ? 'локальное видео' : 'Rutube' }}</span>
                        @endif
                        @if($article->source_url)
                            <span class="chip">ссылка</span>
                        @endif
                        <span class="chip {{ $article->is_published ? 'border-mint/20 bg-mint-soft text-emerald-700' : 'text-muted' }}">
                            {{ $article->is_published ? 'опубликовано' : 'черновик' }}
                        </span>
                    </div>
                    <p class="mt-1 text-[11px] text-muted">{{ $article->slug }} · {{ $article->progress_count }} прочтений</p>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <a href="{{ route('theory.show', $article) }}" class="btn-ghost px-3 py-2 text-[10px]">Открыть</a>
                    <a href="{{ route('admin.theory.edit', $article) }}" class="btn-secondary px-3 py-2 text-[10px]">Изменить</a>
                    <form method="POST" action="{{ route('admin.theory.toggle', $article) }}">
                        @csrf
                        <button class="btn-ghost px-2 py-2 text-[10px]">{{ $article->is_published ? 'Скрыть' : 'Опубликовать' }}</button>
                    </form>
                </div>
            </div>
        @empty
            <x-empty-state icon="book" title="Материалов пока нет" text="Добавьте первую статью." />
        @endforelse
    </div>
@endsection

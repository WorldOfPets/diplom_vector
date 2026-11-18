@extends('layouts.admin')

@section('title', $article ? 'Редактирование материала' : 'Новый материал')

@php
    $action = $article
        ? route('admin.theory.update', $article)
        : route('admin.theory.store');
@endphp

@section('content')
    <nav class="flex items-center gap-1.5 text-[11px] font-bold text-muted">
        <a href="{{ route('admin.theory.index') }}" class="hover:text-ink">Теория</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <span class="text-ink-soft">{{ $article ? $article->title : 'Новый материал' }}</span>
    </nav>

    <h1 class="mt-2 text-2xl font-extrabold tracking-tight">{{ $article ? $article->title : 'Новый материал' }}</h1>

    <form method="POST" action="{{ $action }}" class="mt-5 grid gap-5 lg:grid-cols-[1.3fr_0.7fr]">
        @csrf
        @if($article)
            @method('PUT')
        @endif

        <div class="space-y-4">
            <div class="card p-5">
                <div class="grid gap-4 sm:grid-cols-[1.4fr_1fr]">
                    <div>
                        <label class="label">Заголовок</label>
                        <input name="title" value="{{ old('title', $article?->title) }}" class="input" required>
                    </div>
                    <div>
                        <label class="label">Slug</label>
                        <input name="slug" value="{{ old('slug', $article?->slug) }}" class="input" placeholder="автоматически">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="label">Краткое описание</label>
                    <textarea name="excerpt" rows="2" class="textarea">{{ old('excerpt', $article?->excerpt) }}</textarea>
                </div>
                <div class="mt-4">
                    <label class="label">Текст (Markdown)</label>
                    <textarea name="body" rows="16" class="textarea font-mono text-[11px]">{{ old('body', $article?->body) }}</textarea>
                </div>
            </div>

            <div class="card p-5">
                <p class="text-sm font-bold">Медиа и ссылки</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Обложка (путь)</label>
                        <input name="cover_path" value="{{ old('cover_path', $article?->cover_path) }}" class="input" placeholder="media/photos/lecture.jpg">
                    </div>
                    <div>
                        <label class="label">Rutube</label>
                        <input name="video_url" value="{{ old('video_url', $article?->video_url) }}" class="input" placeholder="https://rutube.ru/video/...">
                        <p class="mt-1 text-[10px] leading-relaxed text-muted">Просмотр засчитывается после 80% видео. Поддерживается Rutube embed.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Первоисточник</label>
                        <input name="source_url" value="{{ old('source_url', $article?->source_url) }}" class="input" placeholder="https://...">
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="card p-5">
                <p class="text-sm font-bold">Параметры</p>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="label">Минут чтения</label>
                        <input type="number" min="1" max="120" name="reading_minutes" value="{{ old('reading_minutes', $article?->reading_minutes ?? 5) }}" class="input">
                    </div>
                    <div>
                        <label class="label">Сортировка</label>
                        <input type="number" min="0" max="1000" name="sort" value="{{ old('sort', $article?->sort ?? 0) }}" class="input">
                    </div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-ink-soft">
                        <input type="checkbox" name="is_published" value="1" class="h-4 w-4 rounded border-line text-brand" @checked(old('is_published', $article?->is_published ?? true))>
                        Опубликовано
                    </label>
                </div>
                <button class="btn-primary mt-4 w-full py-2.5 text-xs">Сохранить</button>
            </div>

            <div class="rounded-3xl border border-line/60 bg-white/50 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-muted">Markdown</p>
                <p class="mt-2 text-xs leading-relaxed text-ink-soft">Поддерживаются заголовки, списки, цитаты, ссылки и выделение. HTML вырезается.</p>
            </div>
        </div>
    </form>
@endsection

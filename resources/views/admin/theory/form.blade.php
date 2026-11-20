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

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="mt-5 grid gap-5 lg:grid-cols-[1.3fr_0.7fr]">
        @csrf
        @if($article)
            @method('PUT')
        @endif

        <div class="space-y-4">
            <div class="card p-5">
                <div class="grid gap-4 sm:grid-cols-[1.4fr_1fr]">
                    <div>
                        <label class="label" for="title">Заголовок</label>
                        <input id="title" name="title" value="{{ old('title', $article?->title) }}" class="input" required>
                    </div>
                    <div>
                        <label class="label" for="slug">Slug</label>
                        <input id="slug" name="slug" value="{{ old('slug', $article?->slug) }}" class="input" placeholder="автоматически">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="label" for="excerpt">Краткое описание</label>
                    <textarea id="excerpt" name="excerpt" rows="2" class="textarea">{{ old('excerpt', $article?->excerpt) }}</textarea>
                </div>
                <div class="mt-4">
                    <label class="label" for="body">Текст (Markdown)</label>
                    <textarea id="body" name="body" rows="16" class="textarea font-mono text-[11px]">{{ old('body', $article?->body) }}</textarea>
                </div>
            </div>

            <div class="card p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-bold">Задание с пропусками</p>
                    @if($article?->hasTask())
                        <label class="flex items-center gap-1.5 text-[10px] font-semibold text-muted">
                            <input type="checkbox" name="remove_task" value="1" class="h-3.5 w-3.5 rounded border-line text-rose">
                            удалить задание
                        </label>
                    @endif
                </div>
                <p class="mt-1 text-[10px] leading-relaxed text-muted">
                    В тексте используйте пропуски &#123;&#123;1&#125;&#125;, &#123;&#123;2&#125;&#125; и так далее. Ответы — по одному на строку в формате <span class="font-mono">1=слово</span>. Без задания материал даст только два шага прогресса.
                </p>
                <div class="mt-4">
                    <label class="label" for="task_text">Краткое содержание с пропусками</label>
                    <textarea id="task_text" name="task_text" rows="5" class="textarea">{{ old('task_text', data_get($article?->task, 'text', '')) }}</textarea>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="task_answers">Ответы</label>
                        <textarea id="task_answers" name="task_answers" rows="4" class="textarea font-mono text-[11px]" placeholder="1=слово&#10;2=другое">{{ old('task_answers', collect(data_get($article?->task, 'answers', []))->map(fn ($answer, $key) => $key.'='.$answer)->implode("\n")) }}</textarea>
                    </div>
                    <div>
                        <label class="label" for="task_options">Слова для выбора</label>
                        <textarea id="task_options" name="task_options" rows="4" class="textarea" placeholder="слово, другое, лишнее">{{ old('task_options', implode(', ', data_get($article?->task, 'options', []))) }}</textarea>
                        <p class="mt-1 text-[10px] leading-relaxed text-muted">Через запятую. Добавьте 2–4 лишних слова.</p>
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <p class="text-sm font-bold">Медиа и ссылки</p>
                <div class="mt-4">
                    <label class="label" for="slides_text">Слайды презентации (пути, по одному на строку)</label>
                    <textarea id="slides_text" name="slides_text" rows="6" class="textarea font-mono text-[11px]" placeholder="media/slides/deck-04/slide-01.jpg&#10;media/slides/deck-04/slide-02.jpg">{{ old('slides_text', implode("\n", $article?->slidesList() ?? [])) }}</textarea>
                    <p class="mt-1 text-[10px] leading-relaxed text-muted">
                        Если список заполнен, теория показывает презентацию вместо видео, а первый слайд становится обложкой. Каждый слайд засчитывается после 15 секунд просмотра; вся колода — 1 единица прогресса.
                    </p>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="cover_path">Резервная обложка (путь)</label>
                        <input id="cover_path" name="cover_path" value="{{ old('cover_path', $article?->cover_path) }}" class="input" placeholder="media/photos/lecture.jpg">
                        <p class="mt-1 text-[10px] leading-relaxed text-muted">Используется, только если слайдов нет.</p>
                    </div>
                    <div>
                        <label class="label" for="video_url">Rutube (устарело)</label>
                        <input id="video_url" name="video_url" value="{{ old('video_url', $article?->video_url) }}" class="input" placeholder="https://rutube.ru/video/...">
                        <p class="mt-1 text-[10px] leading-relaxed text-muted">Показывается только у материалов без слайдов.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="video_file">Локальное видео (MP4 / WebM)</label>
                        <input id="video_file" type="file" name="video_file" accept="video/mp4,video/webm" class="input file:mr-3 file:rounded-full file:border-0 file:bg-paper-deep file:px-3 file:py-1.5 file:text-xs file:font-bold">
                        <p class="mt-1 text-[10px] leading-relaxed text-muted">Устаревший формат. Для новых материалов загружайте слайды.</p>
                        @if($article?->video_path)
                            <div class="mt-3 flex flex-wrap items-center gap-3 rounded-2xl border border-line bg-paper/60 px-3 py-2">
                                <span class="text-[11px] font-semibold text-ink-soft">Сейчас: {{ $article->video_path }}</span>
                                <a href="{{ asset($article->video_path) }}" target="_blank" rel="noopener" class="text-[11px] font-bold text-brand underline">открыть</a>
                                <label class="ml-auto flex items-center gap-1.5 text-[11px] font-semibold text-ink-soft">
                                    <input type="checkbox" name="remove_video" value="1" class="h-3.5 w-3.5 rounded border-line text-rose">
                                    удалить
                                </label>
                            </div>
                        @endif
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="source_url">Первоисточник</label>
                        <input id="source_url" name="source_url" value="{{ old('source_url', $article?->source_url) }}" class="input" placeholder="https://...">
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="card p-5">
                <p class="text-sm font-bold">Параметры</p>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="label" for="reading_minutes">Минут чтения</label>
                        <input id="reading_minutes" type="number" min="1" max="120" name="reading_minutes" value="{{ old('reading_minutes', $article?->reading_minutes ?? 5) }}" class="input">
                    </div>
                    <div>
                        <label class="label" for="sort">Сортировка</label>
                        <input id="sort" type="number" min="0" max="1000" name="sort" value="{{ old('sort', $article?->sort ?? 0) }}" class="input">
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

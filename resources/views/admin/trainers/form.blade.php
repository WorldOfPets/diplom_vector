@extends('layouts.admin')

@section('title', $trainer ? 'Редактирование тренажёра' : 'Новый тренажёр')

@php
    $action = $trainer
        ? route('admin.trainers.update', $trainer)
        : route('admin.trainers.store');
    $selectedEngine = $selectedEngine ?? $trainer?->engine ?? 'reflection';
    $engineUrl = $trainer
        ? route('admin.trainers.edit', $trainer)
        : route('admin.trainers.create');
    $formConfig = $config;
    if (old('config_json')) {
        $decodedConfig = json_decode(old('config_json'), true);
        if (is_array($decodedConfig)) {
            $formConfig = $decodedConfig;
        }
    }
@endphp

@section('content')
    <nav class="flex items-center gap-1.5 text-[11px] font-bold text-muted">
        <a href="{{ route('admin.trainers.index') }}" class="hover:text-ink">Тренажёры</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <span class="text-ink-soft">{{ $trainer ? $trainer->title : 'Новый' }}</span>
    </nav>

    <div class="mt-2 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">{{ $trainer ? $trainer->title : 'Новый тренажёр' }}</h1>
            @if($trainer)
                <p class="mt-1 text-xs text-muted">{{ $trainer->module?->title }} · {{ $trainer->engine }} · v{{ $trainer->version }}</p>
            @endif
        </div>
        @if($trainer)
            <div class="flex gap-2">
                <a href="{{ route('trainers.show', $trainer) }}" class="btn-secondary px-4 py-2.5 text-xs">Предпросмотр</a>
                <form method="POST" action="{{ route('admin.trainers.toggle', $trainer) }}">
                    @csrf
                    <button class="btn-ghost px-3 py-2.5 text-xs">{{ $trainer->is_published ? 'Скрыть' : 'Опубликовать' }}</button>
                </form>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ $action }}" class="mt-5 grid gap-5 lg:grid-cols-[1.25fr_0.75fr]">
        @csrf
        @if($trainer)
            @method('PUT')
        @endif

        <div class="space-y-4">
            <div class="card p-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Модуль</label>
                        <select name="module_id" class="input">
                            @foreach($modules as $module)
                                <option value="{{ $module->id }}" @selected((int) old('module_id', $trainer?->module_id) === $module->id)>{{ $module->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Движок</label>
                        <select name="engine" class="input" onchange="window.location='{{ $engineUrl }}?engine=' + encodeURIComponent(this.value)">
                            @foreach($engines as $key => $label)
                                <option value="{{ $key }}" @selected(old('engine', $selectedEngine) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[10px] text-muted">При смене движка форма перезагрузится.</p>
                    </div>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-[1.5fr_1fr]">
                    <div>
                        <label class="label">Название</label>
                        <input name="title" value="{{ old('title', $trainer?->title) }}" class="input" required>
                    </div>
                    <div>
                        <label class="label">Slug</label>
                        <input name="slug" value="{{ old('slug', $trainer?->slug) }}" class="input" placeholder="автоматически">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="label">Подзаголовок</label>
                    <input name="subtitle" value="{{ old('subtitle', $trainer?->subtitle) }}" class="input" maxlength="255">
                </div>
                <div class="mt-4">
                    <label class="label">Описание</label>
                    <textarea name="description" rows="3" class="textarea">{{ old('description', $trainer?->description) }}</textarea>
                </div>
                <div class="mt-4">
                    <label class="label">Обложка (путь)</label>
                    <input name="cover_path" value="{{ old('cover_path', $trainer?->cover_path) }}" class="input" placeholder="media/photos/workshop.jpg">
                    @if(old('cover_path', $trainer?->cover_path))
                        <div class="mt-2 h-24 overflow-hidden rounded-xl border border-line">
                            <img src="{{ asset(old('cover_path', $trainer?->cover_path)) }}" alt="" class="h-full w-full object-cover">
                        </div>
                    @endif
                    <p class="mt-1 text-[10px] text-muted">Путь относительно public. Авторы и лицензии — в разделе «Фото и лицензии».</p>
                </div>
            </div>

            @if($trainer && $trainer->engine !== $selectedEngine)
                <x-alert type="warning">Выбран другой движок. Конфигурация будет собрана заново; старые значения не переносятся.</x-alert>
            @endif

            <div class="card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold">Содержание тренажёра</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-muted">Поля зависят от движка: текст, карточки, шкалы, варианты и ключи.</p>
                    </div>
                    <span class="chip">{{ $selectedEngine }}</span>
                </div>
            </div>

            @include('admin.partials.schema-editor', ['schema' => $schema, 'config' => $formConfig])
        </div>

        <div class="space-y-4">
            <div class="card p-5">
                <p class="text-sm font-bold">Параметры</p>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="label">Порог прохождения, %</label>
                        <input type="number" min="0" max="100" name="threshold" value="{{ old('threshold', $trainer?->settings['threshold'] ?? 0) }}" class="input">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Минут</label>
                            <input type="number" min="1" max="120" name="duration_min" value="{{ old('duration_min', $trainer?->duration_min ?? 5) }}" class="input">
                        </div>
                        <div>
                            <label class="label">XP</label>
                            <input type="number" min="0" max="1000" name="points" value="{{ old('points', $trainer?->points ?? 10) }}" class="input">
                        </div>
                    </div>
                    <div>
                        <label class="label">Сортировка</label>
                        <input type="number" min="0" max="1000" name="sort" value="{{ old('sort', $trainer?->sort ?? 0) }}" class="input">
                    </div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-ink-soft">
                        <input type="checkbox" name="is_published" value="1" class="h-4 w-4 rounded border-line text-brand" @checked(old('is_published', $trainer?->is_published ?? true))>
                        Опубликован
                    </label>
                </div>
                <button class="btn-primary mt-4 w-full py-2.5 text-xs">Сохранить</button>
            </div>

            <div class="rounded-3xl border border-line/60 bg-white/50 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-muted">Подсказка</p>
                <p class="mt-2 text-xs leading-relaxed text-ink-soft"><strong>reflection</strong> — карточки, шкалы и действия. <strong>ranking</strong> — порядок. <strong>allocation</strong> — бюджет. <strong>tag_weight</strong> — теги. <strong>scenario</strong> — ветвящийся диалог. <strong>categorize</strong> — категории. <strong>shield</strong> — герб. <strong>timeline</strong> — этапы.</p>
                <p class="mt-2 text-xs leading-relaxed text-muted">Если нужен новый тип взаимодействия — это уже разработка, а не контент.</p>
            </div>
        </div>
    </form>
@endsection

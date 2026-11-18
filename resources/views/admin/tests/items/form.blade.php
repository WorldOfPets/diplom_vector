@extends('layouts.admin')

@section('title', $item ? 'Задание #'.$item->id : 'Новое задание')

@php
    $selectedType = $selectedType ?? $item?->type ?? 'single';
    $config = $config ?? [];
    $key = $key ?? [];
    $typeLabels = [
        'single' => 'Один ответ',
        'multi' => 'Несколько ответов',
        'scene' => 'Сцена + эмоции',
        'image_choice' => 'Сцена + выбор',
        'best_worst' => 'Лучшая/худшая',
        'ordering' => 'Порядок',
        'matching' => 'Сопоставление',
        'scale' => 'Шкала',
        'motivation' => 'Мотивация',
    ];
    $action = $item
        ? route('admin.tests.items.update', [$test, $item])
        : route('admin.tests.items.store', $test);
    $typeUrl = $item
        ? route('admin.tests.items.edit', [$test, $item])
        : route('admin.tests.items.create', $test);
    $formConfig = $config;
    $formKey = $key;
    if (old('config_json')) {
        $decoded = json_decode(old('config_json'), true);
        if (is_array($decoded)) {
            $formConfig = $decoded;
        }
    }
    if (old('key_json')) {
        $decoded = json_decode(old('key_json'), true);
        if (is_array($decoded)) {
            $formKey = $decoded;
        }
    }
@endphp

@section('content')
    <nav class="flex items-center gap-1.5 text-[11px] font-bold text-muted">
        <a href="{{ route('admin.tests.index') }}" class="hover:text-ink">Тесты</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <a href="{{ route('admin.tests.show', $test) }}" class="hover:text-ink">{{ $test->title }}</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <span class="text-ink-soft">{{ $item ? 'Задание #'.$item->id : 'Новое' }}</span>
    </nav>

    <h1 class="mt-2 text-2xl font-extrabold tracking-tight">{{ $item ? 'Редактирование задания' : 'Новое задание' }}</h1>

    <form method="POST" action="{{ $action }}" class="mt-5 grid gap-5 lg:grid-cols-[1.3fr_0.7fr]">
        @csrf
        @if($item)
            @method('PUT')
        @endif

        <div class="space-y-4">
            <div class="card p-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Ветвь</label>
                        <input name="branch" value="{{ old('branch', $item?->branch) }}" class="input" placeholder="perception / managing / internal" required>
                    </div>
                    <div>
                        <label class="label">Тип</label>
                        <select name="type" class="input" onchange="window.location='{{ $typeUrl }}?type=' + encodeURIComponent(this.value)">
                            @foreach($types as $type)
                                <option value="{{ $type }}" @selected(old('type', $selectedType) === $type)>{{ $typeLabels[$type] ?? $type }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[10px] text-muted">При смене типа форма перезагрузится.</p>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="label">Формулировка</label>
                    <textarea name="prompt" rows="3" class="textarea" required>{{ old('prompt', $item?->prompt) }}</textarea>
                </div>
                <div class="mt-4">
                    <label class="label">Путь к изображению</label>
                    <input name="media_path" value="{{ old('media_path', $item?->media_path) }}" class="input" placeholder="media/photos/conflict-laptop.jpg">
                    @if(old('media_path', $item?->media_path))
                        <div class="mt-2 h-28 overflow-hidden rounded-xl border border-line">
                            <img src="{{ asset(old('media_path', $item?->media_path)) }}" alt="" class="h-full w-full object-cover">
                        </div>
                    @endif
                    <p class="mt-1 text-[10px] text-muted">Только для сцен. Автор и лицензия подтягиваются из config/photos.php.</p>
                </div>
            </div>

            @include('admin.tests.items.builder', ['type' => $selectedType, 'config' => $formConfig, 'key' => $formKey])
        </div>

        <div class="space-y-4">
            <div class="card p-5">
                <p class="text-sm font-bold">Параметры</p>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="label">Вес</label>
                        <input type="number" step="0.1" min="0.1" max="10" name="weight" value="{{ old('weight', $item?->weight ?? 1) }}" class="input">
                    </div>
                    <div>
                        <label class="label">Сложность</label>
                        <select name="difficulty" class="input">
                            @for($level = 1; $level <= 3; $level++)
                                <option value="{{ $level }}" @selected((int) old('difficulty', $item?->difficulty ?? 2) === $level)>{{ $level }}</option>
                            @endfor
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-ink-soft">
                        <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-line text-brand" @checked(old('is_active', $item?->is_active ?? true))>
                        Активно
                    </label>
                </div>
                <button class="btn-primary mt-4 w-full py-2.5 text-xs">Сохранить задание</button>
            </div>

            <div class="rounded-3xl border border-line/60 bg-white/50 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-muted">Подсказка</p>
                <p class="mt-2 text-xs leading-relaxed text-ink-soft">Ключ не уходит в браузер студента. Для сцены вес эмоции — вклад в правильный ответ; дистракторы оставьте с весом 0.</p>
                <p class="mt-2 text-xs leading-relaxed text-muted">Для сложных типов (сценарии, произвольные ключи) остаётся JSON-поле внутри визуального редактора.</p>
            </div>
        </div>
    </form>
@endsection

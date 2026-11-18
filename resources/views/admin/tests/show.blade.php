@extends('layouts.admin')

@section('title', $test->title)

@php
    $typeLabels = [
        'single' => 'Один ответ',
        'multi' => 'Несколько ответов',
        'scene' => 'Сцена',
        'best_worst' => 'Лучшая/худшая',
        'ordering' => 'Порядок',
        'matching' => 'Сопоставление',
        'scale' => 'Шкала',
        'motivation' => 'Мотивация',
    ];
@endphp

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <nav class="flex items-center gap-1.5 text-[11px] font-bold text-muted">
                <a href="{{ route('admin.tests.index') }}" class="hover:text-ink">Тесты</a>
                <x-icon name="chevron-right" class="h-3 w-3" />
                <span class="text-ink-soft">{{ $test->title }}</span>
            </nav>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">{{ $test->title }}</h1>
            <p class="mt-1 text-sm text-muted">{{ $test->items->count() }} {{ \App\Support\Text::plural($test->items->count(), 'задание', 'задания', 'заданий') }} · {{ $attemptsCount }} {{ \App\Support\Text::plural($attemptsCount, 'завершённая попытка', 'завершённые попытки', 'завершённых попыток') }} · v{{ $test->version }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.tests.items.create', $test) }}" class="btn-primary px-4 py-2.5 text-xs">Добавить задание</a>
            <a href="{{ route('tests.show', $test) }}" class="btn-secondary px-4 py-2.5 text-xs">Предпросмотр</a>
        </div>
    </div>

    <div class="card mt-5 overflow-hidden">
        <details class="group">
            <summary class="flex cursor-pointer items-center justify-between px-5 py-4">
                <div>
                    <p class="section-eyebrow">Настройки</p>
                    <h2 class="mt-1 text-lg font-extrabold">Название, описание и время</h2>
                </div>
                <x-icon name="chevron-down" class="h-4 w-4 text-muted transition group-open:rotate-180" />
            </summary>
            <form method="POST" action="{{ route('admin.tests.update', $test) }}" class="grid gap-3 border-t border-line/70 p-5 sm:grid-cols-2">
                @csrf
                @method('PUT')
                <div class="sm:col-span-2">
                    <label class="label">Название</label>
                    <input name="title" value="{{ $test->title }}" class="input" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Подзаголовок</label>
                    <input name="subtitle" value="{{ $test->subtitle }}" class="input">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Описание</label>
                    <textarea name="description" rows="3" class="textarea">{{ $test->description }}</textarea>
                </div>
                <div>
                    <label class="label">Время, мин</label>
                    <input type="number" min="1" max="180" name="time_limit_min" value="{{ $test->time_limit_min ?? 30 }}" class="input">
                </div>
                <div class="flex items-end">
                    <button class="btn-primary w-full py-3 text-xs">Сохранить настройки</button>
                </div>
            </form>
        </details>
    </div>

    <div class="card mt-5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-xs">
                <thead class="border-b border-line/70 bg-paper/60 text-[10px] font-bold uppercase tracking-[0.12em] text-muted">
                    <tr>
                        <th scope="col" class="px-4 py-3">#</th>
                        <th scope="col" class="px-4 py-3">Ветвь</th>
                        <th scope="col" class="px-4 py-3">Тип</th>
                        <th scope="col" class="px-4 py-3">Формулировка</th>
                        <th scope="col" class="px-4 py-3">Слож.</th>
                        <th scope="col" class="px-4 py-3">Статус</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Действия</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($test->items as $item)
                        <tr class="align-top hover:bg-paper/40">
                            <td class="px-4 py-3 font-bold text-muted">{{ $item->sort }}</td>
                            <td class="px-4 py-3"><span class="chip">{{ $item->branch }}</span></td>
                            <td class="px-4 py-3 text-muted">{{ $typeLabels[$item->type] ?? $item->type }}</td>
                            <td class="max-w-xl px-4 py-3">
                                <p class="font-semibold text-ink-soft">{{ \Illuminate\Support\Str::limit($item->prompt, 130) }}</p>
                                @if($item->media_path)
                                    <p class="mt-1 text-[10px] text-muted">{{ $item->media_path }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-muted">{{ $item->difficulty }}/3</td>
                            <td class="px-4 py-3">
                                <span class="chip {{ $item->is_active ? 'border-mint/20 bg-mint-soft text-emerald-700' : 'text-muted' }}">
                                    {{ $item->is_active ? 'активно' : 'скрыто' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    <a href="{{ route('admin.tests.items.edit', [$test, $item]) }}" class="btn-secondary px-3 py-1.5 text-[10px]">Изменить</a>
                                    <form method="POST" action="{{ route('admin.tests.items.toggle', [$test, $item]) }}">
                                        @csrf
                                        <button class="btn-ghost px-2 py-1.5 text-[10px]">{{ $item->is_active ? 'Скрыть' : 'Вернуть' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-muted">Заданий пока нет.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@extends('layouts.admin')

@section('title', 'Тренажёры')

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="section-eyebrow">Контент</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight">Тренажёры</h1>
            <p class="mt-1 text-sm text-muted">{{ $trainers->count() }} {{ \App\Support\Text::plural($trainers->count(), 'упражнение', 'упражнения', 'упражнений') }}. Конфигурация хранится в JSON, движок определяет поведение.</p>
        </div>
        <a href="{{ route('admin.trainers.create') }}" class="btn-primary px-4 py-2.5 text-xs">Добавить тренажёр</a>
    </div>

    <form method="GET" class="card mt-5 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        <div class="sm:w-72">
            <label class="label">Модуль</label>
            <select name="module_id" class="input">
                <option value="">Все модули</option>
                @foreach($modules as $module)
                    <option value="{{ $module->id }}" @selected((int) request('module_id') === $module->id)>{{ $module->title }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn-primary px-5 py-3 text-xs">Фильтр</button>
    </form>

    <div class="card mt-5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-xs">
                <thead class="border-b border-line/70 bg-paper/60 text-[10px] font-bold uppercase tracking-[0.12em] text-muted">
                    <tr>
                        <th class="px-4 py-3">Модуль</th>
                        <th class="px-4 py-3">Название</th>
                        <th class="px-4 py-3">Движок</th>
                        <th class="px-4 py-3">Попытки</th>
                        <th class="px-4 py-3">Статус</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @foreach($trainers as $trainer)
                        <tr class="hover:bg-paper/40">
                            <td class="px-4 py-3 text-muted">{{ $trainer->module?->title }}</td>
                            <td class="px-4 py-3">
                                <p class="font-bold text-ink">{{ $trainer->title }}</p>
                                <p class="mt-0.5 text-[10px] text-muted">{{ $trainer->slug }} · v{{ $trainer->version }}</p>
                            </td>
                            <td class="px-4 py-3"><span class="chip">{{ $trainer->engine }}</span></td>
                            <td class="px-4 py-3 text-muted">{{ $trainer->attempts_count }}</td>
                            <td class="px-4 py-3">
                                <span class="chip {{ $trainer->is_published ? 'border-mint/20 bg-mint-soft text-emerald-700' : 'text-muted' }}">
                                    {{ $trainer->is_published ? 'опубликован' : 'черновик' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    <a href="{{ route('admin.trainers.edit', $trainer) }}" class="btn-secondary px-3 py-1.5 text-[10px]">Изменить</a>
                                    <a href="{{ route('trainers.show', $trainer) }}" class="btn-ghost px-2 py-1.5 text-[10px]">Открыть</a>
                                    <form method="POST" action="{{ route('admin.trainers.toggle', $trainer) }}">
                                        @csrf
                                        <button class="btn-ghost px-2 py-1.5 text-[10px]">{{ $trainer->is_published ? 'Скрыть' : 'Опубликовать' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

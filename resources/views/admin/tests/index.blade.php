@extends('layouts.admin')

@section('title', 'Тесты')

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
            <p class="section-eyebrow">Диагностика</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight">Тесты и задания</h1>
            <p class="mt-1 text-sm text-muted">Три инструмента ЭИ и опросник мотивации. Ключи хранятся отдельно от публичных данных.</p>
        </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        @foreach($tests as $test)
            <div class="card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <span class="chip">{{ $test->kind }}</span>
                        <h2 class="mt-2 text-lg font-extrabold">{{ $test->title }}</h2>
                        <p class="mt-1 text-xs leading-relaxed text-muted">{{ $test->subtitle }}</p>
                    </div>
                    <span class="chip {{ $test->is_active ? 'border-mint/20 bg-mint-soft text-emerald-700' : 'border-line text-muted' }}">
                        {{ $test->is_active ? 'активен' : 'выключен' }}
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="chip">{{ $test->items_count }} {{ \App\Support\Text::plural($test->items_count, 'задание', 'задания', 'заданий') }}</span>
                    <span class="chip">{{ $test->attempts_count }} {{ \App\Support\Text::plural($test->attempts_count, 'попытка', 'попытки', 'попыток') }}</span>
                    <span class="chip">v{{ $test->version }}</span>
                    @if($test->time_limit_min)
                        <span class="chip">{{ $test->time_limit_min }} мин</span>
                    @endif
                </div>

                <div class="mt-4 flex flex-wrap gap-2 border-t border-line/60 pt-4">
                    <a href="{{ route('admin.tests.show', $test) }}" class="btn-primary px-4 py-2 text-xs">Задания</a>
                    <a href="{{ route('tests.show', $test) }}" class="btn-secondary px-4 py-2 text-xs">Предпросмотр</a>
                    <form method="POST" action="{{ route('admin.tests.toggle', $test) }}">
                        @csrf
                        <button class="btn-ghost px-3 py-2 text-xs">{{ $test->is_active ? 'Выключить' : 'Включить' }}</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endsection

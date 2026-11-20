@extends('layouts.admin')

@section('title', 'Качество тренажёров')

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="section-eyebrow">Обратная связь</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight">Качество тренажёров</h1>
            <p class="mt-1 text-sm text-muted">Оценки после упражнений: средний балл, доля низких оценок и комментарии студентов.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.export.feedback') }}" class="btn-secondary px-4 py-2.5 text-xs">Оценки CSV</a>
            <a href="{{ route('admin.analytics.index') }}" class="btn-secondary px-4 py-2.5 text-xs">Аналитика заданий</a>
        </div>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Оценок" :value="$total" hint="всего отзывов" icon="star" tone="amber" />
        <x-stat label="Средний балл" :value="$average !== null ? number_format($average, 2, ',', ' ') : '—'" hint="из 5" icon="chart" tone="brand" />
        <x-stat label="Ниже 3" :value="$low" hint="требуют внимания" icon="warning" tone="coral" />
        <x-stat label="С комментарием" :value="$withComment" hint="разборов от студентов" icon="edit" tone="lilac" />
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_1.4fr]">
        <section class="card p-5">
            <p class="section-eyebrow">Распределение</p>
            <h2 class="mt-1 text-lg font-extrabold">Оценки 1–5</h2>

            <div class="mt-4 space-y-2.5">
                @foreach([5, 4, 3, 2, 1] as $rating)
                    @php
                        $count = (int) ($distribution[$rating] ?? 0);
                        $share = $total > 0 ? round($count / $total * 100) : 0;
                    @endphp
                    <div class="flex items-center gap-3">
                        <span class="w-8 text-xs font-extrabold {{ $rating < 3 ? 'text-coral-ink' : 'text-ink' }}">{{ $rating }} ★</span>
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-paper-deep">
                            <div class="h-full rounded-full {{ $rating < 3 ? 'bg-coral' : 'bg-mint' }}" style="width: {{ $share }}%;"></div>
                        </div>
                        <span class="w-16 text-right text-[11px] font-semibold text-muted">{{ $count }} · {{ $share }}%</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 rounded-2xl border border-line/70 bg-paper/50 p-4">
                <p class="text-[11px] font-bold text-muted">Охват обратной связи</p>
                <p class="mt-1 text-2xl font-extrabold">{{ $responseRate }}%</p>
                <p class="mt-1 text-[10px] leading-relaxed text-muted">{{ $total }} из {{ $completedAttempts }} завершённых попыток оценены. Низкая оценка требует комментария — это даёт материал для доработки упражнений.</p>
            </div>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-line/70 px-5 py-4">
                <p class="section-eyebrow">Тренажёры</p>
                <h2 class="mt-1 text-lg font-extrabold">Средний балл по упражнениям</h2>
            </div>
            <div class="max-h-[420px] overflow-auto">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 border-b border-line/70 bg-paper/80 text-[10px] font-bold uppercase tracking-[0.12em] text-muted backdrop-blur">
                        <tr>
                            <th class="px-4 py-2.5">Упражнение</th>
                            <th class="px-3 py-2.5">Оценок</th>
                            <th class="px-3 py-2.5">Средний</th>
                            <th class="px-3 py-2.5">Ниже 3</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/60">
                        @forelse($trainerStats as $row)
                            <tr class="hover:bg-paper/40">
                                <td class="px-4 py-2.5">
                                    <p class="font-bold">{{ $row['trainer']->title }}</p>
                                    <p class="text-[10px] text-muted">{{ $row['trainer']->module?->title }}</p>
                                </td>
                                <td class="px-3 py-2.5 text-muted">{{ $row['count'] }}</td>
                                <td class="px-3 py-2.5 font-extrabold {{ $row['average'] < 3 ? 'text-coral-ink' : ($row['average'] < 4 ? 'text-amber-ink' : 'text-mint-ink') }}">
                                    {{ number_format($row['average'], 2, ',', ' ') }}
                                </td>
                                <td class="px-3 py-2.5 {{ $row['low'] > 0 ? 'font-bold text-coral-ink' : 'text-muted' }}">{{ $row['low'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-muted">Оценок пока нет.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <form method="GET" class="card mt-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
        <div>
            <label class="label" for="fb-module">Модуль</label>
            <select id="fb-module" name="module" class="input">
                <option value="">Все модули</option>
                @foreach($modules as $module)
                    <option value="{{ $module->id }}" @selected($filters['module'] === $module->id)>{{ $module->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label" for="fb-trainer">Тренажёр</label>
            <select id="fb-trainer" name="trainer" class="input">
                <option value="">Все тренажёры</option>
                @foreach($trainers as $trainer)
                    <option value="{{ $trainer->id }}" @selected($filters['trainer'] === $trainer->id)>{{ $trainer->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label" for="fb-rating">Оценка</label>
            <select id="fb-rating" name="rating" class="input">
                <option value="">Любая</option>
                @foreach([5, 4, 3, 2, 1] as $rating)
                    <option value="{{ $rating }}" @selected($filters['rating'] === $rating)>{{ $rating }} ★</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 pb-2 text-xs font-semibold text-ink-soft">
            <input type="checkbox" name="comments" value="1" @checked($filters['only_comments']) class="h-4 w-4 rounded border-line">
            Только с комментарием
        </label>
        <div class="flex gap-2">
            <button class="btn-primary px-5 py-3 text-xs">Показать</button>
            <a href="{{ route('admin.feedback.index') }}" class="btn-secondary px-4 py-3 text-xs">Сброс</a>
        </div>
    </form>

    <section class="card mt-5 overflow-hidden">
        <div class="flex items-center justify-between border-b border-line/70 px-5 py-4">
            <div>
                <p class="section-eyebrow">Отзывы</p>
                <h2 class="mt-1 text-lg font-extrabold">Последние комментарии</h2>
            </div>
            <span class="text-[10px] font-semibold text-muted">{{ $feedback->total() }} записей</span>
        </div>

        <div class="divide-y divide-line/60">
            @forelse($feedback as $item)
                <article class="px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-bold">{{ $item->trainer?->title }} <span class="font-semibold text-muted">· {{ $item->trainer?->module?->title }}</span></p>
                            <p class="mt-0.5 text-[11px] text-muted">{{ $item->user?->name }}@if($item->user?->group) · {{ $item->user->group->name }}@endif · {{ $item->created_at?->diffForHumans() }}</p>
                        </div>
                        <span class="chip {{ $item->rating < 3 ? 'border-coral/20 bg-coral-soft text-coral-ink' : 'border-mint/20 bg-mint-soft text-mint-ink' }}">
                            {{ $item->rating }} ★
                        </span>
                    </div>
                    @if($item->comment)
                        <p class="mt-2 rounded-2xl bg-paper/60 px-4 py-3 text-xs leading-relaxed text-ink-soft">{{ $item->comment }}</p>
                    @else
                        <p class="mt-2 text-[11px] italic text-muted">Без комментария</p>
                    @endif
                </article>
            @empty
                <p class="px-5 py-12 text-center text-sm text-muted">По выбранным фильтрам отзывов нет.</p>
            @endforelse
        </div>

        @if($feedback->hasPages())
            <div class="border-t border-line/70 px-5 py-4">{{ $feedback->links() }}</div>
        @endif
    </section>
@endsection

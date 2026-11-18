@extends('layouts.app')

@section('title', 'Прогресс')

@php
    $percent = (int) round($summary['percent']);
    $stageMeta = [
        'initial' => ['label' => 'Старт', 'hint' => 'Первый замер', 'target' => null],
        'fifty' => ['label' => '50%', 'hint' => 'Первый рубеж', 'target' => 50],
        'ninety' => ['label' => '90%', 'hint' => 'Перед сертификатом', 'target' => 90],
    ];
@endphp

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="section-eyebrow">Ваш маршрут</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ tone('progress.index.title') }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
                {{ tone('progress.index.lead') }}
            </p>
        </div>
        <a href="{{ route('modules.index') }}" class="btn-secondary shrink-0 px-4 py-2.5 text-xs">К модулям</a>
    </div>

    <section class="card mt-6 overflow-hidden p-5 sm:p-7">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
            <div class="flex justify-center sm:justify-start">
                <x-progress-ring :value="$summary['percent']" :size="124" :stroke="10" color="#2D5BFF" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <p class="section-eyebrow">Общий прогресс</p>
                        <p class="mt-1 text-2xl font-extrabold tracking-tight">{{ $summary['done'] }} из {{ $summary['total'] }} единиц</p>
                    </div>
                    <span class="chip {{ $summary['certificate_ready'] ? 'border-mint/20 bg-mint-soft text-mint-ink' : '' }}">
                        {{ $summary['certificate_ready'] ? 'порог сертификата достигнут' : 'до сертификата: '.$summary['remaining'].' ед.' }}
                    </span>
                </div>
                <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-paper-deep">
                    <div class="h-full rounded-full bg-brand transition-all" style="width: {{ $summary['percent'] }}%;"></div>
                </div>
                <div class="mt-2 flex justify-between text-[10px] font-semibold text-muted">
                    <span>0%</span>
                    <span>50%</span>
                    <span>90%</span>
                    <span>95% — сертификат</span>
                    <span>100%</span>
                </div>
                <p class="mt-3 text-xs leading-relaxed text-muted">
                    {{ tone('progress.index.scale_hint') }}
                </p>
            </div>
        </div>
    </section>

    <section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($summary['categories'] as $category)
            <div class="card p-4">
                <div class="flex items-start justify-between gap-2">
                    <span class="grid h-10 w-10 place-items-center rounded-2xl" style="background: color-mix(in srgb, {{ $category['color'] }} 12%, transparent); color: {{ $category['color'] }};">
                        <x-icon :name="$category['icon']" class="h-4 w-4" />
                    </span>
                    <span class="text-lg font-extrabold" style="color: {{ $category['color'] }};">{{ (int) round($category['percent']) }}%</span>
                </div>
                <p class="mt-3 text-sm font-extrabold">{{ $category['label'] }}</p>
                <p class="mt-0.5 text-[11px] text-muted">{{ $category['hint'] }}</p>
                <p class="mt-2 text-xs font-bold text-ink-soft">{{ $category['done'] }} / {{ $category['total'] }}</p>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-paper-deep">
                    <div class="h-full rounded-full" style="width: {{ $category['percent'] }}%; background: {{ $category['color'] }};"></div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="mt-7">
        <div class="flex items-end justify-between gap-3">
            <div>
                <p class="section-eyebrow">Градация</p>
                <h2 class="mt-1 text-xl font-extrabold tracking-tight">{{ tone('progress.index.snapshots_title') }}</h2>
            </div>
            <span class="text-[10px] font-semibold text-muted">для динамики и статистики</span>
        </div>

        <div class="mt-3 grid gap-3 sm:grid-cols-3">
            @foreach($stageMeta as $stage => $meta)
                @php $snapshot = $snapshots->get($stage); @endphp
                <div class="card relative overflow-hidden p-4">
                    <div class="absolute -right-4 -top-4 h-16 w-16 rounded-full {{ $snapshot ? 'bg-mint-soft' : 'bg-paper-deep' }}"></div>
                    <div class="relative">
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted">{{ $meta['hint'] }}</p>
                        <p class="mt-1 text-xl font-extrabold">{{ $meta['label'] }}</p>
                        @if($snapshot)
                            <p class="mt-2 text-2xl font-extrabold text-mint-ink">{{ (int) round($snapshot->percent) }}%</p>
                            <p class="mt-1 text-[11px] text-muted">{{ $snapshot->captured_at?->translatedFormat('j F Y, H:i') }}</p>
                        @else
                            <p class="mt-2 text-sm font-bold text-muted">Ещё не достигнут</p>
                            <p class="mt-1 text-[11px] text-muted">
                                @if($meta['target'])
                                    Зафиксируется, когда прогресс дойдёт до {{ $meta['target'] }}%.
                                @else
                                    Срез создаётся при первом открытии прогресса.
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-7 grid gap-5 lg:grid-cols-[1.25fr_0.75fr]">
        <div class="card p-5 sm:p-6">
            @if($certificate)
                <p class="section-eyebrow">Сертификат</p>
                <h2 class="mt-1 text-xl font-extrabold">Сертификат уже выпущен</h2>
                <p class="mt-2 text-sm leading-relaxed text-muted">Номер {{ $certificate->number }} · {{ $certificate->issued_at?->translatedFormat('j F Y') }}. Его можно открыть и распечатать в любое время.</p>
                <a href="{{ route('certificate.show') }}" class="btn-primary mt-4">Открыть сертификат</a>
            @elseif($summary['certificate_ready'])
                @if($feedback)
                    <p class="section-eyebrow">Обратная связь</p>
                    <h2 class="mt-1 text-xl font-extrabold">Спасибо, форма заполнена</h2>
                    <p class="mt-2 text-sm leading-relaxed text-muted">Осталось открыть сертификат. Если он ещё не появился, обновите страницу или обратитесь к администратору.</p>
                    <a href="{{ route('certificate.show') }}" class="btn-primary mt-4">Открыть сертификат</a>
                @else
                    <p class="section-eyebrow">Финальный шаг</p>
                    <h2 class="mt-1 text-xl font-extrabold">Сертификат доступен</h2>
                    <p class="mt-2 text-sm leading-relaxed text-muted">Вы прошли 95% контента. Заполните короткую форму обратной связи — после этого сертификат будет выпущен автоматически.</p>
                    <a href="#feedback" class="btn-primary mt-4">Заполнить форму</a>
                    <div class="mt-4 rounded-2xl border border-mint/20 bg-mint-soft/60 p-4 text-xs leading-relaxed text-mint-ink">
                        Сертификат — педагогический документ о прохождении тренажёра, а не психологическая диагностика.
                    </div>
                @endif
            @else
                <p class="section-eyebrow">Сертификат</p>
                <h2 class="mt-1 text-xl font-extrabold">Пока закрыт</h2>
                <p class="mt-2 text-sm leading-relaxed text-muted">До порога 95% осталось {{ $summary['remaining'] }} единиц контента. Сертификат выдаётся после достижения порога и заполнения формы обратной связи.</p>
                <div class="mt-4 rounded-2xl border border-line/70 bg-paper/60 p-4">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span>Текущий прогресс</span>
                        <span>{{ $percent }}%</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-paper-deep">
                        <div class="h-full rounded-full bg-brand" style="width: {{ $summary['percent'] }}%;"></div>
                    </div>
                </div>
            @endif
        </div>

        <div class="card p-5 sm:p-6">
            <p class="section-eyebrow">Что дальше</p>
            <h2 class="mt-1 text-lg font-extrabold">Закрыть пробелы</h2>
            <div class="mt-4 space-y-2">
                @foreach($summary['categories'] as $category)
                    @if($category['done'] < $category['total'])
                        <div class="flex items-center justify-between rounded-2xl bg-paper/60 px-3.5 py-2.5">
                            <span class="text-xs font-semibold text-ink-soft">{{ $category['label'] }}</span>
                            <span class="text-[11px] font-extrabold text-muted">+{{ $category['total'] - $category['done'] }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
            <a href="{{ route('dashboard') }}" class="btn-secondary mt-4 w-full py-2.5 text-xs">На главную</a>
        </div>
    </section>

    @if($summary['certificate_ready'] && ! $feedback)
        <section id="feedback" class="card mt-5 p-5 sm:p-6">
            <p class="section-eyebrow">Обратная связь</p>
            <h2 class="mt-1 text-xl font-extrabold">{{ tone('progress.index.feedback_title') }}</h2>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">{{ tone('progress.index.feedback_lead') }}</p>

            <form method="POST" action="{{ route('progress.feedback') }}" class="mt-5 space-y-5" x-data="{ rating: 5 }">
                @csrf

                <div>
                    <label class="label">Общая оценка</label>
                    <input type="hidden" name="rating" value="5" :value="rating">
                    <div class="flex gap-2">
                        <template x-for="value in [1, 2, 3, 4, 5]" :key="value">
                            <button type="button" @click="rating = value"
                                    class="grid h-12 w-12 place-items-center rounded-2xl border text-lg font-extrabold transition"
                                    :class="rating >= value ? 'border-amber/30 bg-amber-soft text-amber-ink' : 'border-line bg-surface text-muted'"
                                    x-text="value"></button>
                        </template>
                    </div>
                    <p class="mt-2 text-[10px] text-muted">1 — совсем не полезно, 5 — очень полезно.</p>
                </div>

                <div>
                    <label class="label" for="useful">Что оказалось самым полезным?</label>
                    <select id="useful" name="useful" class="input">
                        <option value="">Выберите</option>
                        <option value="trainers">Тренажёры</option>
                        <option value="theory">Теория</option>
                        <option value="shorts">Шортсы</option>
                        <option value="diagnostics">Диагностика</option>
                    </select>
                </div>

                <div>
                    <label class="label" for="liked">Что понравилось или помогло?</label>
                    <textarea id="liked" name="liked" rows="3" class="textarea" placeholder="Например: упражнение на паузу перед реакцией" required>{{ old('liked') }}</textarea>
                </div>

                <div>
                    <label class="label" for="improve">Что стоит улучшить?</label>
                    <textarea id="improve" name="improve" rows="3" class="textarea" placeholder="Необязательно">{{ old('improve') }}</textarea>
                </div>

                <button type="submit" class="btn-primary w-full py-3.5">Отправить и получить сертификат</button>
            </form>
        </section>
    @endif
@endsection

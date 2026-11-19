@extends('layouts.app')

@section('title', 'Дневник эмоций')

@php
    $defaultSlot = now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'day' : 'evening');
    $lineConfig = [
        'type' => 'line',
        'data' => [
            'labels' => $chart['labels'],
            'datasets' => [[
                'label' => 'Средняя интенсивность',
                'data' => $chart['intensity'],
                'borderColor' => '#2D5BFF',
                'backgroundColor' => 'rgba(45, 91, 255, 0.10)',
                'fill' => true,
                'tension' => 0.35,
                'spanGaps' => true,
                'pointRadius' => 3,
                'pointBackgroundColor' => '#2D5BFF',
            ]],
        ],
        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['grid' => ['display' => false], 'ticks' => ['color' => '#8B93A3', 'font' => ['size' => 9]]],
                'y' => ['beginAtZero' => true, 'max' => 5, 'ticks' => ['stepSize' => 1, 'color' => '#8B93A3', 'font' => ['size' => 9]], 'grid' => ['color' => 'rgba(139,147,163,0.22)']],
            ],
        ],
    ];

    $donutConfig = [
        'type' => 'doughnut',
        'data' => [
            'labels' => $emotionCounts->pluck('label')->all(),
            'datasets' => [[
                'data' => $emotionCounts->pluck('count')->all(),
                'backgroundColor' => $emotionCounts->pluck('color')->all(),
                'borderWidth' => 0,
            ]],
        ],
        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '68%',
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['color' => '#8B93A3', 'boxWidth' => 8, 'boxHeight' => 8, 'font' => ['size' => 10]]]],
        ],
    ];

    $zones = ['голова', 'шея и плечи', 'грудь', 'живот', 'руки', 'ноги', 'всё тело'];
@endphp

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="section-eyebrow">Дневник</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ tone('diary.index.title') }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">{{ tone('diary.index.lead') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="chip"><x-icon name="flame" class="h-3.5 w-3.5 text-coral" /> {{ $entriesCount }} {{ \App\Support\Text::plural($entriesCount, 'запись', 'записи', 'записей') }}</span>
            <span class="chip"><x-icon name="target" class="h-3.5 w-3.5 text-mint-ink" /> {{ $practiceUnits }}/21 единиц · {{ $practiceDays }}/7 дней</span>
            <span class="chip"><x-icon name="shield" class="h-3.5 w-3.5 text-mint-ink" /> шифруется · видно только вам</span>
            <a href="{{ route('profile.index') }}" class="btn-secondary px-4 py-2.5 text-xs">{{ tone('common.profile') }}</a>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-3xl border border-line/60">
        <x-photo path="media/photos/writing.jpg" credit="overlay" class="h-36 sm:h-44" img-class="h-full w-full object-cover" />
    </div>

    <div class="mt-7 grid gap-5 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="min-w-0 space-y-5">
            <section class="card p-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="section-eyebrow">Новая запись</p>
                        <h2 class="mt-1 text-lg font-extrabold">{{ tone('diary.index.form_title') }}</h2>
                        <p class="mt-1 text-[10px] leading-relaxed text-muted">Утро, день и вечер дают по 1 единице прогресса. Максимум 3 в день и 21 за 7 дней. «Сейчас» — быстрая запись без отдельной единицы.</p>
                    </div>
                    <span class="text-[11px] font-semibold text-muted">{{ now()->translatedFormat('j F') }}</span>
                </div>

                <form method="POST" action="{{ route('diary.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="label" for="slot">Время дня</label>
                            <select id="slot" name="slot" class="input">
                                @foreach($slots as $value => $label)
                                    <option value="{{ $value }}" @selected(old('slot', $defaultSlot) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label" for="entry_date">Дата</label>
                            <input id="entry_date" type="date" name="entry_date" value="{{ old('entry_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="input">
                        </div>
                    </div>

                    <div>
                        <label class="label" for="emotion_id">Эмоция</label>
                        <select id="emotion_id" name="emotion_id" class="input" required>
                            <option value="">Выберите</option>
                            @foreach($emotions->groupBy('group') as $group => $items)
                                <optgroup label="{{ $group === 'primary' ? 'Базовые' : 'Более точные' }}">
                                    @foreach($items as $emotion)
                                        <option value="{{ $emotion->id }}" @selected(old('emotion_id') == $emotion->id)>{{ $emotion->emoji }} {{ $emotion->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs font-bold">
                            <label for="intensity">Интенсивность</label>
                            <span class="text-brand" id="intensity-value">3</span>
                        </div>
                        <input type="range" min="1" max="5" name="intensity" id="intensity" value="{{ old('intensity', 3) }}" class="mt-2 w-full accent-[#2D5BFF]" oninput="document.getElementById('intensity-value').textContent = this.value">
                        <div class="mt-1 flex justify-between text-[10px] font-semibold text-muted"><span>едва заметно</span><span>очень сильно</span></div>
                    </div>

                    <div>
                        <label class="label" for="cause">Что вызвало</label>
                        <input id="cause" name="cause" value="{{ old('cause') }}" class="input" placeholder="Например: встреча с мастером, защита проекта">
                    </div>

                    <div>
                        <p class="label">Где в теле?</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($zones as $zone)
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="body_zones[]" value="{{ $zone }}" class="peer sr-only" @checked(in_array($zone, old('body_zones', []), true))>
                                    <span class="inline-block rounded-full border border-line bg-surface px-3 py-1.5 text-[11px] font-bold text-ink-soft transition peer-checked:border-brand/30 peer-checked:bg-brand-soft peer-checked:text-brand-deep">{{ $zone }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="label" for="note">Заметка</label>
                        <textarea id="note" name="note" rows="2" class="textarea" placeholder="Что хочется запомнить?">{{ old('note') }}</textarea>
                    </div>

                    <button type="submit" class="btn-primary w-full py-3">Сохранить запись</button>
                </form>
            </section>

            <section class="card p-5">
                <p class="section-eyebrow">Сегодня</p>
                <div class="mt-3 space-y-2">
                    @foreach($slots as $slot => $label)
                        @php $entry = $todayEntries->get($slot); @endphp
                        <div class="flex items-center gap-3 rounded-2xl border border-line/70 bg-paper/50 px-3 py-2.5">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-surface text-base">{{ $entry?->emotion?->emoji ?: '·' }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-muted">{{ $label }}</p>
                                <p class="truncate text-xs font-semibold text-ink-soft">{{ $entry?->emotion?->name ?? 'Пока нет записи' }}</p>
                            </div>
                            @if($entry)
                                <span class="text-[10px] font-bold text-muted">{{ $entry->intensity }}/5</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="min-w-0 space-y-5">
            <section class="card p-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="section-eyebrow">Динамика</p>
                        <h2 class="mt-1 text-lg font-extrabold">Интенсивность за 14 дней</h2>
                    </div>
                    <span class="chip">среднее по дням</span>
                </div>
                <div class="mt-4 h-48 min-w-0">
                    <canvas x-data="chartCanvas(@js($lineConfig))" class="max-w-full"></canvas>
                </div>
            </section>

            <section class="card p-5 sm:p-6">
                <p class="section-eyebrow">Частые эмоции</p>
                <h2 class="mt-1 text-lg font-extrabold">Что звучит чаще</h2>
                @if($emotionCounts->isEmpty())
                    <x-empty-state icon="calendar" title="Пока нет данных" text="Сделайте первую запись — график появится." class="mt-4" />
                @else
                    <div class="mt-4 h-56 min-w-0">
                        <canvas x-data="chartCanvas(@js($donutConfig))" class="max-w-full"></canvas>
                    </div>
                    <div class="mt-3 space-y-1.5">
                        @foreach($emotionCounts as $emotion)
                            <div class="flex items-center justify-between text-xs">
                                <span class="flex items-center gap-2 font-semibold text-ink-soft">
                                    <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $emotion['color'] }};"></span>
                                    {{ $emotion['label'] }}
                                </span>
                                <span class="font-extrabold text-muted">{{ $emotion['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="card p-5 sm:p-6">
                <p class="section-eyebrow">История</p>
                <div class="mt-3 space-y-2">
                    @forelse($entries as $entry)
                        <div class="flex items-start gap-3 rounded-2xl border border-line/60 bg-paper/40 px-3 py-2.5">
                            <span class="text-base">{{ $entry->emotion?->emoji }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold">{{ $entry->emotion?->name }} <span class="font-semibold text-muted">· {{ $entry->intensity }}/5</span></p>
                                <p class="text-[10px] font-semibold text-muted">{{ $entry->entry_date->translatedFormat('j F') }} · {{ $slots[$entry->slot] ?? $entry->slot }}</p>
                                @if($entry->note)
                                    <p class="mt-1 text-[11px] leading-relaxed text-ink-soft">{{ $entry->note }}</p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('diary.destroy', $entry) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="grid h-7 w-7 place-items-center rounded-lg text-muted transition hover:bg-rose/10 hover:text-rose" title="Удалить">
                                    <x-icon name="trash" class="h-3.5 w-3.5" />
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-xs leading-relaxed text-muted">Записей пока нет. Начните с одной — этого достаточно.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection

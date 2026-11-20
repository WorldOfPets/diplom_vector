@extends('layouts.admin')

@section('title', 'Аналитика')

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="section-eyebrow">Аналитика</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight">Данные и качество заданий</h1>
            <p class="mt-1 text-sm text-muted">Сложность, дискриминация, среднее время и динамика по группам.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.export.progress') }}" class="btn-secondary px-4 py-2.5 text-xs">Прогресс CSV</a>
            <a href="{{ route('admin.export.test-attempts') }}" class="btn-secondary px-4 py-2.5 text-xs">Результаты CSV</a>
            <a href="{{ route('admin.export.trainer-attempts') }}" class="btn-secondary px-4 py-2.5 text-xs">Тренажёры CSV</a>
            <a href="{{ route('admin.export.groups') }}" class="btn-secondary px-4 py-2.5 text-xs">Группы CSV</a>
            <a href="{{ route('admin.feedback.index') }}" class="btn-primary px-4 py-2.5 text-xs">Качество</a>
        </div>
    </div>

    <div class="mt-5 grid gap-3 sm:grid-cols-3">
        <x-stat label="Пользователи" :value="$usersCount" hint="в системе" icon="user" tone="brand" />
        <x-stat label="Попытки теста" :value="$attemptsCount" hint="завершённые" icon="chart" tone="lilac" />
        <x-stat label="Заданий" :value="$selectedTest?->items->count() ?? 0" hint="в выбранном тесте" icon="target" tone="amber" />
    </div>

    <section class="card mt-5 p-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="section-eyebrow">Градация прогресса</p>
                <h2 class="mt-1 text-lg font-extrabold">Срезы пользователей</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="chip border-mint/20 bg-mint-soft text-emerald-700">сертификатов: {{ $certificatesCount }}</span>
                <span class="chip">обратной связи: {{ $feedbackCount }}</span>
            </div>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            @foreach(['initial' => 'Старт', 'fifty' => '50%', 'ninety' => '90%'] as $stage => $label)
                <div class="rounded-2xl border border-line/70 bg-paper/50 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-extrabold">{{ $stageStats[$stage]['users'] }}</p>
                    <p class="mt-1 text-[11px] text-muted">пользователей · средний срез {{ $stageStats[$stage]['avg_percent'] }}%</p>
                </div>
            @endforeach
        </div>
    </section>

    <form method="GET" class="card mt-5 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        <div class="sm:w-80">
            <label class="label" for="analytics-test">Тест</label>
            <select id="analytics-test" name="test" class="input">
                @foreach($tests as $test)
                    <option value="{{ $test->id }}" @selected($selectedTest?->id === $test->id)>{{ $test->title }} ({{ $test->kind }})</option>
                @endforeach
            </select>
        </div>
        <button class="btn-primary px-5 py-3 text-xs">Показать</button>
    </form>

    @if($branchMeans->isNotEmpty())
        <section class="card mt-5 p-5">
            <p class="section-eyebrow">Средние по ветвям</p>
            <h2 class="mt-1 text-lg font-extrabold">{{ $selectedTest?->title }}</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach($branchMeans as $branch)
                    <div>
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-ink-soft">{{ $branch['label'] }}</span>
                            <span style="color: {{ $branch['color'] }};">{{ $branch['value'] !== null ? $branch['value'].'%' : '—' }}</span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-paper-deep">
                            <div class="h-full rounded-full" style="width: {{ $branch['value'] ?? 0 }}%; background: {{ $branch['color'] }};"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="card mt-5 overflow-hidden">
        <div class="flex items-center justify-between border-b border-line/70 px-5 py-4">
            <div>
                <p class="section-eyebrow">Задания</p>
                <h2 class="mt-1 text-lg font-extrabold">Сложность и дискриминация</h2>
            </div>
            <span class="text-[10px] font-semibold text-muted">p — доля балла · r — связь с итогом</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-xs">
                <thead class="border-b border-line/70 bg-paper/60 text-[10px] font-bold uppercase tracking-[0.12em] text-muted">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Ветвь</th>
                        <th class="px-4 py-3">Тип</th>
                        <th class="px-4 py-3">Формулировка</th>
                        <th class="px-4 py-3">N</th>
                        <th class="px-4 py-3">p</th>
                        <th class="px-4 py-3">r</th>
                        <th class="px-4 py-3">Время</th>
                        <th class="px-4 py-3">Флаг</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($itemStats as $row)
                        <tr class="align-top hover:bg-paper/40">
                            <td class="px-4 py-3 font-bold text-muted">{{ $row['item']->sort }}</td>
                            <td class="px-4 py-3"><span class="chip">{{ $row['item']->branch }}</span></td>
                            <td class="px-4 py-3 text-muted">{{ $row['item']->type }}</td>
                            <td class="max-w-md px-4 py-3 font-semibold text-ink-soft">{{ \Illuminate\Support\Str::limit($row['item']->prompt, 110) }}</td>
                            <td class="px-4 py-3 text-muted">{{ $row['n'] }}</td>
                            <td class="px-4 py-3 font-bold {{ $row['p'] !== null && ($row['p'] < 20 || $row['p'] > 90) ? 'text-coral-ink' : 'text-ink' }}">
                                {{ $row['p'] !== null ? $row['p'].'%' : '—' }}
                            </td>
                            <td class="px-4 py-3 font-bold {{ $row['r'] !== null && $row['r'] < 0.2 ? 'text-amber-ink' : 'text-ink' }}">
                                {{ $row['r'] ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted">{{ $row['avg_time'] ? round($row['avg_time'] / 1000, 1).' с' : '—' }}</td>
                            <td class="px-4 py-3">
                                @if($row['flag'] || ($row['r'] !== null && $row['r'] < 0.2))
                                    <span class="chip border-amber/20 bg-amber-soft text-amber-ink">пересмотреть</span>
                                @else
                                    <span class="chip border-mint/20 bg-mint-soft text-emerald-700">ок</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-sm text-muted">Нет данных по выбранному тесту.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <section class="card overflow-hidden">
            <div class="border-b border-line/70 px-5 py-4">
                <p class="section-eyebrow">Модули</p>
                <h2 class="mt-1 text-lg font-extrabold">Завершённые попытки</h2>
            </div>
            <div class="divide-y divide-line/60">
                @foreach($moduleStats as $row)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold">{{ $row['module']->title }}</p>
                            <p class="text-[10px] text-muted">{{ $row['trainers'] }} тренажёров · {{ $row['students'] }} студентов</p>
                        </div>
                        <span class="text-sm font-extrabold">{{ $row['completed'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-line/70 px-5 py-4">
                <p class="section-eyebrow">Группы</p>
                <h2 class="mt-1 text-lg font-extrabold">Средние результаты</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-line/70 bg-paper/60 text-[10px] font-bold uppercase tracking-[0.12em] text-muted">
                        <tr>
                            <th class="px-5 py-2.5">Группа</th>
                            <th class="px-3 py-2.5">Студ.</th>
                            <th class="px-3 py-2.5">ЭИ</th>
                            <th class="px-3 py-2.5">Тренажёры</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/60">
                        @foreach($groupStats as $row)
                            <tr>
                                <td class="px-5 py-2.5 font-bold">{{ $row['group']->name }}</td>
                                <td class="px-3 py-2.5 text-muted">{{ $row['group']->users_count }}</td>
                                <td class="px-3 py-2.5">{{ $row['ei'] !== null ? $row['ei'].'%' : '—' }}</td>
                                <td class="px-3 py-2.5">{{ $row['trainers'] !== null ? $row['trainers'].'%' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 px-5 text-[10px] leading-relaxed text-muted">
                Средние по группам меньше 3 человек скрыты, чтобы не показывать индивидуальные результаты.
            </p>
        </section>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Мимика')

@php
    $targets = $trainer->config['targets'] ?? [
        ['id' => 'happy', 'label' => 'Радость', 'emoji' => '😊', 'threshold' => 80],
        ['id' => 'sad', 'label' => 'Грусть', 'emoji' => '😔', 'threshold' => 80],
        ['id' => 'angry', 'label' => 'Злость', 'emoji' => '😠', 'threshold' => 80],
        ['id' => 'surprised', 'label' => 'Удивление', 'emoji' => '😮', 'threshold' => 80],
        ['id' => 'fearful', 'label' => 'Страх', 'emoji' => '😨', 'threshold' => 80],
        ['id' => 'disgusted', 'label' => 'Отвращение', 'emoji' => '🤢', 'threshold' => 80],
        ['id' => 'neutral', 'label' => 'Нейтрально', 'emoji' => '😐', 'threshold' => 80],
    ];
    $labels = collect($targets)->mapWithKeys(fn ($target) => [$target['id'] => $target['label']])->all();
    $thresholds = collect($targets)->mapWithKeys(fn ($target) => [$target['id'] => ((int) ($target['threshold'] ?? 80)) / 100])->all();
    $expressions = [
        'neutral' => ['Нейтрально', '😐'],
        'happy' => ['Радость', '😊'],
        'sad' => ['Грусть', '😔'],
        'angry' => ['Злость', '😠'],
        'fearful' => ['Страх', '😨'],
        'disgusted' => ['Отвращение', '🤢'],
        'surprised' => ['Удивление', '😮'],
    ];
@endphp

@push('head')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        window.VECTOR_CAMERA = {
            targets: @json(array_column($targets, 'id')),
            labels: @json($labels),
            thresholds: @json($thresholds),
            completed: @json($completedTargets ?? []),
            total: {{ count($targets) }},
            holdMs: 2000,
            progressUrl: '{{ route('camera.progress') }}',
            submitUrl: '{{ route('camera.submit') }}',
            csrf: '{{ csrf_token() }}',
        };
    </script>
    @vite('resources/js/camera.js')
@endpush

@section('content')
    <nav class="flex items-center gap-1.5 text-[11px] font-bold text-muted" aria-label="Хлебные крошки">
        <a href="{{ route('modules.index') }}" class="hover:text-ink">Модули</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <span class="text-ink-soft">Мимика</span>
    </nav>

    <div class="mt-4 grid gap-5 lg:grid-cols-[1.5fr_1fr]">
        <div class="space-y-4">
            <section class="card relative overflow-hidden p-6 sm:p-7">
                <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-lilac-soft"></div>
                <div class="-mx-6 -mt-6 mb-5 h-40 overflow-hidden sm:-mx-7 sm:-mt-7">
                    <x-photo path="media/photos/portrait-neutral.jpg" credit="overlay" class="h-full" img-class="h-full w-full object-cover" loading="eager" />
                </div>
                <div class="relative">
                    <span class="chip border-lilac/20 bg-lilac-soft text-lilac-ink">практикум · камера</span>
                    <h1 class="mt-3 text-3xl font-extrabold tracking-tight">Мимика</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
                        {{ tone('camera.index.lead') }}
                    </p>
                    <p class="mt-2 max-w-2xl text-xs leading-relaxed text-muted">
                        {{ tone('camera.index.note') }}
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="chip"><x-icon name="shield" class="h-3 w-3 text-mint-ink" /> кадры не уходят на сервер</span>
                        <span class="chip">7 мимических паттернов</span>
                        <span class="chip">1 эмоция = 1 единица прогресса</span>
                        <span class="chip">порог модели 80%</span>
                    </div>
                </div>
            </section>

            @if(! $hasConsent)
                <section class="card p-6">
                    <p class="section-eyebrow">Согласие</p>
                    <h2 class="mt-1 text-xl font-extrabold">Сначала — разрешение</h2>
                    <p class="mt-2 text-sm leading-relaxed text-ink-soft">
                        Для упражнения нужен доступ к камере. Видео обрабатывается только в браузере: на сервер отправляется лишь итоговая уверенность модели и длительность удержания.
                        Кадры не сохраняются и не передаются.
                    </p>
                    <p class="mt-2 text-xs leading-relaxed text-muted">
                        Модель различает семь мимических паттернов и может ошибаться. Уверенность 80% — это порог модели, а не оценка ваших эмоций и не диагноз.
                    </p>
                    <ul class="mt-3 space-y-1.5 text-xs leading-relaxed text-muted">
                        <li>• Согласие можно отозвать в любой момент — кнопка есть ниже на этой странице.</li>
                        <li>• Если камеры нет — выполните альтернативное задание в модуле «Понимаю других».</li>
                        <li>• Результат не влияет на оценку по другим дисциплинам.</li>
                    </ul>
                    <form method="POST" action="{{ route('camera.consent') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="btn-primary">Разрешить камеру</button>
                    </form>
                </section>
            @else
                <section class="card overflow-hidden">
                    <div class="border-b border-line/70 p-4 sm:p-5">
                        <div class="flex items-end justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted">Прохождение практикума</p>
                                <p class="mt-1 text-sm font-extrabold"><span id="camera-completed">0</span> из <span id="camera-total">{{ count($targets) }}</span> эмоций</p>
                            </div>
                            <p id="camera-percent" class="text-2xl font-extrabold tabular-nums text-brand">0%</p>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-paper-deep" role="progressbar" aria-label="Прохождение практикума" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="camera-completion-track">
                            <div id="camera-completion-bar" class="h-full rounded-full bg-brand transition-all" style="width: 0%"></div>
                        </div>
                        <p class="mt-1.5 text-[10px] leading-relaxed text-muted">Одна эмоция = {{ (int) round(100 / max(count($targets), 1)) }}%. Прогресс сохраняется в профиле и идёт в общую шкалу.</p>
                        <button type="button" id="camera-finish" class="btn-primary mt-3 hidden min-h-11 w-full py-2.5 text-xs">
                            Завершить практикум
                        </button>
                    </div>

                    <div class="relative aspect-[4/3] bg-inverse">
                        <video id="camera-video" autoplay playsinline muted class="h-full w-full scale-x-[-1] object-cover"></video>
                        <div id="camera-placeholder" class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-inverse/80 text-center text-white">
                            <x-icon name="camera" class="h-8 w-8 text-white/60" />
                            <p class="text-sm font-bold">Камера выключена</p>
                            <p class="max-w-xs text-xs text-white/70">Нажмите «Включить камеру» и разрешите доступ в браузере.</p>
                        </div>
                        <div class="absolute left-3 top-3 rounded-full bg-black/40 px-3 py-1.5 text-[10px] font-bold text-white/80 backdrop-blur" id="camera-status">Готово к запуску</div>
                        <div class="absolute inset-x-0 bottom-0 h-1.5 bg-white/10">
                            <div id="camera-progress" class="h-full bg-mint transition-all" style="width: 0%"></div>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted">Цель</p>
                                <p class="text-sm font-extrabold">Порог <span id="camera-threshold">80%</span> · попыток: <span id="camera-attempts">0</span></p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" id="camera-start" class="btn-primary min-h-11 px-4 py-2.5 text-xs">Включить камеру</button>
                                <button type="button" id="camera-stop" class="btn-secondary hidden min-h-11 px-4 py-2.5 text-xs">Остановить</button>
                                <button type="button" id="camera-retry" class="btn-secondary hidden min-h-11 px-4 py-2.5 text-xs">Ещё раз</button>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($targets as $target)
                                <button type="button" data-camera-target="{{ $target['id'] }}" data-completed="{{ in_array($target['id'], $completedTargets ?? [], true) ? 'true' : 'false' }}"
                                        class="inline-flex min-h-11 items-center gap-1.5 rounded-full border border-line bg-surface px-3 py-2 text-[11px] font-bold text-ink-soft transition data-[active=true]:border-brand/30 data-[active=true]:bg-brand-soft data-[active=true]:text-brand-deep data-[completed=true]:border-mint/30 data-[completed=true]:bg-mint-soft data-[completed=true]:text-mint-ink">
                                    {{ $target['emoji'] ?? '' }} {{ $target['label'] }}
                                </button>
                            @endforeach
                        </div>

                        <p id="camera-hint" class="mt-3 text-xs leading-relaxed text-muted">Выберите эмоцию и начните. Держите лицо в центре кадра.</p>

                        <div id="camera-result" class="mt-3 hidden rounded-2xl border border-mint/20 bg-mint-soft/60 p-4">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-extrabold text-mint-ink">Получилось</p>
                                <p class="text-2xl font-extrabold text-mint-ink" id="camera-result-value">—</p>
                            </div>
                            <p class="mt-1 text-xs text-mint-ink/70" id="camera-result-text"></p>
                        </div>
                    </div>
                </section>

                <section class="card p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="section-eyebrow">Уверенность модели</p>
                            <p class="mt-1 text-xs text-muted">Это вероятность распознавания, а не «сила эмоции».</p>
                        </div>
                    </div>
                    <div id="camera-bars" class="mt-4 space-y-2">
                        @foreach($expressions as $key => [$label, $emoji])
                            <div data-bar="{{ $key }}" class="flex items-center gap-3">
                                <span class="w-24 shrink-0 text-[11px] font-bold text-ink-soft">{{ $emoji }} {{ $label }}</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-paper-deep">
                                    <div data-fill class="h-full rounded-full bg-brand/40 transition-all" style="width: 0%"></div>
                                </div>
                                <span data-score class="w-10 text-right text-[10px] font-extrabold tabular-nums text-muted">0%</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-4">
            <div class="card p-5">
                <p class="section-eyebrow">Как это работает</p>
                <ol class="mt-3 space-y-2 text-xs leading-relaxed text-ink-soft">
                    <li><span class="font-extrabold">1.</span> Выберите эмоцию.</li>
                    <li><span class="font-extrabold">2.</span> Включите камеру и встаньте так, чтобы лицо было хорошо видно.</li>
                    <li><span class="font-extrabold">3.</span> Удерживайте выражение, пока полоса не заполнится.</li>
                </ol>
                <p class="mt-3 text-[11px] leading-relaxed text-muted">Модель может ошибаться на слабом свете, в очках или при необычном ракурсе. Это ограничение технологии, а не ваша ошибка.</p>
            </div>

            @if($completed)
                <div class="card p-5">
                    <p class="section-eyebrow">Прошлый результат</p>
                    <p class="mt-1 text-2xl font-extrabold text-mint-ink">{{ (int) $completed->percent }}%</p>
                    <p class="mt-1 text-xs text-muted">{{ $completed->completed_at?->translatedFormat('j F, H:i') }}</p>
                    <a href="{{ route('trainers.result', $completed) }}" class="btn-secondary mt-3 w-full py-2.5 text-xs">Открыть разбор</a>
                </div>
            @endif

            <div class="rounded-3xl border border-line/60 bg-surface/50 p-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-muted">Без камеры</p>
                <p class="mt-2 text-xs leading-relaxed text-ink-soft">Если камера недоступна, выполните фото-кейсы в модуле «Понимаю других» — навык тот же: замечать и называть эмоцию.</p>
                <a href="{{ route('modules.index') }}" class="btn-ghost mt-2 px-0 text-xs text-brand hover:bg-transparent">К модулям →</a>
            </div>

            @if($hasConsent)
                <form method="POST" action="{{ route('camera.consent.revoke') }}" class="rounded-3xl border border-line/60 bg-surface/50 p-5">
                    @csrf
                    @method('DELETE')
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-muted">Согласие</p>
                    <p class="mt-2 text-xs leading-relaxed text-ink-soft">Можно отозвать доступ к камере в любой момент. Это не удалит уже сохранённый прогресс практикума.</p>
                    <button type="submit" class="btn-ghost mt-2 px-0 text-xs hover:bg-transparent" style="color: var(--color-rose);">Отозвать согласие</button>
                </form>
            @endif
        </aside>
    </div>
@endsection

@php
    $areas = $config['areas'] ?? [];
    $max = (int) ($config['max'] ?? 10);
    $defaults = collect($areas)->mapWithKeys(fn ($area): array => [$area['key'] => 5])->all();
@endphp

<div class="card p-5 sm:p-6"
     x-data
     x-init="$dispatch('response-patch', {
        values: { ...@js($defaults), ...(response.values || {}) },
        actions: (response.actions && response.actions.length ? response.actions : ['', '', ''])
     })">
    <div class="flex items-start gap-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-mint-soft text-mint-ink"><x-icon name="chart" class="h-4 w-4" /></span>
        <div>
            <p class="text-sm font-bold">{{ $config['title'] ?? 'Где вы сейчас?' }}</p>
            <p class="mt-1 text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Оцените каждую сферу от 0 до 10. Не «как надо», а как есть.' }}</p>
        </div>
    </div>

    <div class="mt-5 space-y-4">
        @foreach($areas as $area)
            <div>
                <div class="flex items-center justify-between text-xs font-bold">
                    <span class="flex items-center gap-2">
                        <span class="h-1.5 w-1.5 rounded-full" style="background: {{ \App\Support\Color::safe($area['color'] ?? null) }};"></span>
                        {{ $area['label'] }}
                    </span>
                    <span style="color: {{ \App\Support\Color::safe($area['color'] ?? null) }};" x-text="response.values?.[@js($area['key'])] ?? 5"></span>
                </div>
                <input type="range" min="0" max="{{ $max }}" x-model.number="response.values[@js($area['key'])]" class="mt-2 w-full accent-[#2D5BFF]" aria-label="{{ $area['label'] }}">
            </div>
        @endforeach
    </div>

    @if(collect($areas)->contains(fn (array $area): bool => ($area['key'] ?? '') === 'anxiety'))
        <p class="mt-3 text-[11px] leading-relaxed text-muted">Для тревоги высокий балл — это сигнал, а не цель. Остальные шкалы — ресурсы, на которые можно опираться.</p>
    @endif

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="focus">Одна зона роста</label>
            <select id="focus" class="input" x-model="response.focus">
                <option value="">Выберите…</option>
                @foreach($areas as $area)
                    <option value="{{ $area['key'] }}">{{ $area['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Что сдвинете за 2 недели</label>
            <div class="space-y-2">
                <input class="input py-2.5 text-xs" x-model="response.actions[0]" placeholder="Шаг 1">
                <input class="input py-2.5 text-xs" x-model="response.actions[1]" placeholder="Шаг 2">
                <input class="input py-2.5 text-xs" x-model="response.actions[2]" placeholder="Шаг 3">
            </div>
        </div>
    </div>
</div>

@php
    $fields = $config['fields'] ?? [];
    $beforeAfter = $config['before_after'] ?? null;
    $skip = $beforeAfter ? [$beforeAfter['before_key'] ?? null, $beforeAfter['after_key'] ?? null] : [];
@endphp

<div class="card p-5 sm:p-6" x-data x-init="$dispatch('response-patch', { fields: { ...(response.fields || {}) } })">
    <div class="flex items-start gap-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-brand-soft text-brand-deep"><x-icon name="edit" class="h-4 w-4" /></span>
        <div>
            <p class="text-sm font-bold">{{ $config['title'] ?? 'Соберите высказывание' }}</p>
            <p class="mt-1 text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Заполните поля. Важна конкретика, а не красивые слова.' }}</p>
        </div>
    </div>

    @if(! empty($config['template']))
        <blockquote class="mt-4 rounded-2xl border-l-4 border-brand/30 bg-brand-soft/40 px-4 py-3 text-sm leading-relaxed text-ink-soft">
            {{ $config['template'] }}
        </blockquote>
    @endif

    @if($beforeAfter)
        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            @foreach([['key' => $beforeAfter['before_key'] ?? 'before', 'label' => $beforeAfter['before_label'] ?? 'До'], ['key' => $beforeAfter['after_key'] ?? 'after', 'label' => $beforeAfter['after_label'] ?? 'После']] as $slider)
                <div class="rounded-2xl bg-paper/70 p-4">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span>{{ $slider['label'] }}</span>
                        <span class="text-brand" x-text="response.fields?.['{{ $slider['key'] }}'] ?? 0"></span>
                    </div>
                    <input type="range" min="0" max="{{ $beforeAfter['max'] ?? 10 }}" x-model.number="response.fields['{{ $slider['key'] }}']" class="mt-2 w-full accent-[#2D5BFF]" aria-label="{{ $slider['label'] }}">
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-5 space-y-4">
        @foreach($fields as $field)
            @continue(in_array($field['key'], $skip, true))
            <div>
                <label class="label" for="field-{{ $field['key'] }}">{{ $field['label'] }}</label>
                @if(($field['type'] ?? 'textarea') === 'text')
                    <input id="field-{{ $field['key'] }}" class="input" x-model="response.fields['{{ $field['key'] }}']" placeholder="{{ $field['placeholder'] ?? '' }}">
                @else
                    <textarea id="field-{{ $field['key'] }}" rows="3" class="textarea" x-model="response.fields['{{ $field['key'] }}']" placeholder="{{ $field['placeholder'] ?? '' }}"></textarea>
                @endif
                @if(! empty($field['hint']))
                    <p class="mt-1.5 text-[11px] leading-relaxed text-muted">{{ $field['hint'] }}</p>
                @endif
            </div>
        @endforeach
    </div>

    <p class="mt-4 text-[11px] leading-relaxed text-muted">Сервер проверит полноту и стоп-слова. Это не оценка личности — это подсказка, что усилить.</p>
</div>

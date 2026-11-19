@php
    $fields = $config['fields'] ?? [];
    $beforeAfter = $config['before_after'] ?? null;
    $skip = $beforeAfter ? [$beforeAfter['before_key'] ?? null, $beforeAfter['after_key'] ?? null] : [];
    $values = is_array($saved['fields'] ?? null) ? $saved['fields'] : [];
    $coachFields = collect($fields)
        ->reject(fn (array $field): bool => in_array($field['key'], $skip, true))
        ->map(fn (array $field): array => [
            'key' => $field['key'],
            'min' => (int) ($field['min'] ?? 10),
            'required' => (bool) ($field['required'] ?? true),
        ])
        ->values()
        ->all();
    $blacklist = $config['blacklist'] ?? [];
    $chips = $config['chips'] ?? [];
@endphp

<div class="card p-5 sm:p-6"
     x-data="constructorCoach({ values: @js($values), fields: @js($coachFields), blacklist: @js($blacklist), chips: @js($chips) })">
    <div class="flex items-start gap-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-brand-soft text-brand-deep"><x-icon name="edit" class="h-4 w-4" /></span>
        <div class="flex-1">
            <p class="text-sm font-bold">{{ $config['title'] ?? 'Соберите высказывание' }}</p>
            <p class="mt-1 text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Заполните поля. Важна конкретика, а не красивые слова.' }}</p>
        </div>
        <span class="chip shrink-0" x-text="`${progress}%`"></span>
    </div>

    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-line">
        <div class="h-full rounded-full bg-brand transition-all" :style="`width: ${progress}%`"></div>
    </div>

    @if(! empty($config['template']))
        <blockquote class="mt-4 rounded-2xl border-l-4 border-brand/30 bg-brand-soft/40 px-4 py-3 text-sm leading-relaxed text-ink-soft">
            {{ $config['template'] }}
        </blockquote>
    @endif

    @if($chips !== [])
        <div class="mt-4 rounded-2xl border border-dashed border-brand/30 bg-brand-soft/20 p-3.5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-[11px] font-extrabold text-brand-deep">Готовые фразы</p>
                <p class="text-[10px] font-semibold text-muted">Перетащите в поле или нажмите</p>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($chips as $chip)
                    <button type="button"
                            draggable="true"
                            @dragstart="dragChip(@js($chip['id']))"
                            @dragend="draggedChip = null"
                            @click="update(@js($chip['slot']), @js($chip['label']))"
                            class="cursor-grab rounded-full border border-line bg-surface px-3 py-2 text-left text-[11px] font-semibold leading-snug text-ink-soft transition hover:border-brand/40 active:cursor-grabbing">
                        {{ $chip['label'] }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    @if($beforeAfter)
        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            @foreach([
                ['key' => $beforeAfter['before_key'] ?? 'before', 'label' => $beforeAfter['before_label'] ?? 'До'],
                ['key' => $beforeAfter['after_key'] ?? 'after', 'label' => $beforeAfter['after_label'] ?? 'После'],
            ] as $slider)
                <div class="rounded-2xl bg-paper/70 p-4">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span>{{ $slider['label'] }}</span>
                        <span class="text-brand" x-text="values[@js($slider['key'])] ?? 0"></span>
                    </div>
                    <input type="range"
                           min="0"
                           max="{{ $beforeAfter['max'] ?? 10 }}"
                           :value="values[@js($slider['key'])] ?? 0"
                           @input="update(@js($slider['key']), Number($event.target.value))"
                           class="mt-2 w-full accent-[#2D5BFF]"
                           aria-label="{{ $slider['label'] }}">
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-5 space-y-4">
        @foreach($fields as $field)
            @continue(in_array($field['key'], $skip, true))
            @php
                $min = (int) ($field['min'] ?? 10);
            @endphp
            <div @dragover.prevent @drop="dropChip(@js($field['key']))"
                 :class="draggedChip ? 'rounded-2xl bg-brand-soft/20 p-2 ring-2 ring-brand/20' : ''">
                <div class="flex items-end justify-between gap-2">
                    <label class="label" for="field-{{ $field['key'] }}">{{ $field['label'] }}</label>
                    <span class="text-[10px] font-bold tabular-nums"
                          :class="length(@js($field['key'])) >= {{ $min }} ? 'text-mint-ink' : 'text-muted'"
                          x-text="length(@js($field['key'])) + ' / {{ $min }}'"></span>
                </div>

                <p x-show="draggedChip" x-cloak class="mb-1 text-[10px] font-bold text-brand">Отпустите здесь</p>

                @if(($field['type'] ?? 'textarea') === 'text')
                    <input id="field-{{ $field['key'] }}"
                           class="input"
                           :value="values[@js($field['key'])] ?? ''"
                           @input="update(@js($field['key']), $event.target.value)"
                           placeholder="{{ $field['placeholder'] ?? '' }}">
                @else
                    <textarea id="field-{{ $field['key'] }}"
                              rows="3"
                              class="textarea"
                              :value="values[@js($field['key'])] ?? ''"
                              @input="update(@js($field['key']), $event.target.value)"
                              placeholder="{{ $field['placeholder'] ?? '' }}"></textarea>
                @endif

                @if(! empty($field['hint']))
                    <p class="mt-1.5 text-[11px] leading-relaxed text-muted">{{ $field['hint'] }}</p>
                @endif

                <p x-show="stopWords(@js($field['key'])).length > 0" x-cloak class="mt-1.5 rounded-xl border border-amber/20 bg-amber-soft/40 px-3 py-2 text-[11px] leading-relaxed text-amber-ink">
                    Стоп-слова: <span class="font-bold" x-text="stopWords(@js($field['key'])).join(', ')"></span>. Попробуйте заменить их на факт и просьбу.
                </p>
            </div>
        @endforeach
    </div>

    <p class="mt-4 text-[11px] leading-relaxed text-muted">Сервер проверит полноту, осмысленность текста и стоп-слова. Это не оценка личности — это подсказка, что усилить.</p>
</div>

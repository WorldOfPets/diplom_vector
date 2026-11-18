@if(empty($schema))
    <div class="card p-5">
        <p class="text-sm font-bold">JSON-конфигурация</p>
        <p class="mt-1 text-[11px] text-muted">Для этого движка визуальная схема пока не описана.</p>
        <textarea name="config_json" rows="18" class="textarea mt-3 font-mono text-[11px]" aria-label="JSON-конфигурация">{{ json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
    </div>
@else
    <div x-data="schemaEditor(@js($schema), @js($config))" class="space-y-4">
        @foreach($schema as $field)
            @php $key = $field['key']; @endphp
            <div class="card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <label class="text-sm font-bold text-ink">{{ $field['label'] ?? $key }}</label>
                        @if(! empty($field['hint']))
                            <p class="mt-1 text-[11px] leading-relaxed text-muted">{{ $field['hint'] }}</p>
                        @endif
                    </div>
                    <span class="chip font-mono">{{ $key }}</span>
                </div>

                @switch($field['type'])
                    @case('textarea')
                        <textarea class="textarea mt-3 text-xs" rows="3" x-model="data['{{ $key }}']" placeholder="{{ $field['placeholder'] ?? '' }}" aria-label="{{ $field['label'] ?? $key }}"></textarea>
                        @break
                    @case('number')
                        <input type="number" class="input mt-3" x-model.number="data['{{ $key }}']" placeholder="{{ $field['placeholder'] ?? '' }}" aria-label="{{ $field['label'] ?? $key }}">
                        @break
                    @case('boolean')
                        <label class="mt-3 flex items-center gap-2 text-xs font-semibold text-ink-soft">
                            <input type="checkbox" class="h-4 w-4 rounded border-line text-brand" x-model="data['{{ $key }}']">
                            Включено
                        </label>
                        @break
                    @case('select')
                        <select class="input mt-3" x-model="data['{{ $key }}']" aria-label="{{ $field['label'] ?? $key }}">
                            @foreach($field['options'] ?? [] as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                        @break
                    @case('color')
                        <div class="mt-3 flex items-center gap-3">
                            <input type="color" class="h-10 w-14 cursor-pointer rounded-xl border border-line bg-surface p-1" x-model="data['{{ $key }}']" aria-label="{{ $field['label'] ?? $key }} — цвет">
                            <input class="input flex-1" x-model="data['{{ $key }}']" aria-label="{{ $field['label'] ?? $key }}">
                        </div>
                        @break
                    @case('json')
                        <textarea class="textarea mt-3 font-mono text-[11px]" rows="8"
                                  aria-label="{{ $field['label'] ?? $key }}"
                                  :value="jsonText['{{ $key }}']"
                                  @input="updateJson('{{ $key }}', $event.target.value)"></textarea>
                        <p class="mt-1 text-[10px] font-bold text-rose" x-show="jsonError['{{ $key }}']" x-text="jsonError['{{ $key }}']" x-cloak></p>
                        @break
                    @case('repeater')
                        <div class="mt-3 space-y-2.5">
                            <template x-for="(item, index) in data['{{ $key }}']" :key="index">
                                <div class="rounded-2xl border border-line bg-paper/50 p-3">
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        @foreach($field['fields'] ?? [] as $sub)
                                            @php $subKey = $sub['key']; @endphp
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-[0.1em] text-muted">{{ $sub['label'] ?? $subKey }}</label>
                                                @switch($sub['type'])
                                                    @case('textarea')
                                                        <textarea rows="2" class="textarea min-h-16 text-xs" x-model="item['{{ $subKey }}']" placeholder="{{ $sub['placeholder'] ?? '' }}" aria-label="{{ $sub['label'] ?? $subKey }}"></textarea>
                                                        @break
                                                    @case('number')
                                                        <input type="number" class="input py-2 text-xs" x-model.number="item['{{ $subKey }}']" aria-label="{{ $sub['label'] ?? $subKey }}">
                                                        @break
                                                    @case('boolean')
                                                        <label class="flex items-center gap-2 text-xs text-ink-soft">
                                                            <input type="checkbox" class="h-4 w-4 rounded border-line text-brand" x-model="item['{{ $subKey }}']">
                                                            Да
                                                        </label>
                                                        @break
                                                    @case('select')
                                                        <select class="input py-2 text-xs" x-model="item['{{ $subKey }}']" aria-label="{{ $sub['label'] ?? $subKey }}">
                                                            @foreach($sub['options'] ?? [] as $optionValue => $optionLabel)
                                                                <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                                            @endforeach
                                                        </select>
                                                        @break
                                                    @case('json')
                                                        <textarea rows="2" class="textarea min-h-16 font-mono text-[10px]"
                                                                  aria-label="{{ $sub['label'] ?? $subKey }}"
                                                                  :value="JSON.stringify(item['{{ $subKey }}'] || {})"
                                                                  @input="updateItemJson('{{ $key }}', index, '{{ $subKey }}', $event.target.value)"></textarea>
                                                        @break
                                                    @default
                                                        <input class="input py-2 text-xs" x-model="item['{{ $subKey }}']" placeholder="{{ $sub['placeholder'] ?? '' }}" aria-label="{{ $sub['label'] ?? $subKey }}">
                                                @endswitch
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" @click="remove('{{ $key }}', index)" class="mt-2 text-[10px] font-bold text-rose hover:underline">Удалить</button>
                                </div>
                            </template>

                            <button type="button" @click="add('{{ $key }}')" class="btn-secondary w-full py-2 text-xs">+ Добавить элемент</button>
                        </div>
                        @break
                    @default
                        <input class="input mt-3" x-model="data['{{ $key }}']" placeholder="{{ $field['placeholder'] ?? '' }}" aria-label="{{ $field['label'] ?? $key }}">
                @endswitch
            </div>
        @endforeach

        <input type="hidden" name="config_json" value="{{ json_encode($config, JSON_UNESCAPED_UNICODE) }}" :value="JSON.stringify(data)">
    </div>
@endif

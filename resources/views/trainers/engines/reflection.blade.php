@php
    $cards = $config['cards'] ?? [];
    $before = $config['sliders_before'] ?? [];
    $after = $config['sliders_after'] ?? [];
    $actions = $config['actions'] ?? [];
    $texts = $config['texts'] ?? [];

    $beforeDefaults = collect($before)->mapWithKeys(fn ($slider): array => [$slider['key'] => $slider['min'] ?? 0])->all();
    $afterDefaults = collect($after)->mapWithKeys(fn ($slider): array => [$slider['key'] => $slider['min'] ?? 0])->all();
@endphp

<div class="space-y-4"
     x-data="{
        toggleAction(id) {
            const current = [...(response.actions || [])];
            const index = current.indexOf(id);
            if (index >= 0) { current.splice(index, 1); } else { current.push(id); }
            $dispatch('response-patch', { actions: current });
        }
     }"
     x-init="$dispatch('response-patch', {
        card: response.card || null,
        before: { ...@js($beforeDefaults), ...(response.before || {}) },
        after: { ...@js($afterDefaults), ...(response.after || {}) },
        texts: { ...(response.texts || {}) }
     })">

    @if(! empty($config['drawing']))
        @include('trainers.engines.partials.drawing', ['config' => $config, 'saved' => $saved, 'readonly' => false])
    @endif

    @if(! empty($config['puppet']))
        @include('trainers.engines.partials.puppet', ['config' => $config, 'saved' => $saved])
    @endif

    <div class="card p-5 sm:p-6">
        <div class="flex items-start gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-coral-soft text-coral"><x-icon name="heart" class="h-4 w-4" /></span>
            <div>
                <p class="text-sm font-bold">{{ $config['title'] ?? 'Настройтесь на упражнение' }}</p>
                <p class="mt-1 text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Выполняйте шаги в своём темпе. Здесь не нужно спешить.' }}</p>
            </div>
        </div>

        @if($cards !== [])
            <div class="mt-5 grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                @foreach($cards as $card)
                    <button type="button" @click="$dispatch('response-patch', { card: '{{ $card['id'] }}', card_title: '{{ $card['title'] }}' })"
                            class="group relative overflow-hidden rounded-2xl border p-3 text-left transition"
                            :class="response.card === '{{ $card['id'] }}' ? 'border-brand bg-brand-soft/50' : 'border-line bg-surface hover:border-brand/30'">
                        <div class="mb-2 grid h-16 place-items-center rounded-xl bg-paper text-2xl">{{ $card['emoji'] ?? '✦' }}</div>
                        <p class="text-xs font-extrabold">{{ $card['title'] }}</p>
                        @if(! empty($card['description']))
                            <p class="mt-1 text-[10px] leading-relaxed text-muted">{{ $card['description'] }}</p>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if($before !== [])
        <div class="card p-5 sm:p-6">
            <p class="text-sm font-bold">До упражнения</p>
            <p class="mt-1 text-xs text-muted">Отметьте, как вы себя чувствуете прямо сейчас.</p>
            <div class="mt-4 space-y-4">
                @foreach($before as $slider)
                    <div>
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span>{{ $slider['label'] }}</span>
                            <span class="text-brand" x-text="response.before?.['{{ $slider['key'] }}'] ?? {{ $slider['min'] ?? 0 }}"></span>
                        </div>
                        <input type="range" min="{{ $slider['min'] ?? 0 }}" max="{{ $slider['max'] ?? 10 }}" x-model.number="response.before['{{ $slider['key'] }}']" class="mt-2 w-full accent-[#FF6B4A]" aria-label="{{ $slider['label'] }}">
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($actions !== [])
        <div class="card p-5 sm:p-6">
            <p class="text-sm font-bold">{{ $config['actions_label'] ?? 'Что поможет?' }}</p>
            <p class="mt-1 text-xs text-muted">{{ $config['actions_hint'] ?? 'Выберите всё, что откликается. Можно несколько.' }}</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($actions as $action)
                    <button type="button" @click="toggleAction('{{ $action['id'] }}')"
                            class="rounded-full border px-3 py-2 text-xs font-bold transition"
                            :class="(response.actions || []).includes('{{ $action['id'] }}') ? 'border-brand/30 bg-brand-soft text-brand-deep' : 'border-line bg-surface text-ink-soft hover:border-brand/20'">
                        {{ $action['label'] }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    @if($after !== [])
        <div class="card p-5 sm:p-6">
            <p class="text-sm font-bold">После упражнения</p>
            <p class="mt-1 text-xs text-muted">Сравните с состоянием «до». Разница важнее абсолютных цифр.</p>
            <div class="mt-4 space-y-4">
                @foreach($after as $slider)
                    <div>
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span>{{ $slider['label'] }}</span>
                            <span class="text-mint-ink" x-text="response.after?.['{{ $slider['key'] }}'] ?? {{ $slider['min'] ?? 0 }}"></span>
                        </div>
                        <input type="range" min="{{ $slider['min'] ?? 0 }}" max="{{ $slider['max'] ?? 10 }}" x-model.number="response.after['{{ $slider['key'] }}']" class="mt-2 w-full accent-[#2BB673]" aria-label="{{ $slider['label'] }}">
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($texts !== [])
        <div class="card p-5 sm:p-6">
            <p class="text-sm font-bold">Короткая заметка</p>
            <p class="mt-1 text-xs text-muted">Это увидите только вы и куратор. Без оценки.</p>
            <div class="mt-4 space-y-4">
                @foreach($texts as $text)
                    <div>
                        <label class="label" for="text-{{ $text['key'] }}">{{ $text['label'] }}</label>
                        <textarea id="text-{{ $text['key'] }}" rows="3" class="textarea" x-model="response.texts['{{ $text['key'] }}']" placeholder="{{ $text['placeholder'] ?? '' }}"></textarea>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

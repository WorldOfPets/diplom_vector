@php
    $options = $config['options'] ?? [];
    $mode = $config['mode'] ?? 'best_worst';
@endphp

<div class="card p-5 sm:p-6"
     x-data="{
        setBest(id) {
            const patch = { best: id };
            if (response.worst === id) { patch.worst = null; }
            $dispatch('response-patch', patch);
        },
        setWorst(id) {
            const patch = { worst: id };
            if (response.best === id) { patch.best = null; }
            $dispatch('response-patch', patch);
        }
     }">
    <div class="flex items-start gap-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-coral-soft text-coral"><x-icon name="target" class="h-4 w-4" /></span>
        <div>
            <p class="text-sm font-bold">{{ $mode === 'profile' ? 'Выберите свою реакцию' : 'Лучшая и худшая реакция' }}</p>
            <p class="mt-1 text-xs leading-relaxed text-muted">
                {{ $mode === 'profile' ? ($config['prompt'] ?? 'Выберите вариант, который ближе к тому, как вы обычно действуете.') : ($config['prompt'] ?? 'Отметьте, что сработает лучше всего, а что — хуже всего.') }}
            </p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl bg-paper/70 p-4 text-sm leading-relaxed text-ink-soft">
        {{ $config['scenario'] ?? '' }}
    </div>

    <div class="mt-4 space-y-2.5">
        @foreach($options as $option)
            @if($mode === 'profile')
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border p-3.5 transition"
                       :class="response.choice === '{{ $option['id'] }}' ? 'border-brand bg-brand-soft/40' : 'border-line bg-surface hover:border-brand/20'">
                    <input type="radio" name="choice" value="{{ $option['id'] }}" x-model="response.choice" class="mt-1 h-4 w-4 border-line text-brand focus:ring-brand">
                    <span class="text-sm leading-relaxed">{{ $option['label'] }}</span>
                </label>
            @else
                <div class="rounded-2xl border p-3.5 transition"
                     :class="response.best === '{{ $option['id'] }}' || response.worst === '{{ $option['id'] }}' ? 'border-brand/30 bg-brand-soft/20' : 'border-line bg-surface'">
                    <p class="text-sm leading-relaxed">{{ $option['label'] }}</p>
                    <div class="mt-2 flex gap-2">
                        <button type="button" @click="setBest('{{ $option['id'] }}')"
                                class="rounded-full border px-3 py-1.5 text-[10px] font-extrabold transition"
                                :class="response.best === '{{ $option['id'] }}' ? 'border-mint/30 bg-mint-soft text-mint-ink' : 'border-line text-muted hover:border-mint/30'">
                            лучшая
                        </button>
                        <button type="button" @click="setWorst('{{ $option['id'] }}')"
                                class="rounded-full border px-3 py-1.5 text-[10px] font-extrabold transition"
                                :class="response.worst === '{{ $option['id'] }}' ? 'border-rose/30 bg-rose/10 text-rose' : 'border-line text-muted hover:border-rose/30'">
                            худшая
                        </button>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    @if($mode !== 'profile')
        <p class="mt-3 text-[11px] leading-relaxed text-muted">Лучшая и худшая реакции не могут совпадать. Выберите два разных варианта.</p>
    @endif
</div>

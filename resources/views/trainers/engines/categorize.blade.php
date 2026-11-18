@php
    $buckets = $config['buckets'] ?? [];
    $items = $config['items'] ?? [];
@endphp

<div class="card p-5 sm:p-6"
     x-data="{
        assign(item, bucket) {
            const current = { ...(response.buckets || {}) };
            current[item] = bucket;
            $dispatch('response-patch', { buckets: current });
        },
        count(bucket) {
            return Object.values(response.buckets || {}).filter((value) => value === bucket).length;
        }
     }"
     x-init="$dispatch('response-patch', { buckets: { ...(response.buckets || {}) } })">
    <div class="flex items-start gap-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-lilac-soft text-lilac"><x-icon name="grid" class="h-4 w-4" /></span>
        <div>
            <p class="text-sm font-bold">{{ $config['title'] ?? 'Разложите по категориям' }}</p>
            <p class="mt-1 text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Выберите категорию для каждой карточки.' }}</p>
        </div>
    </div>

    <div class="mt-5 space-y-2.5">
        @foreach($items as $item)
            <div class="rounded-2xl border border-line bg-surface p-3.5">
                <div class="flex items-start gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-paper text-base">{{ $item['emoji'] ?? '•' }}</span>
                    <p class="flex-1 text-sm font-semibold leading-snug">{{ $item['label'] }}</p>
                </div>
                <div class="mt-2.5 flex flex-wrap gap-2">
                    @foreach($buckets as $bucket)
                        <button type="button"
                                @click="assign('{{ $item['id'] }}', '{{ $bucket['id'] }}')"
                                class="rounded-full border px-3 py-1.5 text-[10px] font-extrabold transition"
                                :class="response.buckets?.['{{ $item['id'] }}'] === '{{ $bucket['id'] }}' ? 'border-brand/30 bg-brand-soft text-brand-deep' : 'border-line text-muted hover:border-brand/20'">
                            {{ $bucket['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($buckets as $bucket)
            <div class="rounded-2xl border border-line/70 bg-paper/50 p-3">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-[11px] font-bold text-ink-soft">
                        <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $bucket['color'] ?? '#2D5BFF' }};"></span>
                        {{ $bucket['label'] }}
                    </span>
                    <span class="text-sm font-extrabold" x-text="count('{{ $bucket['id'] }}')"></span>
                </div>
            </div>
        @endforeach
    </div>
</div>

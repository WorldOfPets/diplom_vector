@php
    $max = (int) ($config['max_tags'] ?? 8);
    $tags = $config['tags'] ?? [];
    $categories = $config['categories'] ?? [];
@endphp

<div class="card p-5 sm:p-6"
     x-data="{
        toggle(id) {
            const current = response.tags || {};
            const weight = current[id] || 0;
            let next = { ...current };
            if (weight >= 3) { delete next[id]; }
            else if (weight === 0 && Object.keys(current).length >= {{ $max }}) { return; }
            else { next[id] = weight + 1; }
            $dispatch('response-patch', { tags: next });
        }
     }">
    <div class="flex items-start gap-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-lilac-soft text-lilac"><x-icon name="target" class="h-4 w-4" /></span>
        <div>
            <p class="text-sm font-bold">Выберите теги</p>
            <p class="mt-1 text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Нажимайте на теги: 1 — важно, 2 — очень важно, 3 — критично. Повторное нажатие снимает выбор.' }}</p>
        </div>
    </div>

    <div class="mt-5 flex flex-wrap gap-2">
        @foreach($tags as $tag)
            <button type="button" @click="toggle('{{ $tag['id'] }}')"
                    class="inline-flex items-center gap-1.5 rounded-full border px-3 py-2 text-xs font-bold transition"
                    :class="(response.tags?.['{{ $tag['id'] }}'] || 0) > 0 ? 'border-brand/30 bg-brand-soft text-brand-deep' : 'border-line bg-surface text-ink-soft hover:border-brand/20'">
                {{ $tag['label'] }}
                <span class="grid h-4 min-w-4 place-items-center rounded-full bg-surface/80 px-1 text-[10px] font-extrabold"
                      x-show="(response.tags?.['{{ $tag['id'] }}'] || 0) > 0"
                      x-text="response.tags?.['{{ $tag['id'] }}']"></span>
            </button>
        @endforeach
    </div>

    <div class="mt-5 flex flex-wrap items-center gap-2">
        <span class="chip">Выбрано: <span class="font-extrabold" x-text="Object.keys(response.tags || {}).length"></span> из {{ $max }}</span>
        @foreach($categories as $category)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-paper px-2.5 py-1 text-[10px] font-bold text-muted">
                <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $category['color'] ?? '#2D5BFF' }};"></span>
                {{ $category['label'] }}
            </span>
        @endforeach
    </div>
</div>

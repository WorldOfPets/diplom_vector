@php
    $emotions = $config['emotions'] ?? [];
    $needs = $config['needs'] ?? [];
    $max = (int) ($config['choose'] ?? 3);
    $image = $config['image'] ?? null;
    $photo = \App\Support\PhotoLibrary::get($image);
@endphp

<div class="space-y-4"
     x-data="{
        toggle(id) {
            const current = [...(response.emotions || [])];
            const index = current.indexOf(id);
            if (index >= 0) { current.splice(index, 1); }
            else if (current.length < {{ $max }}) { current.push(id); }
            $dispatch('response-patch', { emotions: current });
        }
     }"
     x-init="$dispatch('response-patch', { emotions: response.emotions || [], intensity: response.intensity || 3, need: response.need || null })">

    <div class="card overflow-hidden">
        <div class="relative aspect-[16/10] bg-gradient-to-br from-brand-soft via-paper to-coral-soft">
            @if($image)
                <x-photo :path="$image" credit="none" class="h-full" img-class="h-full w-full object-cover" />
            @else
                <div class="flex h-full flex-col items-center justify-center gap-2 text-center">
                    <span class="text-4xl">{{ $config['emoji'] ?? '🎭' }}</span>
                    <p class="max-w-xs text-xs font-semibold text-ink-soft">{{ $config['image_alt'] ?? 'Представьте сцену из колледжа' }}</p>
                </div>
            @endif
        </div>
        <div class="p-5">
            <p class="text-sm font-bold">{{ $config['prompt'] ?? 'Что, скорее всего, чувствует человек?' }}</p>
            <p class="mt-1 text-xs leading-relaxed text-muted">Выберите до {{ $max }} эмоций — не только «плохо» или «хорошо», а точные слова.</p>
            @if($photo)
                <p class="mt-1 text-[10px] font-semibold leading-relaxed text-muted">
                    Фото:
                    <a href="{{ $photo['author_url'] }}" target="_blank" rel="noopener noreferrer" class="underline decoration-line underline-offset-2 hover:text-ink">{{ $photo['author'] }}</a>
                    /
                    <a href="{{ $photo['source_url'] }}" target="_blank" rel="noopener noreferrer" class="underline decoration-line underline-offset-2 hover:text-ink">{{ $photo['source'] }}</a>
                    · иллюстрация, а не диагностический стимул
                </p>
            @endif

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($emotions as $emotion)
                    <button type="button" @click="toggle('{{ $emotion['id'] }}')"
                            class="rounded-full border px-3 py-2 text-xs font-bold transition"
                            :class="(response.emotions || []).includes('{{ $emotion['id'] }}') ? 'border-brand/30 bg-brand-soft text-brand-deep' : 'border-line bg-surface text-ink-soft hover:border-brand/20'">
                        {{ $emotion['label'] }}
                    </button>
                @endforeach
            </div>

            <div class="mt-5 rounded-2xl bg-paper/70 p-4">
                <div class="flex items-center justify-between text-xs font-bold">
                    <span>Насколько сильно?</span>
                    <span class="text-brand" x-text="response.intensity || 3"></span>
                </div>
                <input type="range" min="1" max="5" x-model.number="response.intensity" class="mt-2 w-full accent-[#2D5BFF]" aria-label="Интенсивность">
                <div class="mt-1 flex justify-between text-[10px] font-semibold text-muted"><span>слабо</span><span>очень сильно</span></div>
            </div>

            @if($needs !== [])
                <div class="mt-4">
                    <label class="label" for="need">Что этому человеку нужно?</label>
                    <select id="need" class="input" x-model="response.need">
                        <option value="">Выберите…</option>
                        @foreach($needs as $need)
                            <option value="{{ $need['id'] }}">{{ $need['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>

    <p class="text-[11px] leading-relaxed text-muted">Нет одного «правильного» ответа на все случаи. Ключ учитывает контекст: что произошло до сцены и чего человек ждал.</p>
</div>

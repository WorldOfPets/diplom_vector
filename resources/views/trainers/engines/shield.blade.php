@php
    $sectors = $config['sectors'] ?? [];
    $symbols = $config['symbols'] ?? [];
    $sectorDefaults = collect($sectors)->mapWithKeys(fn ($sector): array => [$sector['key'] => ''])->all();
    $symbolDefaults = collect($sectors)->mapWithKeys(fn ($sector): array => [$sector['key'] => $sector['emoji'] ?? '✦'])->all();
    $color = $config['color'] ?? '#2D5BFF';
@endphp

<div class="card p-5 sm:p-6"
     x-data
     x-init="$dispatch('response-patch', {
        sectors: { ...@js($sectorDefaults), ...(response.sectors || {}) },
        symbols: { ...@js($symbolDefaults), ...(response.symbols || {}) },
        color: response.color || '{{ $color }}'
     })">
    <div class="flex items-start gap-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-amber-soft text-amber-ink"><x-icon name="shield" class="h-4 w-4" /></span>
        <div>
            <p class="text-sm font-bold">{{ $config['title'] ?? 'Профессиональный герб' }}</p>
            <p class="mt-1 text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Заполните секторы герба. Это метафора вашей профессиональной опоры.' }}</p>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="flex flex-col items-center">
            <div class="relative w-full max-w-xs" style="filter: drop-shadow(0 16px 28px rgba(16,24,40,.12));">
                <div class="grid grid-cols-2 gap-1 overflow-hidden p-4 pb-12 text-white"
                     style="clip-path: polygon(50% 0, 100% 15%, 92% 82%, 50% 100%, 8% 82%, 0 15%); background: {{ $color }}; min-height: 360px;">
                    @foreach($sectors as $sector)
                        <div class="flex flex-col items-center justify-center gap-1 rounded-xl bg-black/15 p-2 text-center">
                            <span class="text-lg" x-text="response.symbols?.['{{ $sector['key'] }}'] || '✦'"></span>
                            <span class="text-[9px] font-bold uppercase tracking-[0.08em] text-white/70">{{ $sector['label'] }}</span>
                            <span class="line-clamp-2 text-[10px] font-semibold text-white/90" x-text="response.sectors?.['{{ $sector['key'] }}'] || '—'"></span>
                        </div>
                    @endforeach
                </div>
            </div>
            <p class="mt-3 text-[10px] font-semibold text-muted">Предпросмотр обновляется по мере заполнения</p>
        </div>

        <div class="space-y-3">
            @foreach($sectors as $sector)
                <div class="rounded-2xl border border-line bg-surface p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-extrabold">{{ $sector['label'] }}</p>
                            @if(! empty($sector['hint']))
                                <p class="mt-0.5 text-[10px] leading-relaxed text-muted">{{ $sector['hint'] }}</p>
                            @endif
                        </div>
                        @if($symbols !== [])
                            <select class="rounded-xl border border-line bg-paper px-2 py-1 text-sm" x-model="response.symbols['{{ $sector['key'] }}']" aria-label="Символ: {{ $sector['label'] }}">
                                @foreach($symbols as $symbol)
                                    <option value="{{ $symbol['emoji'] }}">{{ $symbol['emoji'] }} {{ $symbol['label'] }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <input class="input mt-2 py-2.5 text-xs" x-model="response.sectors['{{ $sector['key'] }}']" placeholder="{{ $sector['placeholder'] ?? 'Коротко' }}" maxlength="120">
                </div>
            @endforeach
        </div>
    </div>
</div>

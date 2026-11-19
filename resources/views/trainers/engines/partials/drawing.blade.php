@php
    $drawing = is_array($config['drawing'] ?? null) ? $config['drawing'] : [];
    $palette = array_values(array_filter(
        is_array($drawing['palette'] ?? null) ? $drawing['palette'] : [],
        fn (mixed $color): bool => is_string($color) && preg_match('/^#[0-9a-f]{6}$/i', $color) === 1,
    ));

    if ($palette === []) {
        $palette = ['#FF6B4A', '#F59E0B', '#2D5BFF', '#2BB673', '#7C5CFF'];
    }

    $background = in_array($drawing['background'] ?? 'blank', ['blank', 'body', 'shield'], true)
        ? ($drawing['background'] ?? 'blank')
        : 'blank';
    $strokes = is_array($saved['strokes'] ?? null) ? $saved['strokes'] : [];
    $readonly = (bool) ($readonly ?? false);
    $title = $readonly
        ? ($drawing['result_title'] ?? 'Ваш рисунок')
        : ($drawing['title'] ?? 'Нарисуйте состояние');
    $prompt = $drawing['prompt'] ?? 'Рисуйте пальцем или мышью. Красиво не нужно — важно заметить.';
@endphp

<div class="card p-5 sm:p-6"
     x-data="drawingBoard({ palette: @js($palette), initial: @js($strokes), readonly: {{ $readonly ? 'true' : 'false' }}, maxPoints: {{ (int) ($drawing['max_points'] ?? 4000) }} })">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-coral-soft text-coral"><x-icon name="edit" class="h-4 w-4" /></span>
            <div>
                <p class="text-sm font-bold">{{ $title }}</p>
                <p class="mt-1 max-w-lg text-xs leading-relaxed text-muted">{{ $prompt }}</p>
            </div>
        </div>
        <span class="chip" x-text="`${count} штрих(ов)`"></span>
    </div>

    @if(! $readonly)
        <div class="mt-4 flex flex-wrap items-center gap-2">
            @foreach($palette as $color)
                <button type="button"
                        @click="color = @js($color)"
                        class="h-8 w-8 rounded-full border-2 transition"
                        :class="color === @js($color) ? 'border-ink scale-110' : 'border-white/80'"
                        :style="'background-color: ' + @js($color)"
                        aria-label="Цвет {{ $color }}"></button>
            @endforeach

            <span class="mx-1 h-6 w-px bg-line"></span>

            @foreach([3, 6, 12] as $size)
                <button type="button"
                        @click="brush = {{ $size }}"
                        class="grid h-8 w-8 place-items-center rounded-full border transition"
                        :class="brush === {{ $size }} ? 'border-brand bg-brand-soft text-brand-deep' : 'border-line text-muted'"
                        aria-label="Кисть {{ $size }} px">
                    <span class="rounded-full bg-current" style="width: {{ max(3, $size / 2) }}px; height: {{ max(3, $size / 2) }}px;"></span>
                </button>
            @endforeach

            <span class="mx-1 h-6 w-px bg-line"></span>

            <button type="button" @click="undo()" :disabled="count === 0" class="rounded-full border border-line px-3 py-1.5 text-[10px] font-extrabold text-muted transition hover:bg-paper disabled:opacity-40">
                Отменить
            </button>
            <button type="button" @click="clear()" :disabled="count === 0" class="rounded-full border border-line px-3 py-1.5 text-[10px] font-extrabold text-muted transition hover:bg-paper disabled:opacity-40">
                Очистить
            </button>
        </div>
    @endif

    <div class="relative mt-4 overflow-hidden rounded-2xl border border-line bg-paper"
         style="aspect-ratio: 3 / 4; max-height: 70vh;">
        @if($background === 'body')
            <svg viewBox="0 0 300 400" class="pointer-events-none absolute inset-0 h-full w-full text-line" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <circle cx="150" cy="58" r="30" />
                <path d="M150 88 C120 88 105 108 100 135 L88 220" />
                <path d="M150 88 C180 88 195 108 200 135 L212 220" />
                <path d="M100 135 C100 170 105 200 118 225 L118 270" />
                <path d="M200 135 C200 170 195 200 182 225 L182 270" />
                <path d="M118 270 L110 385" />
                <path d="M182 270 L190 385" />
                <path d="M118 270 C130 280 170 280 182 270" />
                <path d="M88 220 L120 260" />
                <path d="M212 220 L180 260" />
            </svg>
        @elseif($background === 'shield')
            <svg viewBox="0 0 300 400" class="pointer-events-none absolute inset-0 h-full w-full text-line" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M150 25 L255 65 L240 285 L150 375 L60 285 L45 65 Z" />
                <path d="M150 25 L150 375" stroke-dasharray="6 8" />
                <path d="M45 65 L255 65" stroke-dasharray="6 8" />
            </svg>
        @endif

        <canvas x-ref="canvas"
                class="absolute inset-0 h-full w-full"
                style="touch-action: none;"
                @pointerdown="start"
                @pointermove="move"
                @pointerup="end"
                @pointercancel="end"
                aria-label="Поле для рисования"></canvas>
    </div>

    <p class="mt-3 text-[11px] leading-relaxed text-muted">
        Рисунок сохраняется как набор штрихов, а не фотография. Его видно только вам и куратору.
        @if(! $readonly)
            Можно рисовать пальцем на телефоне.
        @endif
    </p>
</div>

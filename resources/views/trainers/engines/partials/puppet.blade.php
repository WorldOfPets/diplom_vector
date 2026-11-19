@php
    $puppet = is_array($config['puppet'] ?? null) ? $config['puppet'] : [];
    $target = is_string($puppet['target'] ?? null) ? $puppet['target'] : 'confident';
    $targets = is_array($puppet['targets'] ?? null) ? $puppet['targets'] : [
        'confident' => 'уверенный ответ',
        'anxious' => 'тревога',
        'closed' => 'закрытость',
        'aggressive' => 'напряжение и злость',
    ];
    $initial = is_array($saved['puppet'] ?? null) ? $saved['puppet'] : [];
    $controls = [
        ['key' => 'head', 'label' => 'Голова', 'options' => ['up' => 'Поднята', 'level' => 'Прямо', 'down' => 'Опущена']],
        ['key' => 'shoulders', 'label' => 'Плечи', 'options' => ['raised' => 'Подняты', 'neutral' => 'Расслаблены', 'low' => 'Опущены']],
        ['key' => 'arms', 'label' => 'Руки', 'options' => ['open' => 'Открыты', 'crossed' => 'Скрещены', 'hips' => 'На поясе']],
        ['key' => 'posture', 'label' => 'Корпус', 'options' => ['straight' => 'Прямой', 'slouched' => 'Ссутулен']],
    ];
@endphp

@if($puppet !== [])
    <div class="card p-5 sm:p-6"
         x-data="puppetFigure({ initial: @js($initial), target: @js($target), targets: @js($targets) })">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-lilac-soft text-lilac"><x-icon name="user" class="h-4 w-4" /></span>
                <div>
                    <p class="text-sm font-bold">{{ $puppet['title'] ?? 'Соберите позу' }}</p>
                    <p class="mt-1 max-w-lg text-xs leading-relaxed text-muted">{{ $puppet['prompt'] ?? 'Меняйте положение частей тела и смотрите, как меняется чтение позы.' }}</p>
                </div>
            </div>
            <span class="chip">Цель: {{ $targets[$target] ?? $target }}</span>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="rounded-2xl border border-line bg-paper/60 p-3">
                <svg viewBox="0 0 300 400" class="mx-auto h-72 w-full max-w-[250px] text-ink-soft" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" aria-label="Кукла: поза тела">
                    <ellipse cx="150" cy="374" rx="72" ry="9" fill="currentColor" stroke="none" opacity="0.08" />

                    <g :transform="postureTransform">
                        <g :transform="headTransform">
                            <circle cx="150" cy="60" r="28" />
                            <circle cx="140" cy="56" r="2.4" fill="currentColor" stroke="none" />
                            <circle cx="160" cy="56" r="2.4" fill="currentColor" stroke="none" />
                            <path d="M140 70 Q150 76 160 70" stroke-width="3" />
                        </g>

                        <path d="M150 88 L150 108" />

                        <g :transform="shoulderTransform">
                            <path d="M105 120 Q150 102 195 120" stroke-width="7" />
                            <path d="M118 120 Q150 112 182 120 L190 255 Q150 270 110 255 Z" />
                        </g>

                        <g x-show="state.arms === 'open'">
                            <path d="M105 125 L70 200" />
                            <path d="M195 125 L230 200" />
                            <circle cx="70" cy="202" r="6" fill="currentColor" stroke="none" />
                            <circle cx="230" cy="202" r="6" fill="currentColor" stroke="none" />
                        </g>

                        <g x-show="state.arms === 'crossed'">
                            <path d="M110 130 L190 215" />
                            <path d="M190 130 L110 215" />
                            <circle cx="190" cy="217" r="6" fill="currentColor" stroke="none" />
                            <circle cx="110" cy="217" r="6" fill="currentColor" stroke="none" />
                        </g>

                        <g x-show="state.arms === 'hips'">
                            <path d="M110 130 L92 230 L122 252" />
                            <path d="M190 130 L208 230 L178 252" />
                            <circle cx="122" cy="252" r="6" fill="currentColor" stroke="none" />
                            <circle cx="178" cy="252" r="6" fill="currentColor" stroke="none" />
                        </g>

                        <path d="M130 255 L120 368" />
                        <path d="M170 255 L180 368" />
                    </g>
                </svg>
                <p class="mt-2 text-center text-[11px] font-bold text-muted">Поза читается как: <span class="text-ink" x-text="readingLabel"></span></p>
            </div>

            <div class="space-y-3">
                @foreach($controls as $control)
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-muted">{{ $control['label'] }}</p>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            @foreach($control['options'] as $value => $label)
                                <button type="button"
                                        @click="part(@js($control['key']), @js($value))"
                                        class="rounded-full border px-3 py-1.5 text-[10px] font-extrabold transition"
                                        :class="state[@js($control['key'])] === @js($value) ? 'border-brand bg-brand-soft text-brand-deep' : 'border-line bg-surface text-muted hover:border-brand/30'">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="rounded-2xl border border-brand/20 bg-brand-soft/30 p-3">
                    <div class="flex items-center justify-between text-[10px] font-bold text-brand-deep">
                        <span>Совпадение с целью</span>
                        <span x-text="`${match}%`"></span>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-line">
                        <div class="h-full rounded-full bg-brand transition-all" :style="`width: ${match}%`"></div>
                    </div>
                    <p class="mt-2 text-[10px] leading-relaxed text-muted">Это чтение невербальных сигналов, а не оценка личности. Контекст всегда важнее одной позы.</p>
                </div>
            </div>
        </div>
    </div>
@endif

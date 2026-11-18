@php
    $supported = in_array($type, ['single', 'multi', 'scene', 'image_choice', 'best_worst', 'ordering', 'motivation', 'matching', 'scale'], true);
@endphp

@if(! $supported)
    <div class="card p-5">
        <p class="text-sm font-bold">JSON-конфигурация</p>
        <p class="mt-1 text-[11px] text-muted">Для этого типа задания визуальный редактор пока не описан.</p>
        <textarea name="config_json" rows="8" class="textarea mt-3 font-mono text-[11px]" aria-label="JSON-конфигурация">{{ json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
        <textarea name="key_json" rows="6" class="textarea mt-3 font-mono text-[11px]" aria-label="JSON-ключ">{{ json_encode($key, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
    </div>
@else
    <div x-data="testItemBuilder('{{ $type }}', @js($config), @js($key))" class="space-y-4">
        @if($type === 'single' || $type === 'multi')
            <div class="card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold">Варианты ответа</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-muted">Вес 1 — правильный ответ, 0 — дистрактор. Можно ставить 0.5 для частичного кредита.</p>
                    </div>
                    <span class="chip">{{ $type === 'multi' ? 'несколько' : 'один' }}</span>
                </div>

                <div class="mt-3 space-y-2">
                    <template x-for="(option, index) in options" :key="index">
                        <div class="grid grid-cols-[70px_1fr_80px_30px] items-center gap-2">
                            <input class="input py-2 text-xs" x-model="option.id" placeholder="ID" aria-label="ID варианта">
                            <input class="input py-2 text-xs" x-model="option.label" placeholder="Текст варианта" aria-label="Текст варианта">
                            <input type="number" step="0.1" min="0" max="1.5" class="input py-2 text-xs" x-model.number="option.weight" aria-label="Вес варианта">
                            <button type="button" @click="removeOption(index)" class="text-rose" title="Удалить">×</button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addOption()" class="btn-secondary mt-3 w-full py-2 text-xs">+ Добавить вариант</button>
            </div>
        @endif

        @if($type === 'scene' || $type === 'image_choice')
            <div class="card p-5">
                <p class="text-sm font-bold">Сцена и эмоции</p>
                <p class="mt-1 text-[11px] leading-relaxed text-muted">Вес задаёт вклад эмоции в ключ. Дистракторы оставьте с весом 0.</p>
                <div class="mt-3">
                    <label class="label">Alt-текст изображения</label>
                    <input class="input" x-model="imageAlt" placeholder="Что изображено" aria-label="Alt-текст изображения">
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <div>
                        <label class="label">Сколько эмоций выбрать</label>
                        <input type="number" min="1" max="5" class="input" x-model.number="maxChoices" aria-label="Сколько эмоций выбрать">
                    </div>
                    <div>
                        <label class="label">Ориентир интенсивности</label>
                        <input type="number" min="1" max="5" class="input" x-model.number="intensityTarget" aria-label="Ориентир интенсивности">
                    </div>
                </div>
                <p class="mt-2 text-[10px] leading-relaxed text-muted">Интенсивность сохраняется для рефлексии, но не входит в балл: по фото её нельзя измерить объективно.</p>
                <div class="mt-3 space-y-2">
                    <template x-for="(emotion, index) in emotions" :key="index">
                        <div class="grid grid-cols-[70px_1fr_80px_30px] items-center gap-2">
                            <input class="input py-2 text-xs" x-model="emotion.id" placeholder="ID" aria-label="ID эмоции">
                            <input class="input py-2 text-xs" x-model="emotion.label" placeholder="Эмоция" aria-label="Название эмоции">
                            <input type="number" step="0.1" min="0" max="1.5" class="input py-2 text-xs" x-model.number="emotion.weight" aria-label="Вес эмоции">
                            <button type="button" @click="removeEmotion(index)" class="text-rose" title="Удалить">×</button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addEmotion()" class="btn-secondary mt-3 w-full py-2 text-xs">+ Добавить эмоцию</button>
            </div>
        @endif

        @if($type === 'best_worst')
            <div class="card p-5">
                <p class="text-sm font-bold">Лучшая и худшая реакция</p>
                <div class="mt-3">
                    <label class="label">Режим</label>
                    <select class="input" x-model="mode" aria-label="Режим">
                        <option value="best_worst">Лучшая/худшая</option>
                        <option value="profile">Профиль стратегий</option>
                    </select>
                </div>
                <div class="mt-3 space-y-2">
                    <template x-for="(option, index) in options" :key="index">
                        <div class="rounded-2xl border border-line p-2.5">
                            <div class="grid grid-cols-[70px_1fr_30px] items-center gap-2">
                                <input class="input py-2 text-xs" x-model="option.id" placeholder="ID" aria-label="ID варианта">
                                <input class="input py-2 text-xs" x-model="option.label" placeholder="Текст варианта" aria-label="Текст варианта">
                                <button type="button" @click="removeOption(index)" class="text-rose" title="Удалить">×</button>
                            </div>
                            <div x-show="mode === 'best_worst'" class="mt-2 flex gap-2">
                                <button type="button" @click="best = option.id" class="rounded-full border px-3 py-1 text-[10px] font-bold" :class="best === option.id ? 'border-mint/30 bg-mint-soft text-emerald-700' : 'border-line text-muted'">лучшая</button>
                                <button type="button" @click="worst = option.id" class="rounded-full border px-3 py-1 text-[10px] font-bold" :class="worst === option.id ? 'border-rose/30 bg-rose/10 text-rose' : 'border-line text-muted'">худшая</button>
                            </div>
                            <div x-show="mode === 'profile'" class="mt-2">
                                <label class="text-[10px] font-bold text-muted">Баллы по категориям (JSON)</label>
                                <input class="input mt-1 py-2 font-mono text-[10px]" :value="JSON.stringify(option.scores || {})" @input="updateOptionScores(index, $event.target.value)" aria-label="Баллы варианта по категориям, JSON">
                            </div>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addOption()" class="btn-secondary mt-3 w-full py-2 text-xs">+ Добавить вариант</button>

                <div x-show="mode === 'profile'" class="mt-4">
                    <p class="text-xs font-bold">Категории профиля</p>
                    <div class="mt-2 space-y-2">
                        <template x-for="(category, index) in categories" :key="index">
                            <div class="grid grid-cols-[70px_1fr_70px_30px] items-center gap-2">
                                <input class="input py-2 text-xs" x-model="category.key" placeholder="key" aria-label="Ключ категории">
                                <input class="input py-2 text-xs" x-model="category.label" placeholder="Название" aria-label="Название категории">
                                <input type="color" class="h-9 w-full rounded-lg border border-line" x-model="category.color" aria-label="Цвет категории">
                                <button type="button" @click="removeCategory(index)" class="text-rose" title="Удалить">×</button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addCategory()" class="btn-secondary mt-2 w-full py-2 text-xs">+ Добавить категорию</button>
                </div>
            </div>
        @endif

        @if($type === 'ordering')
            <div class="card p-5">
                <p class="text-sm font-bold">Элементы порядка</p>
                <p class="mt-1 text-[11px] text-muted">Порядок строк — правильный ответ. Перемещайте стрелками.</p>
                <div class="mt-3 space-y-2">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="grid grid-cols-[30px_1fr_60px] items-center gap-2">
                            <span class="text-xs font-bold text-muted" x-text="index + 1"></span>
                            <input class="input py-2 text-xs" x-model="item.label" placeholder="Текст" aria-label="Текст элемента порядка">
                            <div class="flex gap-1">
                                <button type="button" @click="moveItem(index, -1)" class="rounded-lg border border-line px-1.5 py-1 text-[10px]">↑</button>
                                <button type="button" @click="moveItem(index, 1)" class="rounded-lg border border-line px-1.5 py-1 text-[10px]">↓</button>
                                <button type="button" @click="removeItem(index)" class="text-rose">×</button>
                            </div>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addItem()" class="btn-secondary mt-3 w-full py-2 text-xs">+ Добавить элемент</button>
            </div>
        @endif

        @if($type === 'motivation')
            <div class="card p-5">
                <p class="text-sm font-bold">Параметры шкалы</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label">Субшкала</label>
                        <select class="input" x-model="subscale" aria-label="Субшкала">
                            <option value="internal">Внутренняя</option>
                            <option value="external">Внешняя</option>
                            <option value="professional">Профессиональная</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Минимум / максимум</label>
                        <div class="flex gap-2">
                            <input type="number" class="input" x-model.number="min" aria-label="Минимум шкалы">
                            <input type="number" class="input" x-model.number="max" aria-label="Максимум шкалы">
                        </div>
                    </div>
                </div>
                <label class="mt-3 flex items-center gap-2 text-xs font-semibold text-ink-soft">
                    <input type="checkbox" class="h-4 w-4 rounded border-line text-brand" x-model="reverse">
                    Обратный пункт
                </label>
            </div>
        @endif

        @if($type === 'matching')
            <div class="card p-5">
                <p class="text-sm font-bold">Сопоставление</p>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-bold">Левая колонка</p>
                        <div class="mt-2 space-y-2">
                            <template x-for="(item, index) in left" :key="index">
                                <div class="flex gap-2">
                                    <input class="input py-2 text-xs" x-model="item.id" placeholder="ID" aria-label="ID левого элемента">
                                    <input class="input py-2 text-xs" x-model="item.label" placeholder="Текст" aria-label="Текст левого элемента">
                                    <button type="button" @click="removeLeft(index)" class="text-rose">×</button>
                                </div>
                            </template>
                        </div>
                        <button type="button" @click="addLeft()" class="btn-secondary mt-2 w-full py-2 text-xs">+ Слева</button>
                    </div>
                    <div>
                        <p class="text-xs font-bold">Правая колонка</p>
                        <div class="mt-2 space-y-2">
                            <template x-for="(item, index) in right" :key="index">
                                <div class="flex gap-2">
                                    <input class="input py-2 text-xs" x-model="item.id" placeholder="ID" aria-label="ID правого элемента">
                                    <input class="input py-2 text-xs" x-model="item.label" placeholder="Текст" aria-label="Текст правого элемента">
                                    <button type="button" @click="removeRight(index)" class="text-rose">×</button>
                                </div>
                            </template>
                        </div>
                        <button type="button" @click="addRight()" class="btn-secondary mt-2 w-full py-2 text-xs">+ Справа</button>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-xs font-bold">Правильные пары</p>
                    <div class="mt-2 space-y-2">
                        <template x-for="(item, index) in left" :key="index">
                            <div class="grid grid-cols-[1fr_1fr] items-center gap-2">
                                <span class="text-xs" x-text="item.label || item.id"></span>
                                <select class="input py-2 text-xs" x-model="pairs[item.id]" aria-label="Правильная пара">
                                    <option value="">—</option>
                                    @foreach($config['right'] ?? [] as $right)
                                        <option value="{{ $right['id'] }}">{{ $right['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </template>
                    </div>
                    <p class="mt-2 text-[10px] text-muted">Если добавили новую правую колонку, сохраните и откройте редактор снова.</p>
                </div>
            </div>
        @endif

        @if($type === 'scale')
            <div class="card p-5">
                <p class="text-sm font-bold">Шкала</p>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <div>
                        <label class="label">Минимум</label>
                        <input type="number" class="input" x-model.number="min" aria-label="Минимум">
                    </div>
                    <div>
                        <label class="label">Максимум</label>
                        <input type="number" class="input" x-model.number="max" aria-label="Максимум">
                    </div>
                    <div>
                        <label class="label">Цель (если есть)</label>
                        <input type="number" class="input" x-model.number="scaleTarget" aria-label="Целевое значение">
                    </div>
                </div>
            </div>
        @endif

        <input type="hidden" name="config_json" value="{{ json_encode($config, JSON_UNESCAPED_UNICODE) }}" :value="configPayload">
        <input type="hidden" name="key_json" value="{{ json_encode($key, JSON_UNESCAPED_UNICODE) }}" :value="keyPayload">
    </div>
@endif

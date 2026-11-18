@php
    $columns = $config['columns'] ?? [];
    $defaults = collect($columns)->mapWithKeys(fn ($column): array => [$column['key'] => ''])->all();
@endphp

<div class="card p-5 sm:p-6"
     x-data
     x-init="$dispatch('response-patch', { milestones: { ...@js($defaults), ...(response.milestones || {}) } })">
    <div class="flex items-start gap-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-brand-soft text-brand-deep"><x-icon name="target" class="h-4 w-4" /></span>
        <div>
            <p class="text-sm font-bold">{{ $config['title'] ?? 'Маршрут профессии' }}</p>
            <p class="mt-1 max-w-lg text-xs leading-relaxed text-muted">{{ $config['prompt'] ?? 'Заполните этапы. Не идеальный план, а реалистичные ориентиры.' }}</p>
        </div>
    </div>

    <div class="relative mt-5 space-y-3 pl-6">
        <div class="absolute bottom-3 left-2 top-3 w-px bg-line"></div>
        @foreach($columns as $index => $column)
            <div class="relative">
                <span class="absolute -left-[1.35rem] top-4 grid h-4 w-4 place-items-center rounded-full border-2 border-white bg-brand text-[8px] font-extrabold text-white">{{ $index + 1 }}</span>
                <div class="rounded-2xl border border-line bg-surface p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs font-extrabold">{{ $column['label'] }}</p>
                        @if(! empty($column['tag']))
                            <span class="chip">{{ $column['tag'] }}</span>
                        @endif
                    </div>
                    @if(! empty($column['hint']))
                        <p class="mt-1 text-[10px] leading-relaxed text-muted">{{ $column['hint'] }}</p>
                    @endif
                    <textarea rows="2" class="textarea mt-2 min-h-16 text-xs" x-model="response.milestones['{{ $column['key'] }}']" placeholder="{{ $column['placeholder'] ?? 'Что важно на этом этапе?' }}"></textarea>
                </div>
            </div>
        @endforeach
    </div>
</div>

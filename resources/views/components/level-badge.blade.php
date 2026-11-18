@props(['level' => 'average', 'label' => null])

@php
    $info = \App\Support\Levels::info($level);
    $text = $label ?? $info['label'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold']) }}
      style="background: {{ $info['color'] }}14; color: {{ $info['color'] }};">
    <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $info['color'] }};"></span>
    {{ $text }}
</span>

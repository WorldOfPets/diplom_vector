@props([
    'path',
    'alt' => null,
    'credit' => 'caption',
    'class' => '',
    'imgClass' => 'h-full w-full object-cover',
    'loading' => 'lazy',
])

@php
    $photo = \App\Support\PhotoLibrary::get($path);
    $resolvedAlt = $alt ?? ($photo['alt'] ?? '');
    $authorUrl = $photo['author_url'] ?? 'https://unsplash.com';
    $sourceUrl = $photo['source_url'] ?? 'https://unsplash.com';
    $licenseUrl = $photo['license_url'] ?? 'https://unsplash.com/license';
@endphp

<figure class="relative overflow-hidden {{ $class }}">
    <img
        src="{{ asset($path) }}"
        alt="{{ $resolvedAlt }}"
        class="{{ $imgClass }}"
        loading="{{ $loading }}"
        decoding="async"
    >

    @if($credit === 'overlay' && $photo)
        <span class="absolute bottom-2 right-2 rounded-full bg-inverse/60 px-2 py-1 text-[9px] font-semibold text-white/80 backdrop-blur-sm"
              title="Фото: {{ $photo['author'] }} / {{ $photo['source'] }}">
            Фото: {{ $photo['author'] }} / {{ $photo['source'] }}
        </span>
    @elseif($credit === 'caption' && $photo)
        <figcaption class="flex flex-wrap items-center gap-1 px-3 py-2 text-[10px] font-semibold leading-relaxed text-muted">
            <span>Фото:</span>
            <a href="{{ $authorUrl }}" target="_blank" rel="noopener noreferrer" class="underline decoration-line underline-offset-2 hover:text-ink">{{ $photo['author'] }}</a>
            <span>/</span>
            <a href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer" class="underline decoration-line underline-offset-2 hover:text-ink">{{ $photo['source'] }}</a>
            <span>·</span>
            <a href="{{ $licenseUrl }}" target="_blank" rel="noopener noreferrer" class="underline decoration-line underline-offset-2 hover:text-ink">{{ $photo['license'] }}</a>
        </figcaption>
    @endif
</figure>

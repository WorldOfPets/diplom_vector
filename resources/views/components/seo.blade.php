@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'imageWidth' => null,
    'imageHeight' => null,
    'robots' => 'noindex,nofollow',
    'canonical' => null,
    'type' => 'website',
    'jsonLd' => null,
])

@php
    $siteName = 'Вектор';
    $pageTitle = filled($title) ? $title.' — '.$siteName : $siteName.' — тренажёр мотивации и эмоционального интеллекта';
    $pageDescription = $description ?: 'Вектор — цифровой тренажёр эмоционального интеллекта и мотивации для студентов СПО: диагностика, 43 интерактивных упражнения, теория и личная динамика.';
    $canonicalUrl = $canonical ?: url()->current();
    $imageUrl = $image ? (str_starts_with($image, 'http') ? $image : asset($image)) : asset('app-icons/icon-512.png');
    $nonce = request()->attributes->get('csp_nonce');
@endphp

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDescription }}">
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

<meta property="og:locale" content="ru_RU">
<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:image" content="{{ $imageUrl }}">
@if($imageWidth && $imageHeight)
<meta property="og:image:width" content="{{ $imageWidth }}">
<meta property="og:image:height" content="{{ $imageHeight }}">
@endif
<meta property="og:image:alt" content="{{ $siteName }} — тренажёр эмоционального интеллекта">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $pageDescription }}">
<meta name="twitter:image" content="{{ $imageUrl }}">

@if(is_array($jsonLd))
    <script type="application/ld+json" @if($nonce) nonce="{{ $nonce }}" @endif>
        {!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
@endif

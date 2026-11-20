@php
    $theme = \App\Support\Preferences::theme();
    $tone = \App\Support\Preferences::tone();
@endphp
<!DOCTYPE html>
<html lang="ru" class="h-full" data-theme="{{ $theme }}" data-tone="{{ $tone }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $theme === 'dark' ? '#0b0e14' : '#f7f5f0' }}">
    @php $pageTitle = trim($__env->yieldContent('title')); @endphp
    <x-seo
        :title="$pageTitle !== '' ? $pageTitle : null"
        :description="$__env->yieldContent('description') ?: null"
        :robots="$__env->yieldContent('robots') ?: 'index,follow'"
    />
    @include('partials.pwa-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-full overflow-x-hidden bg-paper">
<div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
    <div class="absolute -left-24 -top-24 h-80 w-80 rounded-full bg-brand/10 blur-3xl"></div>
    <div class="absolute -right-20 top-1/3 h-72 w-72 rounded-full bg-coral/10 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/3 h-64 w-64 rounded-full bg-mint/10 blur-3xl"></div>
    <div class="absolute inset-0 grain opacity-60"></div>
</div>

<div class="flex min-h-screen flex-col">
    <header class="flex items-center justify-between px-5 py-5 sm:px-8">
        <x-logo />
        <a href="{{ route('login') }}" class="text-xs font-bold text-muted transition hover:text-ink">Вход</a>
    </header>

    <main class="flex flex-1 items-center justify-center px-5 py-8 sm:px-8">
        <div class="w-full max-w-md">
            @if(session('status'))
                <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
            @endif

            @if($errors->any())
                <x-alert type="error" class="mb-4">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </x-alert>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="px-5 pb-6 text-center text-[11px] leading-relaxed text-muted sm:px-8">
        <p>Педагогический инструмент. Не является психологической диагностикой.</p>
        <p class="mt-1">© {{ date('Y') }} Вектор</p>
    </footer>
</div>

<x-preference-modal />

@stack('scripts')
</body>
</html>

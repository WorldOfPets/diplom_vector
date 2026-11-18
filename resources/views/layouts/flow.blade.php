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
    <title>@yield('title', 'Вектор') — Вектор</title>
    @include('partials.pwa-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-dvh bg-paper">
<div class="mx-auto flex min-h-dvh w-full max-w-4xl flex-col px-3 py-4 sm:px-6 sm:py-5">
    <header class="flex flex-wrap items-center justify-between gap-2">
        <x-logo />
        <div class="flex items-center gap-2">
            @yield('flow-header')
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-ghost px-3 text-xs">Выйти</button>
                </form>
            @endauth
        </div>
    </header>

    <main class="flex-1 py-5 sm:py-10">
        @if(session('status'))
            <x-alert type="success" class="mb-5">{{ session('status') }}</x-alert>
        @endif

        @if($errors->any())
            <x-alert type="error" class="mb-5">
                <ul class="list-disc pl-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>

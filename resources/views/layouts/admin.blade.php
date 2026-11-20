@php
    $admin = auth()->user();
    $adminNav = [
        ['route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'label' => 'Обзор', 'icon' => 'home'],
        ['route' => 'admin.users.index', 'pattern' => 'admin.users.*', 'label' => 'Пользователи', 'icon' => 'user'],
        ['route' => 'admin.groups.index', 'pattern' => 'admin.groups.*', 'label' => 'Группы', 'icon' => 'grid'],
        ['route' => 'admin.tests.index', 'pattern' => 'admin.tests.*', 'label' => 'Тесты', 'icon' => 'chart'],
        ['route' => 'admin.trainers.index', 'pattern' => 'admin.trainers.*', 'label' => 'Тренажёры', 'icon' => 'sparkles'],
        ['route' => 'admin.theory.index', 'pattern' => 'admin.theory.*', 'label' => 'Теория', 'icon' => 'book'],
        ['route' => 'admin.analytics.index', 'pattern' => 'admin.analytics.*', 'label' => 'Аналитика', 'icon' => 'target'],
        ['route' => 'admin.feedback.index', 'pattern' => 'admin.feedback.*', 'label' => 'Качество', 'icon' => 'star'],
        ['route' => 'admin.audit.index', 'pattern' => 'admin.audit.*', 'label' => 'Аудит', 'icon' => 'shield'],
    ];
@endphp
<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#101828">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" type="image/png" href="{{ asset('favicon-32.png') }}">
    <meta name="description" content="Админ-панель тренажёра «Вектор»: пользователи, контент, аналитика и аудит.">
    <title>@yield('title', 'Админка') — Вектор</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-full bg-paper">
<aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col bg-ink px-4 py-5 text-white lg:flex">
    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-2">
        <span class="grid h-9 w-9 place-items-center rounded-2xl bg-brand text-sm font-extrabold">V</span>
        <span>
            <span class="block text-[15px] font-extrabold tracking-tight">Вектор</span>
            <span class="block text-[10px] font-bold uppercase tracking-[0.16em] text-white/70">админка</span>
        </span>
    </a>

    <nav class="mt-8 space-y-1" aria-label="Админ-навигация">
        @foreach($adminNav as $item)
            @php $active = request()->routeIs($item['pattern']); @endphp
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-3 rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition {{ $active ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                <x-icon :name="$item['icon']" class="h-[18px] w-[18px] {{ $active ? 'text-brand' : 'text-white/35' }}" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="mt-auto space-y-2">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-2xl px-3.5 py-2.5 text-sm font-semibold text-white/70 transition hover:bg-white/5 hover:text-white">
            <x-icon name="arrow-left" class="h-[18px] w-[18px] text-white/35" />
            В приложение
        </a>
        <div class="flex items-center gap-3 rounded-2xl bg-white/5 p-3">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand text-xs font-extrabold">{{ $admin?->initials() }}</span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-bold">{{ $admin?->name }}</p>
                <p class="truncate text-[10px] text-white/70">{{ $admin?->role?->label() }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="grid h-7 w-7 place-items-center rounded-lg text-white/70 hover:bg-white/10 hover:text-white" title="Выйти">
                    <x-icon name="logout" class="h-3.5 w-3.5" />
                </button>
            </form>
        </div>
    </div>
</aside>

<header class="sticky top-0 z-30 border-b border-line/70 bg-ink px-4 py-3 text-white lg:hidden">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            <span class="grid h-8 w-8 place-items-center rounded-xl bg-brand text-xs font-extrabold">V</span>
            <span class="text-sm font-extrabold">Админка</span>
        </a>
        <a href="{{ route('dashboard') }}" class="py-1 text-xs font-bold text-white/60">В приложение</a>
    </div>
    <nav class="no-scrollbar mt-3 flex gap-1.5 overflow-x-auto pb-1">
        @foreach($adminNav as $item)
            @php $active = request()->routeIs($item['pattern']); @endphp
            <a href="{{ route($item['route']) }}" class="shrink-0 rounded-full px-3.5 py-2.5 text-[11px] font-bold {{ $active ? 'bg-white text-ink' : 'bg-white/10 text-white/60' }}">{{ $item['label'] }}</a>
        @endforeach
    </nav>
</header>

<main class="lg:pl-64">
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
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
    </div>
</main>
@stack('scripts')
</body>
</html>

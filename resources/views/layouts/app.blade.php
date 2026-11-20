@php
    $user = auth()->user();
    $theme = \App\Support\Preferences::theme($user);
    $tone = \App\Support\Preferences::tone($user);
    $nav = [
        ['route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Главная', 'icon' => 'home'],
        ['route' => 'progress.index', 'pattern' => 'progress.*', 'label' => 'Прогресс', 'icon' => 'target'],
        ['route' => 'modules.index', 'pattern' => 'modules.*', 'label' => 'Модули', 'icon' => 'grid'],
        ['route' => 'shorts.index', 'pattern' => 'shorts.*', 'label' => 'Шортсы', 'icon' => 'play'],
        ['route' => 'tests.index', 'pattern' => 'tests.*', 'label' => 'Диагностика', 'icon' => 'chart'],
        ['route' => 'diary.index', 'pattern' => 'diary.*', 'label' => 'Дневник', 'icon' => 'calendar'],
        ['route' => 'theory.index', 'pattern' => 'theory.*', 'label' => 'Теория', 'icon' => 'book'],
        ['route' => 'camera.show', 'pattern' => 'camera.*', 'label' => 'Мимика', 'icon' => 'camera'],
        ['route' => 'profile.index', 'pattern' => 'profile.*', 'label' => 'Профиль', 'icon' => 'user'],
    ];
    $mobilePrimary = [$nav[0], $nav[2], $nav[3], $nav[5]];
    $mobileMore = [$nav[1], $nav[4], $nav[6], $nav[7], $nav[8]];
    $moreActive = request()->routeIs('progress.*', 'tests.*', 'theory.*', 'camera.*', 'profile.*', 'credits');
@endphp
<!DOCTYPE html>
<html lang="ru" class="h-full" data-theme="{{ $theme }}" data-tone="{{ $tone }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $theme === 'dark' ? '#0b0e14' : '#f7f5f0' }}">
    @php $pageTitle = trim($__env->yieldContent('title')); @endphp
    <x-seo :title="$pageTitle !== '' ? $pageTitle : null" robots="noindex,nofollow" />
    @include('partials.pwa-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-dvh bg-paper">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-full focus:bg-inverse focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
    К содержанию
</a>

<aside class="fixed inset-y-0 left-0 z-40 hidden w-72 flex-col border-r border-line/80 bg-surface/70 px-5 py-6 backdrop-blur-xl lg:flex">
    <div class="flex items-center justify-between">
        <x-logo />
    </div>

    <p class="mt-9 px-3 text-[10px] font-bold uppercase tracking-[0.2em] text-muted">Пространство</p>
    <nav class="mt-3 space-y-1" aria-label="Основная навигация">
        @foreach($nav as $item)
            @php $active = request()->routeIs($item['pattern']); @endphp
            <a href="{{ route($item['route']) }}"
               class="group flex items-center gap-3 rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition {{ $active ? 'bg-inverse text-white shadow-sm' : 'text-ink-soft hover:bg-paper-deep/60 hover:text-ink' }}">
                <x-icon :name="$item['icon']" class="h-[18px] w-[18px] {{ $active ? 'text-white' : 'text-muted group-hover:text-ink' }}" />
                <span>{{ $item['label'] }}</span>
                @if($active)
                    <span class="ml-auto h-1.5 w-1.5 rounded-full bg-white/80"></span>
                @endif
            </a>
        @endforeach

        @if($user?->isAdmin())
            <div class="my-3 border-t border-line/70"></div>
            <a href="{{ route('admin.dashboard') }}"
               class="group flex items-center gap-3 rounded-2xl px-3.5 py-2.5 text-sm font-semibold {{ request()->routeIs('admin.*') ? 'bg-inverse text-white' : 'text-ink-soft hover:bg-paper-deep/60 hover:text-ink' }}">
                <x-icon name="shield" class="h-[18px] w-[18px] {{ request()->routeIs('admin.*') ? 'text-white' : 'text-muted' }}" />
                <span>Админка</span>
            </a>
        @endif
    </nav>

    <div class="mt-auto space-y-3">
        <x-install-app />

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-3xl border border-line/80 bg-paper/70 px-3.5 py-3">
            <a href="{{ route('credits') }}" class="text-[10px] font-bold text-muted underline decoration-line underline-offset-2 hover:text-ink">Фото и лицензии →</a>
            <a href="{{ route('dashboard') }}#guide" class="text-[10px] font-bold text-muted underline decoration-line underline-offset-2 hover:text-ink">Как пользоваться →</a>
        </div>

        <div class="flex items-center gap-3 rounded-3xl border border-line/80 bg-surface p-3">
            @if($user?->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="" class="h-10 w-10 rounded-2xl object-cover" referrerpolicy="no-referrer">
            @else
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-brand-soft text-sm font-extrabold text-brand-deep">{{ $user?->initials() }}</span>
            @endif
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-bold text-ink">{{ $user?->name }}</p>
                <p class="truncate text-[11px] font-semibold text-muted">{{ $user?->role?->label() }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="grid h-8 w-8 place-items-center rounded-xl text-muted transition hover:bg-paper hover:text-ink" title="Выйти">
                    <x-icon name="logout" class="h-4 w-4" />
                </button>
            </form>
        </div>
    </div>
</aside>

<header class="sticky top-0 z-30 flex items-center justify-between border-b border-line/70 bg-paper/90 px-4 pt-[max(0.75rem,env(safe-area-inset-top))] pb-3 backdrop-blur-xl lg:hidden">
    <x-logo />
    <div class="flex items-center gap-2">
        @if($user?->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="grid h-9 w-9 place-items-center rounded-2xl border border-line bg-surface text-ink-soft" title="Админка">
                <x-icon name="shield" class="h-4 w-4" />
            </a>
        @endif
        <a href="{{ route('profile.index') }}" class="flex items-center gap-2 rounded-2xl border border-line bg-surface p-1 pr-2.5">
            @if($user?->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="" class="h-7 w-7 rounded-xl object-cover" referrerpolicy="no-referrer">
            @else
                <span class="grid h-7 w-7 place-items-center rounded-xl bg-brand-soft text-[11px] font-extrabold text-brand-deep">{{ $user?->initials() }}</span>
            @endif
            <span class="max-w-24 truncate text-[11px] font-bold text-ink">{{ $user?->name }}</span>
        </a>
    </div>
</header>

<main id="main" class="lg:pl-72">
    <div class="mx-auto w-full min-w-0 max-w-6xl px-3 pb-32 pt-4 sm:px-6 lg:px-10 lg:pb-12 lg:pt-10">
        @if(session('status'))
            <x-alert type="success" class="mb-5">{{ session('status') }}</x-alert>
        @endif

        @if($errors->any())
            <x-alert type="error" class="mb-5">
                <span class="font-semibold">Проверьте форму:</span>
                <ul class="mt-1 list-disc pl-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        @yield('content')
    </div>
</main>

<div x-data="{ more: false, toggleMore(open) { this.more = open; if (this.$refs.moreSheet) { this.$refs.moreSheet.style.display = open ? 'block' : 'none'; } } }">
<nav data-mobile-nav class="fixed inset-x-0 bottom-0 z-40 border-t border-line/80 bg-surface px-2 pt-1.5 pb-[max(0.25rem,env(safe-area-inset-bottom))] lg:hidden" aria-label="Мобильная навигация">
    <div class="mx-auto grid max-w-md grid-cols-5">
        @foreach($mobilePrimary as $item)
            @php $active = request()->routeIs($item['pattern']); @endphp
            <a href="{{ route($item['route']) }}" class="flex min-h-14 flex-col items-center justify-center gap-0.5 rounded-2xl px-1 py-1.5 text-[10px] font-bold {{ $active ? 'text-brand' : 'text-muted' }}">
                <span class="grid h-8 w-8 place-items-center rounded-xl {{ $active ? 'bg-brand-soft' : '' }}">
                    <x-icon :name="$item['icon']" class="h-[18px] w-[18px]" />
                </span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        <button type="button" @click="toggleMore(true)" :aria-expanded="more.toString()" class="flex min-h-14 flex-col items-center justify-center gap-0.5 rounded-2xl px-1 py-1.5 text-[10px] font-bold {{ $moreActive ? 'text-brand' : 'text-muted' }}">
            <span class="grid h-8 w-8 place-items-center rounded-xl {{ $moreActive ? 'bg-brand-soft' : '' }}">
                <x-icon name="grid" class="h-[18px] w-[18px]" />
            </span>
            <span>Ещё</span>
        </button>
    </div>
</nav>

<div x-ref="moreSheet" class="mobile-more-sheet fixed inset-0 z-50 lg:hidden" style="display: none;" @keydown.escape.window="toggleMore(false)">
    <div class="absolute inset-0 bg-inverse/60 backdrop-blur-sm" @click="toggleMore(false)"></div>
    <div class="absolute inset-x-0 bottom-0 rounded-t-[2rem] border-t border-line bg-surface p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-2xl" @click.stop>
        <div class="mx-auto h-1.5 w-10 rounded-full bg-line"></div>
        <div class="mt-4 flex items-center justify-between">
            <p class="text-lg font-extrabold">Ещё</p>
            <button type="button" class="btn-ghost px-3 py-1.5 text-xs" @click="toggleMore(false)">Закрыть</button>
        </div>
        <div class="mt-3">
            <x-install-app variant="sheet" />
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2">
            @foreach($mobileMore as $item)
                @php $active = request()->routeIs($item['pattern']); @endphp
                <a href="{{ route($item['route']) }}" class="flex min-h-16 items-center gap-3 rounded-2xl border p-3 transition {{ $active ? 'border-brand/25 bg-brand-soft/60 text-brand-deep' : 'border-line bg-surface text-ink-soft' }}">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $active ? 'bg-surface/70 text-brand' : 'bg-paper text-muted' }}">
                        <x-icon :name="$item['icon']" class="h-4 w-4" />
                    </span>
                    <span class="text-xs font-bold">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>

        <a href="{{ route('credits') }}" class="mt-3 flex min-h-12 items-center justify-between rounded-2xl bg-paper px-4 py-3 text-xs font-bold text-ink-soft">
            <span>Фото и лицензии</span>
            <x-icon name="chevron-right" class="h-4 w-4 text-muted" />
        </a>

        <a href="{{ route('dashboard') }}#guide" class="mt-2 flex min-h-12 items-center justify-between rounded-2xl bg-paper px-4 py-3 text-xs font-bold text-ink-soft">
            <span>Как пользоваться тренажёром</span>
            <x-icon name="chevron-right" class="h-4 w-4 text-muted" />
        </a>

        <div class="mt-3 flex items-center gap-3 rounded-2xl bg-paper p-3">
            @if($user?->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="" class="h-9 w-9 rounded-xl object-cover" referrerpolicy="no-referrer">
            @else
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-soft text-xs font-extrabold text-brand-deep">{{ $user?->initials() }}</span>
            @endif
            <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-bold text-ink">{{ $user?->name }}</p>
                <p class="truncate text-[10px] font-semibold text-muted">{{ $user?->role?->label() }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="grid h-9 w-9 place-items-center rounded-xl text-muted transition hover:bg-surface hover:text-ink" title="Выйти">
                    <x-icon name="logout" class="h-4 w-4" />
                </button>
            </form>
        </div>
    </div>
</div>
</div>

<x-preference-modal />

@stack('scripts')
</body>
</html>

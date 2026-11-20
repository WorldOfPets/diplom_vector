@extends('layouts.guest')

@section('title', 'Вход')
@section('robots', 'noindex,follow')

@section('content')
    <div class="card overflow-hidden">
        <div class="relative h-36">
            <x-photo path="{{ \App\Support\SlideImages::title('what-is-ei') }}" credit="none" class="h-full" img-class="h-full w-full object-cover" loading="eager" />
            <div class="absolute inset-0 bg-gradient-to-t from-inverse/70 to-transparent"></div>
            <p class="absolute bottom-3 left-5 right-5 text-[10px] font-bold uppercase tracking-[0.16em] text-white/70">эмоции · мотивация · практика</p>
        </div>
        <div class="border-b border-line/70 bg-inverse px-6 py-7 text-white">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">Личный кабинет</p>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight">Вход в «Вектор»</h1>
            <p class="mt-2 text-sm leading-relaxed text-white/70">Один вход — через Яндекс ID. Пароль не нужен.</p>
        </div>

        <div class="space-y-5 px-6 py-7">
            <a href="{{ route('auth.yandex') }}"
               class="flex w-full items-center justify-center gap-3 rounded-2xl border border-line bg-surface px-5 py-3.5 text-sm font-bold text-ink transition hover:border-ink/20 hover:bg-paper">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#FC3F1D] text-[15px] font-extrabold text-white">Я</span>
                Войти с Яндексом
            </a>

            <div class="flex items-start gap-2.5 rounded-2xl bg-paper px-4 py-3 text-xs leading-relaxed text-muted">
                <x-icon name="shield" class="mt-0.5 h-4 w-4 shrink-0 text-mint" />
                <p>Мы получаем только имя, email и аватар. Данные используются для обучения и статистики — без передачи третьим лицам.</p>
            </div>

            <div class="text-center text-xs leading-relaxed text-muted">
                <p>Продолжая, вы соглашаетесь с <a href="{{ route('privacy') }}" class="font-bold underline decoration-line underline-offset-4">обработкой персональных данных</a>.</p>
                <p class="mt-1">Это педагогический инструмент, а не психологическая диагностика.</p>
            </div>

            @if(app()->isLocal() && config('app.dev_login'))
                <div class="border-t border-dashed border-line pt-4 text-center">
                    <a href="{{ route('dev.login') }}" class="text-xs font-bold text-muted underline decoration-line underline-offset-4 hover:text-ink">
                        Локальный вход для разработки
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection

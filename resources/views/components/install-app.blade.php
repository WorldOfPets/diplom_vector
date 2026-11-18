@props(['variant' => 'default'])

<div
    x-data="pwaInstall"
    x-init="init()"
    x-show="visible"
    x-cloak
    @keydown.escape.window="closeHelp()"
>
    @if($variant === 'header')
        <button
            type="button"
            @click="install"
            class="grid h-10 w-10 place-items-center rounded-2xl border border-line bg-surface text-ink-soft transition hover:text-ink"
            title="Установить приложение"
            aria-label="Установить приложение"
        >
            <x-icon name="download" class="h-4 w-4" />
        </button>
    @elseif($variant === 'sheet')
        <button
            type="button"
            @click="install"
            class="flex min-h-12 w-full items-center justify-between rounded-2xl border border-brand/20 bg-brand-soft/60 px-4 py-3 text-left text-xs font-bold text-brand-deep"
        >
            <span class="flex items-center gap-3">
                <x-icon name="download" class="h-4 w-4" />
                Установить приложение
            </span>
            <span class="text-[10px] font-semibold text-brand-deep/70">PWA</span>
        </button>
    @else
        <button
            type="button"
            @click="install"
            class="btn-secondary w-full justify-start px-3.5 py-2.5 text-xs"
        >
            <x-icon name="download" class="h-4 w-4 text-brand" />
            <span>Установить приложение</span>
        </button>
    @endif

    <div
        x-show="helpOpen"
        x-transition.opacity
        class="fixed inset-0 z-[70] flex items-end justify-center bg-inverse/60 p-4 backdrop-blur-sm sm:items-center"
        role="dialog"
        aria-modal="true"
        aria-label="Как установить приложение"
        @click.self="closeHelp()"
    >
        <div class="w-full max-w-md rounded-[2rem] border border-line bg-surface p-5 shadow-2xl sm:p-6" @click.stop>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-muted">PWA</p>
                    <h2 class="mt-1 text-xl font-extrabold">Установить «Вектор»</h2>
                </div>
                <button type="button" @click="closeHelp()" class="grid h-8 w-8 place-items-center rounded-xl text-muted transition hover:bg-paper hover:text-ink" aria-label="Закрыть">
                    <x-icon name="chevron-down" class="h-4 w-4" />
                </button>
            </div>

            <p class="mt-2 text-xs leading-relaxed text-muted">
                Приложение откроется с домашнего экрана и будет работать как обычное приложение — без адресной строки.
            </p>

            <div x-show="isIos" class="mt-4 rounded-2xl border border-line/70 bg-paper/60 p-4 text-xs leading-relaxed text-ink-soft">
                <p class="font-extrabold">iPhone / iPad</p>
                <p class="mt-1">Нажмите «Поделиться» <span class="font-bold">⎋</span> в Safari и выберите «На экран „Домой“».</p>
            </div>

            <div x-show="!isIos" class="mt-4 space-y-3 text-xs leading-relaxed text-ink-soft">
                <div class="rounded-2xl border border-line/70 bg-paper/60 p-4">
                    <p class="font-extrabold">Android / Chrome</p>
                    <p class="mt-1">Откройте меню ⋮ и выберите «Установить приложение» или «Добавить на главный экран».</p>
                </div>
                <div class="rounded-2xl border border-line/70 bg-paper/60 p-4">
                    <p class="font-extrabold">Компьютер</p>
                    <p class="mt-1">Нажмите иконку установки в адресной строке Chrome / Edge или найдите «Установить приложение» в меню браузера.</p>
                </div>
            </div>

            <p class="mt-3 text-[10px] leading-relaxed text-muted">
                Установка доступна на HTTPS. Если браузер не показывает предложение, приложение всё равно можно добавить через его меню.
            </p>

            <button type="button" @click="closeHelp()" class="btn-primary mt-4 w-full py-2.5 text-xs">Понятно</button>
        </div>
    </div>
</div>

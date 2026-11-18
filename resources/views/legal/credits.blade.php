@php
    $photos = \App\Support\PhotoLibrary::credits();
    $theme = \App\Support\Preferences::theme();
    $tone = \App\Support\Preferences::tone();
@endphp
<!DOCTYPE html>
<html lang="ru" class="h-full scroll-smooth" data-theme="{{ $theme }}" data-tone="{{ $tone }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $theme === 'dark' ? '#0b0e14' : '#f7f5f0' }}">
    <title>Фотографии и лицензии — Вектор</title>
    <meta name="description" content="Авторы и источники фотографий, использованных в тренажёре «Вектор». Лицензия Unsplash.">
    @include('partials.pwa-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-paper">
<header class="sticky top-0 z-40 border-b border-line/60 bg-paper/85 backdrop-blur-xl">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-5 py-3.5 sm:px-8">
        <x-logo />
        <div class="flex items-center gap-2">
            <a href="{{ url('/') }}" class="btn-secondary px-4 py-2.5 text-xs">На главную</a>
        </div>
    </div>
</header>

<main class="mx-auto max-w-6xl px-5 py-10 sm:px-8 sm:py-14">
    <p class="section-eyebrow">Правовая информация</p>
    <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Фотографии и лицензии</h1>
    <p class="mt-3 max-w-3xl text-sm leading-relaxed text-muted">
        В оформлении используются фотографии с <a href="https://unsplash.com" target="_blank" rel="noopener noreferrer" class="font-bold text-brand underline decoration-line underline-offset-4">Unsplash</a>.
        Они распространяются по <a href="https://unsplash.com/license" target="_blank" rel="noopener noreferrer" class="font-bold text-brand underline decoration-line underline-offset-4">Unsplash License</a>:
        бесплатно для коммерческого и некоммерческого использования, без обязательной атрибуции.
        Мы всё равно указываем автора и ссылку на оригинал — это честно по отношению к фотографам.
    </p>

    <div class="mt-5 grid gap-3 sm:grid-cols-2">
        <div class="rounded-3xl border border-line/70 bg-surface/60 p-5">
            <p class="text-sm font-extrabold">Что важно знать</p>
            <ul class="mt-2 space-y-1.5 text-xs leading-relaxed text-ink-soft">
                <li>• Фотографии — иллюстрации, а не диагностические стимулы и не участники исследования.</li>
                <li>• Люди на фото — модели из фотобанка. Мы не утверждаем, что они переживают описанные эмоции.</li>
                <li>• Unsplash не гарантирует наличие model release; поэтому снимки используются только как иллюстрации, без диагностических или оценочных утверждений о людях на них.</li>
                <li>• Мы не используем изображения Unsplash+ и не продаём фотографии отдельно.</li>
                <li>• Мини-видео в разделе «Шортсы» собраны из этих фотографий, текста и монтажа продукта.</li>
                <li>• Если правообладатель хочет убрать снимок — напишите администратору, мы заменим его.</li>
            </ul>
        </div>
        <div class="rounded-3xl border border-line/70 bg-surface/60 p-5">
            <p class="text-sm font-extrabold">Как читать подписи</p>
            <p class="mt-2 text-xs leading-relaxed text-ink-soft">
                В карточках и на страницах материалов подпись «Фото: Автор / Unsplash» ведёт на профиль автора и оригинальную публикацию.
                Ниже — полный список фотографий библиотеки.
            </p>
            <p class="mt-3 text-xs font-semibold text-muted">Всего фотографий: {{ count($photos) }}</p>
        </div>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($photos as $photo)
            <figure class="card overflow-hidden">
                <div class="aspect-[16/10] bg-paper-deep">
                    <img src="{{ asset($photo['path']) }}" alt="{{ $photo['alt'] }}" class="h-full w-full object-cover" loading="lazy" decoding="async">
                </div>
                <figcaption class="p-4">
                    <p class="text-xs font-extrabold leading-snug">{{ $photo['alt'] ?: 'Фотография Unsplash' }}</p>
                    <p class="mt-2 text-[11px] leading-relaxed text-muted">
                        Автор:
                        <a href="{{ $photo['source_url'] }}" target="_blank" rel="noopener noreferrer" class="font-bold text-ink-soft underline decoration-line underline-offset-2 hover:text-brand">{{ $photo['author'] }}</a>
                        · <a href="{{ $photo['source_url'] }}" target="_blank" rel="noopener noreferrer" class="underline decoration-line underline-offset-2 hover:text-brand">{{ $photo['source'] }}</a>
                    </p>
                    <p class="mt-1 text-[10px] font-semibold text-muted">
                        <a href="{{ $photo['license_url'] }}" target="_blank" rel="noopener noreferrer" class="underline decoration-line underline-offset-2 hover:text-brand">{{ $photo['license'] }}</a>
                    </p>
                </figcaption>
            </figure>
        @endforeach
    </div>

    <div class="mt-8 rounded-3xl border border-line/60 bg-surface/50 p-5">
        <p class="text-sm font-extrabold">Другие материалы</p>
        <p class="mt-2 text-xs leading-relaxed text-ink-soft">
            Иллюстрации к упражнениям, иконки и схемы созданы для продукта. Внешние статьи и видео открываются по ссылкам на первоисточники.
            Подробнее о данных — в <a href="{{ route('privacy') }}" class="font-bold text-brand underline decoration-line underline-offset-4">политике обработки персональных данных</a>.
        </p>
    </div>
</main>

<footer class="border-t border-line/60 bg-surface/40">
    <div class="mx-auto flex max-w-6xl flex-col gap-3 px-5 py-7 text-xs text-muted sm:flex-row sm:items-center sm:justify-between sm:px-8">
        <span>© {{ date('Y') }} Вектор. Педагогический инструмент.</span>
        <div class="flex flex-wrap gap-4">
            <a href="{{ url('/') }}" class="hover:text-ink">Главная</a>
            <a href="{{ route('privacy') }}" class="hover:text-ink">Персональные данные</a>
            <span>Не является психологической диагностикой</span>
        </div>
    </div>
</footer>

<x-preference-modal />
</body>
</html>

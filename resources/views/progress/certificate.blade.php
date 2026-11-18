@extends('layouts.app')

@section('title', 'Сертификат')

@push('head')
    <style>
        @media print {
            aside,
            header,
            nav,
            .no-print {
                display: none !important;
            }

            main {
                padding: 0 !important;
            }

            main > div {
                max-width: none !important;
                padding: 0 !important;
            }

            body {
                background: #fff !important;
            }

            .certificate-sheet {
                box-shadow: none !important;
                border: 0 !important;
                break-inside: avoid;
            }

            @page {
                size: A4 landscape;
                margin: 10mm;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $certificateCategories = $certificate->meta['categories'] ?? $summary['categories'];
        $certificateDone = $certificate->meta['done'] ?? $summary['done'];
        $certificateTotal = $certificate->meta['total'] ?? $summary['total'];
    @endphp
    <div class="mx-auto max-w-5xl">
        <div class="no-print flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <p class="section-eyebrow">Документ</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Сертификат</h1>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted">Номер {{ $certificate->number }}. Можно распечатать или сохранить в PDF через диалог печати браузера.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('progress.index') }}" class="btn-secondary px-4 py-2.5 text-xs">К прогрессу</a>
                <button type="button" onclick="window.print()" class="btn-primary px-4 py-2.5 text-xs">Печать / PDF</button>
            </div>
        </div>

        <div class="certificate-sheet force-light mt-6 overflow-hidden rounded-[2rem] border-2 border-brand/20 bg-white shadow-soft">
            <div class="relative p-6 sm:p-10">
                <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-brand/10"></div>
                <div class="pointer-events-none absolute -bottom-24 -left-16 h-56 w-56 rounded-full bg-coral/10"></div>

                <div class="relative flex items-center justify-between gap-4">
                    <x-logo />
                    <span class="rounded-full border border-brand/20 bg-brand-soft px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.16em] text-brand-deep">Сертификат</span>
                </div>

                <div class="relative mt-10 text-center">
                    <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-muted">Цифровой тренажёр «Вектор»</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Сертификат о прохождении</h2>
                    <p class="mt-3 text-sm text-muted">подтверждает, что</p>
                    <p class="mt-3 text-2xl font-extrabold text-brand sm:text-3xl">{{ $certificate->user->name }}</p>
                    <p class="mt-2 text-sm text-muted">
                        {{ $certificate->user->group?->name ?? 'студент' }}{{ $certificate->user->course ? ' · '.$certificate->user->course.' курс' : '' }}
                    </p>

                    <p class="mx-auto mt-6 max-w-2xl text-sm leading-relaxed text-ink-soft">
                        прошёл(ла) цифровой тренажёр мотивации и эмоционального интеллекта и освоил(а)
                        <strong class="text-ink">{{ $certificateDone }} из {{ $certificateTotal }}</strong> единиц контента —
                        <strong class="text-ink">{{ number_format((float) $certificate->percent, 1, ',', ' ') }}%</strong> программы.
                    </p>

                    <div class="mx-auto mt-7 grid max-w-2xl grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach($certificateCategories as $category)
                            <div class="rounded-2xl border border-line/70 bg-paper/50 px-3 py-3">
                                <p class="text-lg font-extrabold" style="color: {{ $category['color'] }};">{{ $category['done'] }}/{{ $category['total'] }}</p>
                                <p class="mt-0.5 text-[10px] font-bold uppercase tracking-[0.1em] text-muted">{{ $category['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="relative mt-10 grid gap-4 text-xs sm:grid-cols-3 sm:items-end">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted">Номер</p>
                        <p class="mt-1 font-extrabold text-ink">{{ $certificate->number }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted">Дата</p>
                        <p class="mt-1 font-extrabold text-ink">{{ $certificate->issued_at?->translatedFormat('j F Y') }}</p>
                    </div>
                    <div class="sm:text-right">
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-muted">Подпись</p>
                        <p class="mt-5 border-t border-ink/30 pt-1 font-semibold text-ink-soft">Куратор / администратор</p>
                    </div>
                </div>

                <p class="relative mt-8 text-[9px] leading-relaxed text-muted">
                    Документ подтверждает прохождение педагогического тренажёра и не является психологической диагностикой, медицинским заключением или официальным документом об образовании.
                </p>
            </div>
        </div>

        <div class="no-print mt-4 rounded-3xl border border-line/60 bg-surface/50 p-4 text-xs leading-relaxed text-muted">
            Сертификат выдан после достижения порога {{ (int) $summary['certificate_threshold'] }}% и заполнения формы обратной связи. Номер можно проверить у администратора продукта.
        </div>
    </div>
@endsection

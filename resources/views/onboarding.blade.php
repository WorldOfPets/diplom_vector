@extends('layouts.flow')

@section('title', 'Знакомство')

@section('flow-header')
    <span class="chip">Знакомство</span>
@endsection

@section('content')
    <div x-data="{ step: {{ $errors->has('consent') ? 2 : 1 }} }" class="mx-auto max-w-2xl">
        <div class="relative mb-7 h-40 overflow-hidden rounded-3xl border border-line/70">
            <x-photo path="media/photos/campus-arch.jpg" credit="none" class="h-full" img-class="h-full w-full object-cover" loading="eager" />
            <div class="absolute inset-0 bg-gradient-to-t from-inverse/75 via-inverse/10 to-transparent"></div>
            <p class="absolute bottom-4 left-5 right-5 text-sm font-extrabold text-white">Три минуты на знакомство — и можно начинать.</p>
        </div>
        <div class="mb-7">
            <p class="section-eyebrow">Перед началом</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Давайте познакомимся</h1>
            <p class="mt-3 max-w-xl text-sm leading-relaxed text-muted">
                Это нужно, чтобы показывать ваш прогресс и сравнивать результаты по группе — без публичных рейтингов.
            </p>
        </div>

        <div class="mb-6 flex items-center gap-2">
            <span class="h-1.5 flex-1 rounded-full bg-brand transition-all" :class="step >= 1 ? 'bg-brand' : 'bg-line'"></span>
            <span class="h-1.5 flex-1 rounded-full transition-all" :class="step >= 2 ? 'bg-brand' : 'bg-line'"></span>
        </div>

        <form method="POST" action="{{ route('onboarding.store') }}" class="card p-5 sm:p-7">
            @csrf

            <div x-show="step === 1" x-cloak class="space-y-5">
                <div class="flex items-center gap-3 rounded-2xl bg-paper px-4 py-3">
                    @if($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="" class="h-10 w-10 rounded-2xl object-cover" referrerpolicy="no-referrer">
                    @else
                        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-brand-soft text-sm font-extrabold text-brand-deep">{{ $user->initials() }}</span>
                    @endif
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold">{{ $user->name }}</p>
                        <p class="truncate text-xs text-muted">{{ $user->email }}</p>
                    </div>
                </div>

                <div>
                    <label class="label" for="name">Как к вам обращаться</label>
                    <input class="input" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="group_id">Учебная группа <span class="normal-case tracking-normal text-muted">— необязательно</span></label>
                        <select class="input" id="group_id" name="group_id">
                            <option value="">Выберите группу</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" @selected(old('group_id') == $group->id)>
                                    {{ $group->name }}{{ $group->specialty ? ' — '.$group->specialty : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if($groups->isEmpty())
                            <p class="mt-2 text-xs text-coral">Групп пока нет. Попросите администратора добавить вашу группу.</p>
                        @endif
                    </div>

                    <div>
                        <label class="label" for="course">Курс <span class="normal-case tracking-normal text-muted">— необязательно</span></label>
                        <select class="input" id="course" name="course">
                            <option value="">Выберите курс</option>
                            @for($course = 1; $course <= 4; $course++)
                                <option value="{{ $course }}" @selected(old('course') == $course)>{{ $course }} курс</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div>
                    <label class="label" for="specialty">Специальность <span class="normal-case tracking-normal text-muted">— необязательно</span></label>
                    <input class="input" id="specialty" name="specialty" value="{{ old('specialty', $user->specialty) }}" placeholder="Например, 09.02.07 Информационные системы и программирование">
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" class="btn-primary" @click="step = 2">Дальше <x-icon name="chevron-right" class="h-4 w-4" /></button>
                </div>
            </div>

            <div x-show="step === 2" x-cloak class="space-y-5">
                <div class="rounded-2xl border border-line bg-paper/60 p-4">
                    <p class="text-sm font-bold text-ink">Что мы сохраняем</p>
                    <ul class="mt-2 space-y-1.5 text-xs leading-relaxed text-ink-soft">
                        <li>• Имя, email и аватар из Яндекс ID.</li>
                        <li>• Группу, курс и специальность.</li>
                        <li>• Ответы на задания, результаты тренажёров и записи дневника.</li>
                        <li>• Для камеры — только итоговую уверенность. Видео и кадры не загружаются.</li>
                    </ul>
                </div>

                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-line bg-surface p-4 transition hover:border-brand/30">
                    <input type="checkbox" name="consent" value="1" class="mt-1 h-4 w-4 rounded border-line text-brand focus:ring-brand" required :required="step === 2">
                    <span class="text-sm leading-relaxed text-ink-soft">
                        Я согласен на <a href="{{ route('privacy') }}" target="_blank" class="font-bold underline decoration-line underline-offset-4">обработку персональных данных</a> и использование результатов в обезличенном виде для развития продукта.
                        <span class="mt-1 block text-xs text-muted">Согласие можно отозвать, удалив аккаунт.</span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-line bg-surface p-4 transition hover:border-brand/30">
                    <input type="checkbox" name="camera_consent" value="1" class="mt-1 h-4 w-4 rounded border-line text-brand focus:ring-brand" @checked(old('camera_consent'))>
                    <span class="text-sm leading-relaxed text-ink-soft">
                        Разрешаю использовать камеру для упражнения «Мимика».
                        <span class="mt-1 block text-xs text-muted">Необязательно. Кадры обрабатываются в браузере и не покидают устройство.</span>
                    </span>
                </label>

                <div class="flex items-center justify-between gap-3 pt-2">
                    <button type="button" class="btn-secondary" @click="step = 1"><x-icon name="chevron-left" class="h-4 w-4" /> Назад</button>
                    <button type="submit" class="btn-primary">Начать <x-icon name="sparkles" class="h-4 w-4" /></button>
                </div>
            </div>
        </form>

        <p class="mt-4 text-center text-[11px] leading-relaxed text-muted">
            Это педагогический инструмент. Он не ставит диагнозы и не заменяет консультацию педагога-психолога.
        </p>
    </div>
@endsection

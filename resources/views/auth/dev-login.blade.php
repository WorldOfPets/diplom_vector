@extends('layouts.guest')

@section('title', 'Локальный вход')

@section('content')
    <div class="card p-6">
        <x-alert type="warning" class="mb-5">
            Только для локальной разработки. В production этот маршрут отключён.
        </x-alert>

        <h1 class="text-xl font-extrabold tracking-tight">Локальный вход</h1>
        <p class="mt-1 text-sm text-muted">Создаёт или выбирает пользователя по email, без Яндекса.</p>

        <form method="POST" action="{{ route('dev.login.store') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label class="label" for="name">Имя</label>
                <input class="input" id="name" name="name" value="{{ old('name', 'Тестовый студент') }}" required>
            </div>

            <div>
                <label class="label" for="email">Email</label>
                <input class="input" id="email" name="email" type="email" value="{{ old('email', 'student@example.com') }}" required>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="role">Роль</label>
                    <select class="input" id="role" name="role">
                        @foreach($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', 'student') === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="group_id">Группа</label>
                    <select class="input" id="group_id" name="group_id">
                        <option value="">—</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected(old('group_id') == $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-primary w-full">Войти</button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('login') }}" class="text-xs font-bold text-muted hover:text-ink">← Обычный вход</a>
        </div>
    </div>
@endsection

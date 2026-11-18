@extends('layouts.admin')

@section('title', 'Пользователи')

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="section-eyebrow">Пользователи</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight">Студенты и роли</h1>
            <p class="mt-1 text-sm text-muted">Всего {{ $users->total() }}. Здесь же можно сбросить попытки.</p>
        </div>
        <a href="{{ route('admin.export.users') }}" class="btn-secondary px-4 py-2.5 text-xs">Экспорт CSV</a>
    </div>

    <form method="GET" class="card mt-5 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label class="label" for="q">Поиск</label>
            <input id="q" name="q" value="{{ request('q') }}" class="input" placeholder="Имя или email">
        </div>
        <div class="sm:w-44">
            <label class="label" for="role">Роль</label>
            <select id="role" name="role" class="input">
                <option value="">Все</option>
                @foreach($roles as $role)
                    <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:w-56">
            <label class="label" for="group_id">Группа</label>
            <select id="group_id" name="group_id" class="input">
                <option value="">Все</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}" @selected((int) request('group_id') === $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn-primary px-5 py-3 text-xs">Фильтр</button>
    </form>

    <div class="mt-5 space-y-3">
        @forelse($users as $user)
            <div class="card p-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
                    <div class="flex min-w-0 items-center gap-3 lg:w-72">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="" class="h-10 w-10 rounded-2xl object-cover" referrerpolicy="no-referrer">
                        @else
                            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-brand-soft text-xs font-extrabold text-brand-deep">{{ $user->initials() }}</span>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold">{{ $user->name }}</p>
                            <p class="truncate text-[11px] text-muted">{{ $user->email }}</p>
                            <p class="mt-0.5 text-[10px] font-semibold text-muted">был(а) {{ $user->last_seen_at?->diffForHumans() ?? 'ни разу' }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="grid flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="label">Имя</label>
                            <input name="name" value="{{ $user->name }}" class="input py-2.5 text-xs" required>
                        </div>
                        <div>
                            <label class="label">Роль</label>
                            <select name="role" class="input py-2.5 text-xs">
                                @foreach($roles as $role)
                                    <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Группа</label>
                            <select name="group_id" class="input py-2.5 text-xs">
                                <option value="">—</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}" @selected($user->group_id === $group->id)>{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Курс</label>
                            <select name="course" class="input py-2.5 text-xs">
                                <option value="">—</option>
                                @for($course = 1; $course <= 4; $course++)
                                    <option value="{{ $course }}" @selected($user->course === $course)>{{ $course }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="label">Специальность</label>
                            <input name="specialty" value="{{ $user->specialty }}" class="input py-2.5 text-xs">
                        </div>
                        <div class="flex items-end">
                            <button class="btn-primary w-full py-2.5 text-xs">Сохранить</button>
                        </div>
                    </form>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-line/60 pt-3">
                    <span class="chip">{{ $user->role?->label() }}</span>
                    <span class="chip">{{ $user->group?->name ?? 'без группы' }}</span>
                    <form method="POST" action="{{ route('admin.users.reset', $user) }}" onsubmit="return confirm('Удалить все попытки этого пользователя?')">
                        @csrf
                        <button class="btn-danger px-3 py-1.5 text-[10px]">Сбросить попытки</button>
                    </form>
                </div>
            </div>
        @empty
            <x-empty-state icon="user" title="Пользователи не найдены" text="Измените фильтры или дождитесь первого входа." />
        @endforelse
    </div>

    <div class="mt-5">
        {{ $users->links() }}
    </div>
@endsection

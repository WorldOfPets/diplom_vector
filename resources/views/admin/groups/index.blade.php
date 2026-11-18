@extends('layouts.admin')

@section('title', 'Группы')

@section('content')
    <div>
        <p class="section-eyebrow">Группы</p>
        <h1 class="mt-1 text-3xl font-extrabold tracking-tight">Учебные группы</h1>
        <p class="mt-1 text-sm text-muted">Группа выбирается на онбординге и используется для фильтров и статистики.</p>
    </div>

    <form method="POST" action="{{ route('admin.groups.store') }}" class="card mt-5 grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-6 lg:items-end">
        @csrf
        <div>
            <label class="label" for="group-name">Название</label>
            <input id="group-name" name="name" class="input py-2.5 text-xs" placeholder="ИС-23" required>
        </div>
        <div>
            <label class="label" for="group-code">Код</label>
            <input id="group-code" name="code" class="input py-2.5 text-xs" placeholder="IS-23" required>
        </div>
        <div class="lg:col-span-2">
            <label class="label" for="group-specialty">Специальность</label>
            <input id="group-specialty" name="specialty" class="input py-2.5 text-xs" placeholder="09.02.07 ...">
        </div>
        <div>
            <label class="label" for="group-course">Курс</label>
            <select id="group-course" name="course" class="input py-2.5 text-xs">
                <option value="">—</option>
                @for($course = 1; $course <= 4; $course++)
                    <option value="{{ $course }}">{{ $course }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label class="label" for="group-curator">Куратор</label>
            <input id="group-curator" name="curator_name" class="input py-2.5 text-xs" placeholder="ФИО">
        </div>
        <div class="lg:col-span-6">
            <button class="btn-primary px-5 py-2.5 text-xs">Добавить группу</button>
        </div>
    </form>

    <div class="mt-5 space-y-3">
        @foreach($groups as $group)
            <div class="card p-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
                    <div class="lg:w-52">
                        <p class="text-sm font-extrabold">{{ $group->name }}</p>
                        <p class="mt-0.5 text-[11px] font-semibold text-muted">{{ $group->code }} · {{ $group->users_count }} чел.</p>
                        @if($group->specialty)
                            <p class="mt-1 text-[10px] leading-relaxed text-muted">{{ $group->specialty }}</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.groups.update', $group) }}" class="grid flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="label" for="group-name-{{ $group->id }}">Название</label>
                            <input id="group-name-{{ $group->id }}" name="name" value="{{ $group->name }}" class="input py-2.5 text-xs" required>
                        </div>
                        <div>
                            <label class="label" for="group-code-{{ $group->id }}">Код</label>
                            <input id="group-code-{{ $group->id }}" name="code" value="{{ $group->code }}" class="input py-2.5 text-xs" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label" for="group-specialty-{{ $group->id }}">Специальность</label>
                            <input id="group-specialty-{{ $group->id }}" name="specialty" value="{{ $group->specialty }}" class="input py-2.5 text-xs">
                        </div>
                        <div>
                            <label class="label" for="group-course-{{ $group->id }}">Курс</label>
                            <select id="group-course-{{ $group->id }}" name="course" class="input py-2.5 text-xs">
                                <option value="">—</option>
                                @for($course = 1; $course <= 4; $course++)
                                    <option value="{{ $course }}" @selected($group->course === $course)>{{ $course }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label" for="group-curator-{{ $group->id }}">Куратор</label>
                            <input id="group-curator-{{ $group->id }}" name="curator_name" value="{{ $group->curator_name }}" class="input py-2.5 text-xs">
                        </div>
                        <div class="flex items-end">
                            <button class="btn-primary w-full py-2.5 text-xs">Сохранить</button>
                        </div>
                    </form>
                </div>

                <div class="mt-3 border-t border-line/60 pt-3">
                    <form method="POST" action="{{ route('admin.groups.destroy', $group) }}" onsubmit="return confirm('Удалить группу? Пользователи останутся без группы.')">
                        @csrf
                        @method('DELETE')
                        <button class="btn-danger px-3 py-1.5 text-[10px]">Удалить группу</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endsection

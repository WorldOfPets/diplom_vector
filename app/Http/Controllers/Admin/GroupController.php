<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function index(): View
    {
        return view('admin.groups.index', [
            'groups' => Group::query()->withCount('users')->orderBy('course')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['required', 'string', 'max:40', 'unique:groups,code'],
            'specialty' => ['nullable', 'string', 'max:190'],
            'course' => ['nullable', 'integer', 'between:1,4'],
            'curator_name' => ['nullable', 'string', 'max:120'],
        ]);

        $group = Group::query()->create($data);
        AuditLog::record('group.created', $group);

        return back()->with('status', 'Группа добавлена.');
    }

    public function update(Request $request, Group $group): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['required', 'string', 'max:40', Rule::unique('groups', 'code')->ignore($group->id)],
            'specialty' => ['nullable', 'string', 'max:190'],
            'course' => ['nullable', 'integer', 'between:1,4'],
            'curator_name' => ['nullable', 'string', 'max:120'],
        ]);

        $group->update($data);
        AuditLog::record('group.updated', $group);

        return back()->with('status', 'Группа обновлена.');
    }

    public function destroy(Group $group): RedirectResponse
    {
        DB::transaction(function () use ($group): void {
            $group->users()->update(['group_id' => null]);
            $group->delete();
        });

        AuditLog::record('group.deleted', null, ['name' => $group->name]);

        return back()->with('status', 'Группа удалена. Пользователи остались без группы.');
    }
}

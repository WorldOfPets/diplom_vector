<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Group;
use App\Models\TestAttempt;
use App\Models\TrainerAttempt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('group')
            ->when($request->string('q')->trim()->value(), function ($query, string $q): void {
                $query->where(function ($query) use ($q): void {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($request->string('role')->value(), fn ($query, string $role) => $query->where('role', $role))
            ->when($request->integer('group_id'), fn ($query, int $groupId) => $query->where('group_id', $groupId))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'groups' => Group::query()->orderBy('name')->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'course' => ['nullable', 'integer', 'between:1,4'],
            'specialty' => ['nullable', 'string', 'max:190'],
        ]);

        $user->update($data);
        AuditLog::record('user.updated', $user, ['role' => $data['role']]);

        return back()->with('status', 'Пользователь обновлён.');
    }

    public function reset(User $user): RedirectResponse
    {
        TrainerAttempt::query()->where('user_id', $user->id)->delete();
        TestAttempt::query()->where('user_id', $user->id)->delete();
        AuditLog::record('user.attempts_reset', $user);

        return back()->with('status', 'Попытки пользователя удалены.');
    }
}

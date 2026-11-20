<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CameraProgress;
use App\Models\Certificate;
use App\Models\Feedback;
use App\Models\Group;
use App\Models\ProgressSnapshot;
use App\Models\Reflection;
use App\Models\Short;
use App\Models\ShortLike;
use App\Models\ShortView;
use App\Models\TestAttempt;
use App\Models\TheoryProgress;
use App\Models\TrainerAttempt;
use App\Models\TrainerFeedback;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        if (
            $user->isAdmin()
            && $data['role'] !== UserRole::Admin->value
            && User::query()->where('role', UserRole::Admin)->count() <= 1
        ) {
            return back()->withErrors([
                'role' => 'Нельзя снять роль администратора с последнего администратора.',
            ]);
        }

        $user->update($data);
        AuditLog::record('user.updated', $user, ['role' => $data['role']]);

        return back()->with('status', 'Пользователь обновлён.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Нельзя удалить собственную учётную запись из админки.']);
        }

        if ($user->isAdmin() && User::query()->where('role', UserRole::Admin)->count() <= 1) {
            return back()->withErrors(['user' => 'Нельзя удалить последнего администратора.']);
        }

        $email = $user->email;

        DB::transaction(function () use ($user): void {
            $likedShortIds = ShortLike::query()->where('user_id', $user->id)->pluck('short_id');

            ShortLike::query()->where('user_id', $user->id)->delete();

            if ($likedShortIds->isNotEmpty()) {
                Short::query()
                    ->whereIn('id', $likedShortIds)
                    ->where('likes_count', '>', 0)
                    ->decrement('likes_count');
            }

            $user->delete();
        });

        AuditLog::record('user.deleted', null, ['email' => $email]);

        return back()->with('status', 'Пользователь и связанные данные удалены.');
    }

    public function reset(User $user): RedirectResponse
    {
        DB::transaction(function () use ($user): void {
            TrainerFeedback::query()->where('user_id', $user->id)->delete();
            TrainerAttempt::query()->where('user_id', $user->id)->delete();
            TestAttempt::query()->where('user_id', $user->id)->delete();
            ShortView::query()->where('user_id', $user->id)->delete();
            TheoryProgress::query()->where('user_id', $user->id)->delete();
            CameraProgress::query()->where('user_id', $user->id)->delete();
            ProgressSnapshot::query()->where('user_id', $user->id)->delete();
            Reflection::query()->where('user_id', $user->id)->delete();
            Feedback::query()->where('user_id', $user->id)->delete();
            Certificate::query()->where('user_id', $user->id)->delete();
        });

        AuditLog::record('user.progress_reset', $user);

        return back()->with('status', 'Попытки и прогресс пользователя сброшены.');
    }
}

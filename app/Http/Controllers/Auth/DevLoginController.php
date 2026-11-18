<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DevLoginController extends Controller
{
    public function show(): View
    {
        abort_unless(app()->isLocal() && config('app.dev_login'), 404);

        return view('auth.dev-login', [
            'groups' => Group::query()->orderBy('name')->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(app()->isLocal() && config('app.dev_login'), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
        ]);

        $user = User::query()->firstOrNew(['email' => $data['email']]);
        $user->fill([
            'name' => $data['name'],
            'role' => UserRole::from($data['role']),
            'group_id' => $data['group_id'] ?? null,
        ]);

        if ($user->role !== UserRole::Student) {
            $user->consent_at = $user->consent_at ?? now();
            $user->consent_version = $user->consent_version ?? '1.0';
        }

        $user->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(
            $user->needsOnboarding() ? route('onboarding.show') : route('dashboard')
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->user()->needsOnboarding()) {
            return redirect()->route('dashboard');
        }

        return view('onboarding', [
            'groups' => Group::query()->orderBy('course')->orderBy('name')->get(),
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'specialty' => ['nullable', 'string', 'max:190'],
            'course' => ['nullable', 'integer', 'between:1,4'],
            'consent' => ['accepted'],
            'camera_consent' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Укажите, как к вам обращаться.',
            'consent.accepted' => 'Без согласия на обработку данных продолжить нельзя.',
        ]);

        $user = $request->user();
        $user->fill([
            'name' => $data['name'],
            'group_id' => $data['group_id'] ?? null,
            'specialty' => $data['specialty'] ?? null,
            'course' => $data['course'] ?? null,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        if ($request->boolean('camera_consent')) {
            $user->camera_consent_at = now();
        }

        $user->save();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Профиль заполнен. Можно начинать.');
    }
}

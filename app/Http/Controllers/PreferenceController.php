<?php

namespace App\Http\Controllers;

use App\Support\Preferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

class PreferenceController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme' => ['required', 'string', Rule::in(Preferences::themes())],
            'tone' => ['required', 'string', Rule::in(Preferences::tones())],
        ], [
            'theme.required' => 'Выберите тему.',
            'theme.in' => 'Неизвестная тема.',
            'tone.required' => 'Выберите стиль текста.',
            'tone.in' => 'Неизвестный стиль текста.',
        ]);

        $user = $request->user();

        if ($user !== null) {
            $user->forceFill([
                'theme' => $data['theme'],
                'tone' => $data['tone'],
                'preferences_set_at' => now(),
            ])->save();
        }

        $request->session()->put([
            'vektor_theme' => $data['theme'],
            'vektor_tone' => $data['tone'],
        ]);

        Cookie::queue('vektor_theme', $data['theme'], 60 * 24 * 365);
        Cookie::queue('vektor_tone', $data['tone'], 60 * 24 * 365);

        return back()->with('status', 'Настройки сохранены.');
    }
}

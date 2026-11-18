<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class YandexController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (blank(config('services.yandex.client_id'))) {
            if (app()->isLocal() && config('app.dev_login')) {
                return redirect()
                    ->route('dev.login')
                    ->with('status', 'Яндекс OAuth не настроен. Используйте локальный вход для разработки.');
            }

            return redirect()
                ->route('login')
                ->withErrors(['oauth' => 'Вход через Яндекс временно недоступен. Попробуйте позже.']);
        }

        return Socialite::driver('yandex')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $yandexUser = Socialite::driver('yandex')->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('login')
                ->withErrors(['oauth' => 'Не удалось войти через Яндекс. Попробуйте ещё раз.']);
        }

        $yandexId = (string) $yandexUser->getId();
        $email = Str::lower(
            $yandexUser->getEmail() ?: 'yandex-'.$yandexId.'@no-email.local',
        );

        $byYandex = User::query()->where('yandex_id', $yandexId)->first();
        $byEmail = User::query()->where('email', $email)->first();

        if ($byYandex !== null) {
            $user = $byYandex;
        } elseif ($byEmail !== null) {
            if ($byEmail->yandex_id !== null && (string) $byEmail->yandex_id !== $yandexId) {
                return redirect()
                    ->route('login')
                    ->withErrors(['oauth' => 'Этот email уже привязан к другому аккаунту Яндекса. Войдите под ним или обратитесь к администратору.']);
            }

            $user = $byEmail;
        } else {
            $user = new User;
            $user->role = UserRole::Student;
        }

        $avatar = $yandexUser->getAvatar();

        if (is_string($avatar) && str_contains($avatar, 'get-yapic//')) {
            $avatar = null;
        }

        $user->fill([
            'yandex_id' => $yandexId,
            'name' => $yandexUser->getName() ?: $yandexUser->getNickname() ?: 'Студент',
            'email' => $byYandex !== null && $byEmail !== null && ! $byEmail->is($byYandex)
                ? $byYandex->email
                : $email,
            'avatar_url' => $avatar,
        ]);

        if (blank($user->name)) {
            $user->name = 'Студент '.Str::upper(Str::random(4));
        }

        $user->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(
            $user->needsOnboarding() ? route('onboarding.show') : route('dashboard')
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}

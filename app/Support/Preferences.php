<?php

namespace App\Support;

use App\Models\User;

final class Preferences
{
    public const ThemeLight = 'light';

    public const ThemeDark = 'dark';

    public const ToneVibe = 'vibe';

    public const ToneProfessional = 'professional';

    /**
     * @return array<int, string>
     */
    public static function themes(): array
    {
        return [self::ThemeLight, self::ThemeDark];
    }

    /**
     * @return array<int, string>
     */
    public static function tones(): array
    {
        return [self::ToneVibe, self::ToneProfessional];
    }

    public static function theme(?User $user = null): string
    {
        $user ??= auth()->user();

        if ($user !== null && in_array($user->theme, self::themes(), true)) {
            return $user->theme;
        }

        $session = session('vektor_theme');

        if (in_array($session, self::themes(), true)) {
            return $session;
        }

        $cookie = request()->cookie('vektor_theme');

        return in_array($cookie, self::themes(), true) ? $cookie : self::ThemeLight;
    }

    public static function tone(?User $user = null): string
    {
        $user ??= auth()->user();

        if ($user !== null && in_array($user->tone, self::tones(), true)) {
            return $user->tone;
        }

        $session = session('vektor_tone');

        if (in_array($session, self::tones(), true)) {
            return $session;
        }

        $cookie = request()->cookie('vektor_tone');

        return in_array($cookie, self::tones(), true) ? $cookie : self::ToneVibe;
    }

    public static function isSet(?User $user = null): bool
    {
        $user ??= auth()->user();

        if ($user !== null) {
            return $user->preferences_set_at !== null;
        }

        return (in_array(session('vektor_theme'), self::themes(), true)
                && in_array(session('vektor_tone'), self::tones(), true))
            || (in_array(request()->cookie('vektor_theme'), self::themes(), true)
                && in_array(request()->cookie('vektor_tone'), self::tones(), true));
    }

    public static function isDark(?User $user = null): bool
    {
        return self::theme($user) === self::ThemeDark;
    }
}

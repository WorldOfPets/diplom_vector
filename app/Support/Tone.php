<?php

namespace App\Support;

final class Tone
{
    public static function line(string $key, array $replace = []): string
    {
        $entry = trans('tone.'.$key);

        if (is_string($entry)) {
            return strtr($entry, $replace);
        }

        if (! is_array($entry)) {
            return $key;
        }

        $text = $entry[Preferences::tone()] ?? $entry[Preferences::ToneProfessional] ?? reset($entry);

        if (! is_string($text)) {
            return $key;
        }

        $replacements = [];

        foreach ($replace as $search => $value) {
            $replacements[$search] = (string) $value;
            $replacements[':'.$search] = (string) $value;
        }

        return strtr($text, $replacements);
    }

    public static function vibe(): bool
    {
        return Preferences::tone() === Preferences::ToneVibe;
    }
}

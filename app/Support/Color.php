<?php

namespace App\Support;

final class Color
{
    public static function safe(mixed $value, string $fallback = '#2D5BFF'): string
    {
        return is_string($value) && preg_match('/^#[0-9a-f]{6}$/i', $value) === 1
            ? $value
            : $fallback;
    }
}

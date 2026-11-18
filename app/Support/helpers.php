<?php

use App\Support\Tone;

if (! function_exists('tone')) {
    /**
     * @param  array<string, string|int|float>  $replace
     */
    function tone(string $key, array $replace = []): string
    {
        return Tone::line($key, $replace);
    }
}

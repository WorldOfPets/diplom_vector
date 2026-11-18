<?php

namespace App\Support;

use Illuminate\Encryption\Encrypter;
use RuntimeException;

final class DiaryCipher
{
    public static function encrypt(?int $userId, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::encrypter($userId)->encryptString($value);
    }

    public static function decrypt(?int $userId, ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return self::encrypter($userId)->decryptString($value);
    }

    public static function encrypter(?int $userId): Encrypter
    {
        if ($userId === null) {
            throw new RuntimeException('Diary entries must belong to a user.');
        }

        $appKey = (string) config('app.key');

        if (str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7), true) ?: '';
        }

        if ($appKey === '') {
            throw new RuntimeException('Application key is not configured.');
        }

        $key = hash_hmac('sha256', 'vektor-diary:'.$userId, $appKey, true);

        return new Encrypter($key, 'AES-256-GCM');
    }
}

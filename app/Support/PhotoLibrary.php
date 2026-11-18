<?php

namespace App\Support;

class PhotoLibrary
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function all(): array
    {
        /** @var array<string, array<string, string>> $photos */
        $photos = config('photos', []);

        return $photos;
    }

    /**
     * @return array<string, string>|null
     */
    public static function get(?string $path): ?array
    {
        if ($path === null || $path === '') {
            return null;
        }

        return self::all()[ltrim($path, '/')] ?? null;
    }

    /**
     * @return array{author: string, author_url: string, source: string, source_url: string, license: string, license_url: string}|null
     */
    public static function credit(?string $path): ?array
    {
        $photo = self::get($path);

        if ($photo === null) {
            return null;
        }

        return [
            'author' => $photo['author'] ?? 'Unsplash',
            'author_url' => $photo['author_url'] ?? 'https://unsplash.com',
            'source' => $photo['source'] ?? 'Unsplash',
            'source_url' => $photo['source_url'] ?? 'https://unsplash.com',
            'license' => $photo['license'] ?? 'Unsplash License',
            'license_url' => $photo['license_url'] ?? 'https://unsplash.com/license',
        ];
    }

    /**
     * @return array<int, array{path: string, alt: string, author: string, source: string, source_url: string, license: string, license_url: string}>
     */
    public static function credits(): array
    {
        $credits = [];

        foreach (self::all() as $path => $photo) {
            $credits[] = [
                'path' => $path,
                'alt' => $photo['alt'] ?? '',
                'author' => $photo['author'] ?? 'Unsplash',
                'source' => $photo['source'] ?? 'Unsplash',
                'source_url' => $photo['source_url'] ?? 'https://unsplash.com',
                'license' => $photo['license'] ?? 'Unsplash License',
                'license_url' => $photo['license_url'] ?? 'https://unsplash.com/license',
            ];
        }

        usort($credits, fn (array $a, array $b): int => strcmp($a['path'], $b['path']));

        return $credits;
    }
}

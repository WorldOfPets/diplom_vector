<?php

namespace App\Support;

class Levels
{
    /**
     * @return array{label: string, color: string, description: string}
     */
    public static function info(string $level): array
    {
        return match ($level) {
            'high' => [
                'label' => 'Сильный результат',
                'color' => '#2BB673',
                'description' => 'В этих заданиях навык проявился уверенно. Опирайтесь на него и проверяйте в реальных ситуациях.',
            ],
            'above_average' => [
                'label' => 'Уверенный результат',
                'color' => '#2D5BFF',
                'description' => 'Хорошая база по заданиям. Есть что усилить точечно.',
            ],
            'average' => [
                'label' => 'Базовый уровень',
                'color' => '#D97706',
                'description' => 'База есть. Регулярная практика даст заметный сдвиг.',
            ],
            'below_average' => [
                'label' => 'Начальный уровень',
                'color' => '#FF6B4A',
                'description' => 'Зона роста. Начните с одного тренажёра, не со всего сразу.',
            ],
            default => [
                'label' => 'Стартовый уровень',
                'color' => '#E11D48',
                'description' => 'Пока трудно. Это не приговор: навык развивается.',
            ],
        };
    }

    public static function label(string $level): string
    {
        return self::info($level)['label'];
    }

    public static function color(string $level): string
    {
        return self::info($level)['color'];
    }
}

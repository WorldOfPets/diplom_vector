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
                'label' => 'Высокий',
                'color' => '#2BB673',
                'description' => 'Сильная сторона. Опирайтесь на неё и помогайте другим.',
            ],
            'above_average' => [
                'label' => 'Выше среднего',
                'color' => '#2D5BFF',
                'description' => 'Хороший уровень. Есть что усилить точечно.',
            ],
            'average' => [
                'label' => 'Средний',
                'color' => '#D97706',
                'description' => 'База есть. Регулярная практика даст заметный сдвиг.',
            ],
            'below_average' => [
                'label' => 'Ниже среднего',
                'color' => '#FF6B4A',
                'description' => 'Зона роста. Начните с одного тренажёра, не со всего сразу.',
            ],
            default => [
                'label' => 'Низкий',
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

<?php

namespace App\Enums;

enum EiBranch: string
{
    case Perception = 'perception';
    case Understanding = 'understanding';
    case Using = 'using';
    case Managing = 'managing';
    case Motivation = 'motivation';

    public function label(): string
    {
        return match ($this) {
            self::Perception => 'Восприятие эмоций',
            self::Understanding => 'Понимание эмоций',
            self::Using => 'Использование эмоций',
            self::Managing => 'Управление эмоциями',
            self::Motivation => 'Мотивация',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Perception => 'Восприятие',
            self::Understanding => 'Понимание',
            self::Using => 'Использование',
            self::Managing => 'Управление',
            self::Motivation => 'Мотивация',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Perception => '#2D5BFF',
            self::Understanding => '#7C5CFF',
            self::Using => '#0EA5A4',
            self::Managing => '#FF6B4A',
            self::Motivation => '#D97706',
        };
    }

    public static function eiBranches(): array
    {
        return [self::Perception, self::Understanding, self::Using, self::Managing];
    }
}

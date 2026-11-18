<?php

namespace App\Enums;

enum TestKind: string
{
    case EiFull = 'ei_full';
    case EiSituational = 'ei_situational';
    case EiExpress = 'ei_express';
    case Motivation = 'motivation';

    public function label(): string
    {
        return match ($this) {
            self::EiFull => 'Полный профиль ЭИ',
            self::EiSituational => 'Кейсы СПО',
            self::EiExpress => 'Экспресс-ЭИ',
            self::Motivation => 'Мотивация',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::EiFull => 'Полный профиль',
            self::EiSituational => 'Кейсы СПО',
            self::EiExpress => 'Экспресс',
            self::Motivation => 'Мотивация',
        };
    }

    public function isEi(): bool
    {
        return $this !== self::Motivation;
    }
}

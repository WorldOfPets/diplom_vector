<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Curator = 'curator';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Студент',
            self::Curator => 'Куратор',
            self::Admin => 'Администратор',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Student;
    }
}

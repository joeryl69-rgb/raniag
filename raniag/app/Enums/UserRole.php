<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Agency = 'agency';
    case Personnel = 'personnel';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Agency => 'Agency',
            self::Personnel => 'Personnel',
        };
    }
}

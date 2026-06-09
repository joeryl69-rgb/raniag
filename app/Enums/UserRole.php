<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Agency = 'agency';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Agency => 'Agency',
        };
    }
}

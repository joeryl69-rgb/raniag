<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Database = 'database';
    case Mail = 'mail';
    case Sms = 'sms';

    public function label(): string
    {
        return match ($this) {
            self::Database => 'Database',
            self::Mail => 'Email',
            self::Sms => 'SMS',
        };
    }
}

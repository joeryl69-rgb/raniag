<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hostinger already runs: php artisan schedule:run every minute.
// Queue draining: separate cron with queue:work --stop-when-empty.
Schedule::command('raniag:escalate-sla')->hourly();
Schedule::command('raniag:purge-retained')->dailyAt('02:15');
Schedule::command('raniag:monthly-summary')->monthlyOn(1, '03:00');

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hostinger already runs: php artisan schedule:run every minute.
// Its CLI cannot spawn another process, so the queue has to drain inside
// this same run. Otherwise push jobs sit forever and no system notification
// is delivered.
Schedule::call(function () {
    Artisan::call('queue:work', [
        '--stop-when-empty' => true,
        '--max-time' => 45,
        '--tries' => 3,
        '--queue' => 'default',
    ]);
})->everyMinute()->name('drain-queue')->withoutOverlapping(2);

Schedule::command('raniag:escalate-sla')->hourly();
Schedule::command('raniag:purge-retained')->dailyAt('02:15');
Schedule::command('raniag:monthly-summary')->monthlyOn(1, '03:00');

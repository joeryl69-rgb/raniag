<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Queue draining is handled by Hostinger cron calling:
// php artisan queue:work --stop-when-empty
// (Schedule registration deferred until deploy is stable.)

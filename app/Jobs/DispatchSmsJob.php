<?php

namespace App\Jobs;

use App\Models\SmsLog;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public int $smsLogId) {}

    public function handle(NotificationService $notifications): void
    {
        $smsLog = SmsLog::find($this->smsLogId);

        if (! $smsLog) {
            return;
        }

        $notifications->dispatchSms($smsLog);
    }
}

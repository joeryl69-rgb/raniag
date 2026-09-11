<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fans a single in-app Notification (see App\Services\NotificationService::notify)
 * out to the recipient's subscribed browsers/devices as a real OS push
 * notification, so it's seen even if RANIAG isn't open. Queued the same
 * way DispatchSmsJob is, so a slow/unreachable push service never blocks
 * the request that created the notification.
 */
class DispatchWebPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public int $notificationId) {}

    public function handle(WebPushService $webPush): void
    {
        $notification = Notification::with('user')->find($this->notificationId);

        if (! $notification || ! $notification->user) {
            return;
        }

        $webPush->sendToUser(
            $notification->user,
            $notification->title,
            $notification->message,
            $notification->url(),
        );
    }
}

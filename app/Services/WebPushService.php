<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Sends browser Web Push notifications so a user gets an OS-level
 * notification (with title/body/click-through URL) even when RANIAG
 * isn't open in a tab — the same mechanism Messenger/Gmail use. Backed
 * by the standard VAPID + Push API protocol via minishlink/web-push
 * (composer.json); requires VAPID_PUBLIC_KEY/VAPID_PRIVATE_KEY in .env
 * (see App\Console\Commands\GenerateVapidKeys).
 */
class WebPushService
{
    private ?WebPush $webPush = null;

    private function client(): ?WebPush
    {
        if ($this->webPush) {
            return $this->webPush;
        }

        $publicKey = config('services.webpush.public_key');
        $privateKey = config('services.webpush.private_key');

        if (! $publicKey || ! $privateKey) {
            Log::warning('Web Push skipped: VAPID keys not configured. Run php artisan raniag:generate-vapid-keys.');

            return null;
        }

        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        return $this->webPush;
    }

    /**
     * Push the same payload to every device the user is subscribed on.
     * Dead subscriptions (410 Gone / 404) are pruned automatically.
     */
    public function sendToUser(User $user, string $title, string $body, ?string $url = null, array $extra = []): void
    {
        $client = $this->client();
        if (! $client) {
            return;
        }

        $subscriptions = PushSubscription::where('user_id', $user->id)->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = json_encode(array_merge([
            'title' => $title,
            'body' => $body,
            'url' => $url ?? '/',
            'icon' => '/images/icons/icon-192.png',
            'badge' => '/images/icons/icon-72.png',
        ], $extra));

        foreach ($subscriptions as $subscription) {
            $client->queueNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
                ]),
                $payload
            );
        }

        foreach ($client->flush() as $report) {
            if ($report->isSuccess()) {
                continue;
            }

            $endpoint = $report->getRequest()->getUri()->__toString();

            // 404/410 = the browser/push service has invalidated this
            // subscription permanently (uninstalled, revoked permission,
            // etc.) — anything else is transient, so only prune on those.
            if (in_array($report->getResponse()?->getStatusCode(), [404, 410], true)) {
                PushSubscription::where('user_id', $user->id)->where('endpoint', $endpoint)->delete();
            } else {
                Log::warning('Web Push delivery failed', [
                    'user_id' => $user->id,
                    'reason' => $report->getReason(),
                ]);
            }
        }
    }
}

<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\ReporterPushSubscription;
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

        foreach ($subscriptions as $subscription) {
            $sound = $subscription->sound_enabled !== false;
            $vibrate = $subscription->vibrate_enabled !== false;
            $payload = json_encode(array_merge([
                'title' => $title,
                'body' => $body,
                'url' => $url ?? '/',
                'icon' => '/images/icons/icon-192.png',
                'badge' => '/images/icons/icon-72.png',
                'silent' => ! $sound,
                'vibrate' => $vibrate ? [220, 90, 220, 90, 320] : [],
                'sound' => $sound,
            ], $extra));

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

    /**
     * Tell the browser that queued an offline report that RANIAG accepted it.
     */
    public function sendToReporter(string $clientToken, string $title, string $body, ?string $url = null): void
    {
        $client = $this->client();
        $subscription = ReporterPushSubscription::query()->where('client_token', $clientToken)->first();
        if (! $client || ! $subscription) {
            return;
        }

        $client->queueNotification(
            Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
            ]),
            json_encode([
                'title' => $title,
                'body' => $body,
                'url' => $url ?? '/',
                'icon' => '/images/icons/icon-192.png',
                'badge' => '/images/icons/icon-72.png',
                'silent' => false,
                'vibrate' => [220, 90, 220, 90, 320],
                'sound' => true,
            ])
        );

        foreach ($client->flush() as $report) {
            if ($report->isSuccess()) {
                continue;
            }

            if (in_array($report->getResponse()?->getStatusCode(), [404, 410], true)) {
                $subscription->delete();
            } else {
                Log::warning('Reporter web push delivery failed', [
                    'client_token' => $clientToken,
                    'reason' => $report->getReason(),
                ]);
            }
        }
    }
}

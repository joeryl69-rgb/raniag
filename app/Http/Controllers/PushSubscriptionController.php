<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backs public/js/push-notifications.js: the browser calls these after
 * PushManager.subscribe()/unsubscribe() so the server knows which
 * endpoints to send to (see App\Services\WebPushService).
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        PushSubscription::updateOrCreate(
            ['user_id' => $request->user()->id, 'endpoint' => $data['endpoint']],
            [
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => 'aes128gcm',
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]
        );

        return response()->json([
            'status' => 'subscribed',
            'sound' => true,
            'vibrate' => true,
        ]);
    }

    public function updateOptions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sound' => ['required', 'boolean'],
            'vibrate' => ['required', 'boolean'],
        ]);

        $updated = PushSubscription::where('user_id', $request->user()->id)->update([
            'sound_enabled' => $data['sound'],
            'vibrate_enabled' => $data['vibrate'],
        ]);

        if ($updated === 0) {
            return response()->json(['status' => 'none'], 422);
        }

        return response()->json(['status' => 'saved', 'sound' => $data['sound'], 'vibrate' => $data['vibrate']]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint', $data['endpoint'])
            ->delete();

        return response()->json(['status' => 'unsubscribed']);
    }

    public function test(Request $request, WebPushService $webPush): JsonResponse
    {
        if (! config('services.webpush.public_key') || ! config('services.webpush.private_key')) {
            return response()->json([
                'status' => 'unconfigured',
                'message' => 'VAPID keys are missing on the server.',
            ], 503);
        }

        $devices = PushSubscription::where('user_id', $request->user()->id)->count();
        if ($devices === 0) {
            return response()->json([
                'status' => 'none',
                'message' => 'This account has no saved browser yet. Turn Push notifications on first.',
            ], 422);
        }

        $webPush->sendToUser(
            $request->user(),
            'RANIAG test',
            'Push reached this browser. VAPID delivery is working.',
            route('settings.appearance.index'),
        );

        return response()->json(['status' => 'sent', 'devices' => $devices]);
    }
}

<?php

use App\Enums\SmsLogStatus;
use App\Models\SmsLog;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Http;

test('disabled SMS provider never sends and does not mark messages as delivered', function () {
    config([
        'services.sms.provider' => 'Disabled',
        'app.env' => 'local',
    ]);
    Http::fake();

    $smsLog = SmsLog::query()->create([
        'recipient_phone' => '09171234567',
        'message' => 'Test alert',
        'status' => SmsLogStatus::Pending->value,
        'provider' => 'Disabled',
    ]);

    app(NotificationService::class)->dispatchSms($smsLog);

    $smsLog->refresh();
    expect($smsLog->status)->toBe(SmsLogStatus::Failed)
        ->and($smsLog->sent_at)->toBeNull()
        ->and($smsLog->failed_at)->not->toBeNull()
        ->and($smsLog->provider_response['error'] ?? null)
        ->toBe('SMS delivery is disabled by configuration; no provider request was made.');

    Http::assertNothingSent();
});

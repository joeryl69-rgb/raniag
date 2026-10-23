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

test('philsms sends a text and records it only when the gateway keeps it as text', function () {
    config([
        'services.sms.provider' => 'philsms',
        'services.philsms.api_token' => 'test-token',
        'services.philsms.sender_id' => 'PhilSMS',
    ]);

    Http::fake([
        'https://dashboard.philsms.com/api/v3/sms/send' => Http::response([
            'status' => 'success',
            'data' => ['uid' => 'text-1', 'status' => 'Delivered'],
        ]),
        'https://dashboard.philsms.com/api/v3/sms/text-1' => Http::response([
            'status' => 'success',
            'data' => ['uid' => 'text-1', 'sms_type' => 'plain', 'status' => 'Delivered'],
        ]),
    ]);

    $smsLog = SmsLog::query()->create([
        'recipient_phone' => '09171234567',
        'message' => 'RANIAG ALERT: test',
        'status' => SmsLogStatus::Pending->value,
        'provider' => 'philsms',
    ]);

    app(NotificationService::class)->dispatchSms($smsLog);

    $smsLog->refresh();
    expect($smsLog->status)->toBe(SmsLogStatus::Sent)
        ->and($smsLog->provider_message_id)->toBe('text-1');

    Http::assertSent(function ($request) {
        if ($request->url() !== 'https://dashboard.philsms.com/api/v3/sms/send') {
            return false;
        }

        $body = $request->data();

        return ($body['type'] ?? null) === 'plain'
            && ($body['sms_type'] ?? null) === 'plain'
            && ($body['sender_id'] ?? null) === 'PhilSMS'
            && ($body['recipient'] ?? null) === '639171234567'
            && ! array_key_exists('language', $body)
            && ! array_key_exists('gender', $body);
    });
});

test('an accepted philsms text is recorded as sent when the gateway labels it voice', function () {
    config([
        'services.sms.provider' => 'philsms',
        'services.philsms.api_token' => 'test-token',
        'services.philsms.sender_id' => 'PhilSMS',
    ]);

    Http::fake([
        'https://dashboard.philsms.com/api/v3/sms/send' => Http::response([
            'status' => 'success',
            'data' => ['uid' => 'text-voice-label', 'sms_type' => 'voice', 'status' => 'Delivered'],
        ]),
    ]);

    $smsLog = SmsLog::query()->create([
        'recipient_phone' => '09171234567',
        'message' => 'RANIAG ALERT: test',
        'status' => SmsLogStatus::Pending->value,
        'provider' => 'philsms',
    ]);

    app(NotificationService::class)->dispatchSms($smsLog);

    $smsLog->refresh();
    expect($smsLog->status)->toBe(SmsLogStatus::Sent)
        ->and($smsLog->provider_message_id)->toBe('text-voice-label')
        ->and($smsLog->failed_at)->toBeNull()
        ->and($smsLog->provider_response['requested_type'] ?? null)->toBe('plain');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://dashboard.philsms.com/api/v3/sms/send'
            && ($request->data()['type'] ?? null) === 'plain'
            && ($request->data()['sms_type'] ?? null) === 'plain';
    });
});

test('texts marked failed only because philsms labeled them voice are corrected to sent', function () {
    $smsLog = SmsLog::query()->create([
        'recipient_phone' => '09171234567',
        'message' => 'RANIAG ALERT',
        'status' => SmsLogStatus::Failed->value,
        'provider' => 'philsms',
        'failed_at' => now(),
        'provider_response' => [
            'error' => 'PhilSMS routed this alert as a voice call. A text message was requested.',
        ],
    ]);

    $migration = require database_path('migrations/2026_10_23_160000_mark_mislabeled_philsms_texts_as_sent.php');
    $migration->up();

    $smsLog->refresh();
    expect($smsLog->status)->toBe(SmsLogStatus::Sent)
        ->and($smsLog->failed_at)->toBeNull()
        ->and($smsLog->sent_at)->not->toBeNull();
});

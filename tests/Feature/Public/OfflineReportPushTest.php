<?php

use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\ReporterPushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a reporter can register the browser that should receive the submitted notice', function () {
    $this->postJson(route('public.report.notifications'), [
        'endpoint' => 'https://push.example.test/subscription/1',
        'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
        'client_token' => 'phone-token-1',
    ])->assertOk()->assertJsonPath('client_token', 'phone-token-1');

    $row = ReporterPushSubscription::query()->first();
    expect($row)->not->toBeNull()
        ->and($row->endpoint)->toBe('https://push.example.test/subscription/1')
        ->and($row->public_key)->toBe('public-key');
});

test('an offline report sends a vapid notice only after it is accepted', function () {
    Storage::fake('local');
    $type = IncidentType::factory()->create();
    ReporterPushSubscription::query()->create([
        'client_token' => 'phone-token-1',
        'endpoint' => 'https://push.example.test/subscription/1',
        'public_key' => 'public-key',
        'auth_token' => 'auth-token',
        'content_encoding' => 'aes128gcm',
    ]);

    $push = \Mockery::mock(WebPushService::class);
    $push->shouldReceive('sendToReporter')
        ->once()
        ->withArgs(function (string $token, string $title, string $body, string $url) {
            return $token === 'phone-token-1'
                && $title === 'Report submitted'
                && str_contains($body, 'RAN-')
                && str_contains($url, '/report/');
        });
    app()->instance(WebPushService::class, $push);

    $gpsLog = json_encode([
        [
            'filename' => 'gps-123.jpg',
            'latitude' => 18.472,
            'longitude' => 121.325,
            'accuracy' => 12,
            'captured_at' => now()->toIso8601String(),
        ],
    ]);

    $this->postJson(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Queued while the phone was offline.',
        'barangay' => 'Santa Cruz',
        'is_anonymous' => true,
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
        'meta' => ['gps_captures' => $gpsLog],
        'evidence' => [UploadedFile::fake()->image('gps-123.jpg')],
        'offline_delivery' => '1',
        'push_client_token' => 'phone-token-1',
    ])->assertCreated();
});

test('an ordinary online report does not send the offline submission notice', function () {
    Storage::fake('local');
    $type = IncidentType::factory()->create();

    $push = \Mockery::mock(WebPushService::class);
    $push->shouldNotReceive('sendToReporter');
    app()->instance(WebPushService::class, $push);

    $gpsLog = json_encode([
        [
            'filename' => 'gps-123.jpg',
            'latitude' => 18.472,
            'longitude' => 121.325,
            'accuracy' => 12,
            'captured_at' => now()->toIso8601String(),
        ],
    ]);

    $this->postJson(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Sent while the phone was online.',
        'barangay' => 'Santa Cruz',
        'is_anonymous' => true,
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
        'meta' => ['gps_captures' => $gpsLog],
        'evidence' => [UploadedFile::fake()->image('gps-123.jpg')],
        'push_client_token' => 'phone-token-1',
    ])->assertCreated();
});

test('an offline replay still notifies when the original response was lost', function () {
    Storage::fake('local');
    $type = IncidentType::factory()->create();
    ReporterPushSubscription::query()->create([
        'client_token' => 'phone-token-retry',
        'endpoint' => 'https://push.example.test/subscription/retry',
        'public_key' => 'public-key',
        'auth_token' => 'auth-token',
        'content_encoding' => 'aes128gcm',
    ]);

    $push = \Mockery::mock(WebPushService::class);
    $push->shouldReceive('sendToReporter')->once();
    app()->instance(WebPushService::class, $push);

    $report = [
        'incident_type_id' => $type->id,
        'description' => 'The first online response was lost.',
        'barangay' => 'Santa Cruz',
        'is_anonymous' => true,
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
        'meta' => ['gps_captures' => json_encode([[
            'filename' => 'gps-retry.jpg',
            'latitude' => 18.472,
            'longitude' => 121.325,
            'accuracy' => 12,
            'captured_at' => now()->toIso8601String(),
        ]])],
        'evidence' => [UploadedFile::fake()->image('gps-retry.jpg')],
        'idempotency_key' => 'offline-retry-key',
    ];

    $this->postJson(route('public.report.store'), $report)->assertCreated();
    $this->postJson(route('public.report.store'), [
        ...$report,
        'offline_delivery' => '1',
        'push_client_token' => 'phone-token-retry',
    ])->assertOk()->assertJsonPath('duplicate', true);
    $this->postJson(route('public.report.store'), [
        ...$report,
        'offline_delivery' => '1',
        'push_client_token' => 'phone-token-retry',
    ])->assertOk()->assertJsonPath('duplicate', true);
});

test('an offline report can be filed when gps and location were unavailable', function () {
    $type = IncidentType::factory()->create();

    $this->postJson(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Filed while the phone had no signal.',
        'is_anonymous' => 0,
        'reporter_name' => 'Offline Reporter',
        'reporter_phone' => '09171234567',
        'offline_delivery' => '1',
        'meta' => ['location_unavailable' => 'offline'],
    ])->assertCreated();

    $incident = Incident::query()->first();
    expect($incident)->not->toBeNull()
        ->and($incident->latitude)->toBeNull()
        ->and($incident->longitude)->toBeNull()
        ->and($incident->location_address)->toBe('Location unavailable because this report was filed offline.')
        ->and($incident->meta['location_unavailable'] ?? null)->toBe('offline');
});

test('an online report still requires a location when no gps photo was attached', function () {
    $type = IncidentType::factory()->create();

    $this->postJson(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Online with no pin.',
        'is_anonymous' => 0,
        'reporter_name' => 'Online Reporter',
        'reporter_phone' => '09171234567',
    ])->assertStatus(422)->assertJsonValidationErrors('latitude');
});

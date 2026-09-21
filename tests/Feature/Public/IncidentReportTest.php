<?php

use App\Models\Incident;
use App\Models\IncidentType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('public home page is accessible', function () {
    $this->get(route('public.home'))
        ->assertOk()
        ->assertSee(config('raniag.name'), false);
});

test('report form page is accessible', function () {
    IncidentType::factory()->create();

    $this->get(route('public.report.create'))
        ->assertOk()
        ->assertSee('Report an Incident', false)
        ->assertSee('GPS Camera', false);
});

test('anonymous users can submit an incident report via web', function () {
    Storage::fake('local');
    $type = IncidentType::factory()->create();

    $gpsLog = json_encode([
        [
            'filename' => 'gps-123.jpg',
            'latitude' => 18.472,
            'longitude' => 121.325,
            'accuracy' => 12,
            'captured_at' => now()->toIso8601String(),
        ],
    ]);

    $response = $this->post(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Test incident report for RANIAG public module.',
        'barangay' => 'Santa Cruz',
        'is_anonymous' => '1',
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
        'meta' => ['gps_captures' => $gpsLog],
        'evidence' => [UploadedFile::fake()->image('gps-123.jpg')],
    ]);

    $incident = Incident::query()->first();

    expect($incident)->not->toBeNull();
    expect($incident->tracking_pin)->not->toBeNull();
    $response->assertRedirect(route('public.report.success', $incident->tracking_number));
    $response->assertSessionHas('tracking_pin');
});

test('anonymous users can submit an incident report via json', function () {
    Storage::fake('local');
    $type = IncidentType::factory()->create();

    $gpsLog = json_encode([
        [
            'filename' => 'gps-123.jpg',
            'latitude' => 18.472,
            'longitude' => 121.325,
            'accuracy' => 12,
            'captured_at' => now()->toIso8601String(),
        ],
    ]);

    $response = $this->postJson(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Test incident report for RANIAG foundation.',
        'barangay' => 'Santa Cruz',
        'is_anonymous' => true,
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
        'meta' => ['gps_captures' => $gpsLog],
        'evidence' => [
            UploadedFile::fake()->image('gps-123.jpg'),
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonStructure(['tracking_number', 'access_code', 'incident']);
});

test('users can track an incident by tracking number via web', function () {
    Storage::fake('local');
    $type = IncidentType::factory()->create();

    $gpsLog = json_encode([
        [
            'filename' => 'gps-123.jpg',
            'latitude' => 18.472,
            'longitude' => 121.325,
            'accuracy' => 12,
            'captured_at' => now()->toIso8601String(),
        ],
    ]);

    $submit = $this->post(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Trackable incident report with enough detail.',
        'barangay' => 'Santa Cruz',
        'is_anonymous' => '1',
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
        'meta' => ['gps_captures' => $gpsLog],
        'evidence' => [UploadedFile::fake()->image('gps-123.jpg')],
    ]);

    $trackingNumber = Incident::query()->value('tracking_number');
    $accessCode = $submit->getSession()->get('tracking_pin');

    $this->post(route('public.track.lookup'), [
        'tracking_number' => $trackingNumber,
        'access_code' => $accessCode,
    ])
        ->assertOk()
        ->assertSee($trackingNumber, false)
        ->assertSee('Incident Status', false);
});

test('users can track an incident by tracking number via json', function () {
    $type = IncidentType::factory()->create();

    Storage::fake('local');
    $gpsLog = json_encode([
        [
            'filename' => 'gps-123.jpg',
            'latitude' => 18.472,
            'longitude' => 121.325,
            'accuracy' => 12,
            'captured_at' => now()->toIso8601String(),
        ],
    ]);

    $submit = $this->postJson(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Trackable incident report.',
        'is_anonymous' => true,
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
        'meta' => ['gps_captures' => $gpsLog],
        'evidence' => [UploadedFile::fake()->image('gps-123.jpg')],
    ]);

    $trackingNumber = $submit->json('tracking_number');
    $accessCode = $submit->json('access_code');

    $this->postJson(route('public.track.lookup'), [
        'tracking_number' => $trackingNumber,
        'access_code' => $accessCode,
    ])
        ->assertOk()
        ->assertJsonPath('tracking_number', $trackingNumber);
});

test('tracking rejects a wrong access code', function () {
    Storage::fake('local');
    $type = IncidentType::factory()->create();

    $gpsLog = json_encode([
        [
            'filename' => 'gps-123.jpg',
            'latitude' => 18.472,
            'longitude' => 121.325,
            'accuracy' => 12,
            'captured_at' => now()->toIso8601String(),
        ],
    ]);

    $submit = $this->postJson(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Trackable incident report for access gate.',
        'is_anonymous' => true,
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
        'meta' => ['gps_captures' => $gpsLog],
        'evidence' => [UploadedFile::fake()->image('gps-123.jpg')],
    ]);

    $this->post(route('public.track.lookup'), [
        'tracking_number' => $submit->json('tracking_number'),
        'access_code' => $submit->json('access_code') === '999999' ? '000000' : '999999',
    ])->assertRedirect(route('public.track'));
});

test('report submission stores gps capture metadata', function () {
    $type = IncidentType::factory()->create();

    $gpsLog = json_encode([
        [
            'filename' => 'gps-123.jpg',
            'latitude' => 18.472,
            'longitude' => 121.325,
            'accuracy' => 12,
            'captured_at' => now()->toIso8601String(),
        ],
    ]);

    Storage::fake('local');

    $this->post(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Incident with GPS camera metadata attached.',
        'is_anonymous' => '1',
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
        'meta' => ['gps_captures' => $gpsLog],
        'evidence' => [UploadedFile::fake()->image('gps-123.jpg')],
    ])->assertRedirect();

    $incident = Incident::query()->first();

    expect($incident->meta['gps_captures'])->toBeArray()
        ->and($incident->meta['gps_captures'][0]['filename'])->toBe('gps-123.jpg');
});

test('report submission accepts optional evidence files', function () {
    Storage::fake('local');
    $type = IncidentType::factory()->create();

    $gpsLog = json_encode([
        [
            'filename' => 'gps-123.jpg',
            'latitude' => 18.472,
            'longitude' => 121.325,
            'accuracy' => 12,
            'captured_at' => now()->toIso8601String(),
        ],
    ]);

    $this->post(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Incident with photo evidence attached for review.',
        'is_anonymous' => '1',
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
        'meta' => ['gps_captures' => $gpsLog],
        'evidence' => [
            UploadedFile::fake()->image('scene.jpg'),
        ],
    ])->assertRedirect();

    $incident = Incident::query()->with('evidence')->first();

    expect($incident->evidence)->toHaveCount(1);
    Storage::disk('local')->assertExists($incident->evidence->first()->file_path);
});

test('report submission accepts an unofficial client barangay when geofence cannot resolve one', function () {
    Storage::fake('local');
    $type = IncidentType::factory()->create();

    $gpsLog = json_encode([
        [
            'filename' => 'gps-123.jpg',
            'latitude' => 18.472,
            'longitude' => 121.325,
            'accuracy' => 12,
            'captured_at' => now()->toIso8601String(),
        ],
    ]);

    $this->post(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Incident with a barangay value that does not exist.',
        'barangay' => 'Not A Real Barangay',
        'is_anonymous' => '1',
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
        'meta' => ['gps_captures' => $gpsLog],
        'evidence' => [UploadedFile::fake()->image('gps-123.jpg')],
    ])->assertRedirect();

    expect(Incident::query()->count())->toBe(1);
});

test('report submission accepts blank barangay', function () {
    Storage::fake('local');
    $type = IncidentType::factory()->create();

    $gpsLog = json_encode([
        [
            'filename' => 'gps-123.jpg',
            'latitude' => 18.472,
            'longitude' => 121.325,
            'accuracy' => 12,
            'captured_at' => now()->toIso8601String(),
        ],
    ]);

    $this->post(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Incident submitted without a barangay value at all.',
        'is_anonymous' => '1',
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
        'meta' => ['gps_captures' => $gpsLog],
        'evidence' => [UploadedFile::fake()->image('gps-123.jpg')],
    ])->assertSessionDoesntHaveErrors('barangay');

    expect(Incident::query()->count())->toBe(1);
});

test('report submission without evidence enters verification queue', function () {
    Storage::fake('local');
    $type = IncidentType::factory()->create();

    $this->post(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Incident submitted without photo evidence for call-back queue.',
        'is_anonymous' => '1',
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
    ])->assertRedirect();

    $incident = Incident::query()->first();
    expect($incident)->not->toBeNull()
        ->and($incident->meta['needs_verification'] ?? false)->toBeTrue()
        ->and($incident->evidence)->toHaveCount(0);
});

test('idempotency key prevents duplicate public reports', function () {
    Storage::fake('local');
    $type = IncidentType::factory()->create();
    $key = 'test-idem-key-001';

    $first = $this->postJson(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'First submission with an idempotency key for retries.',
        'is_anonymous' => true,
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
        'idempotency_key' => $key,
        'evidence' => [UploadedFile::fake()->image('gps-123.jpg')],
        'meta' => ['gps_captures' => json_encode([[
            'filename' => 'gps-123.jpg',
            'latitude' => 18.472,
            'longitude' => 121.325,
            'accuracy' => 12,
            'captured_at' => now()->toIso8601String(),
        ]])],
    ])->assertCreated();

    $second = $this->postJson(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Retry submission with the same idempotency key.',
        'is_anonymous' => true,
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
        'idempotency_key' => $key,
    ])->assertOk();

    expect(Incident::query()->count())->toBe(1)
        ->and($second->json('tracking_number'))->toBe($first->json('tracking_number'))
        ->and($second->json('duplicate'))->toBeTrue();
});

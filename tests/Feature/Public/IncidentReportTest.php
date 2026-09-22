<?php

use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

test('two factor challenge page uses the authentication wording', function () {
    $user = User::factory()->create([
        'email' => 'ops@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'administrator',
    ]);

    session(['pending_2fa_id' => $user->id]);

    $this->get(route('two-factor.challenge'))
        ->assertOk()
        ->assertSee('Two Factor Authentication', false)
        ->assertDontSee('Verify it\'s you', false);
});

test('trusted device survives logout and skips otp on next login', function () {
    Mail::fake();

    $user = User::factory()->administrator()->create([
        'password' => bcrypt('secret123'),
    ]);

    $twoFactor = app(\App\Services\TwoFactorService::class);
    $cookie = $twoFactor->issueTrustedDeviceCookie($user);

    $this->withCookie($cookie->getName(), $cookie->getValue())
        ->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();

    // Cookie must still be present after logout (Facebook-style remember device).
    $this->withCookie($cookie->getName(), $cookie->getValue())
        ->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])
        ->assertRedirect(route('admin.dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    Mail::assertNothingSent();
});

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
    $response->assertRedirect(route('public.report.success', $incident->tracking_number));
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
        ->assertJsonStructure(['tracking_number', 'incident']);
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

    $this->post(route('public.report.store'), [
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

    $this->post(route('public.track.lookup'), [
        'tracking_number' => $trackingNumber,
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

    $this->postJson(route('public.track.lookup'), [
        'tracking_number' => $trackingNumber,
    ])
        ->assertOk()
        ->assertJsonPath('tracking_number', $trackingNumber);
});

test('tracking rejects an unknown tracking number', function () {
    $this->post(route('public.track.lookup'), [
        'tracking_number' => 'RAN-NOPEXX',
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

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
    Storage::fake('public');
    $type = IncidentType::factory()->create();

    $response = $this->post(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Test incident report for RANIAG public module.',
        'barangay' => 'Sample Barangay',
        'is_anonymous' => '1',
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
    ]);

    $incident = Incident::query()->first();

    expect($incident)->not->toBeNull();
    $response->assertRedirect(route('public.report.success', $incident->tracking_number));
});

test('anonymous users can submit an incident report via json', function () {
    $type = IncidentType::factory()->create();

    $response = $this->postJson(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Test incident report for RANIAG foundation.',
        'barangay' => 'Sample Barangay',
        'is_anonymous' => true,
    ]);

    $response
        ->assertCreated()
        ->assertJsonStructure(['tracking_number', 'incident']);
});

test('users can track an incident by tracking number via web', function () {
    $type = IncidentType::factory()->create();

    $this->post(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Trackable incident report with enough detail.',
        'is_anonymous' => '1',
    ]);

    $trackingNumber = Incident::query()->value('tracking_number');

    $this->post(route('public.track.lookup'), [
        'tracking_number' => $trackingNumber,
    ])
        ->assertOk()
        ->assertSee($trackingNumber, false)
        ->assertSee('Status Timeline', false);
});

test('users can track an incident by tracking number via json', function () {
    $type = IncidentType::factory()->create();

    $submit = $this->postJson(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Trackable incident report.',
        'is_anonymous' => true,
    ]);

    $trackingNumber = $submit->json('tracking_number');

    $this->postJson(route('public.track.lookup'), [
        'tracking_number' => $trackingNumber,
    ])
        ->assertOk()
        ->assertJsonPath('tracking_number', $trackingNumber);
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

    $this->post(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Incident with GPS camera metadata attached.',
        'is_anonymous' => '1',
        'latitude' => '18.47200000',
        'longitude' => '121.32500000',
        'meta' => ['gps_captures' => $gpsLog],
    ])->assertRedirect();

    $incident = Incident::query()->first();

    expect($incident->meta['gps_captures'])->toBeArray()
        ->and($incident->meta['gps_captures'][0]['filename'])->toBe('gps-123.jpg');
});

test('report submission accepts optional evidence files', function () {
    Storage::fake('public');
    $type = IncidentType::factory()->create();

    $this->post(route('public.report.store'), [
        'incident_type_id' => $type->id,
        'description' => 'Incident with photo evidence attached for review.',
        'is_anonymous' => '1',
        'evidence' => [
            UploadedFile::fake()->create('scene.pdf', 100, 'application/pdf'),
        ],
    ])->assertRedirect();

    $incident = Incident::query()->with('evidence')->first();

    expect($incident->evidence)->toHaveCount(1);
    Storage::disk('public')->assertExists($incident->evidence->first()->file_path);
});

<?php

use Illuminate\Support\Facades\Http;

test('public hazard map page renders live risk chrome', function () {
    $this->get(route('public.hazard.map'))
        ->assertOk()
        ->assertSee('What is happening in Pamplona', false)
        ->assertSee('Open reports inside Pamplona are pins at the reported spot.', false)
        ->assertSee('Names and contact details stay private.', false)
        ->assertSee('for="layer-risk">Open reports', false)
        ->assertDontSee('Barangay risk', false)
        ->assertDontSee('Exact report locations stay private', false)
        ->assertSee('id="layer-geofence"', false)
        ->assertSee('Pamplona AOR', false)
        ->assertSee('class="rg-hazard-tabs" role="tablist"', false)
        ->assertSee('class="rg-hazard-tab is-active" data-hazard-tab="risk"', false)
        ->assertSee('id="hazard-map"', false)
        ->assertSee('id="hazard-report-modal"', false)
        ->assertSee('id="hazard-report-picks"', false)
        ->assertSee('class="modal-dialog rg-report-sheet"', false)
        ->assertSee('aria-label="Close"', false)
        ->assertSee('viewport-fit=cover', false)
        ->assertSee('id="route-targets"', false)
        ->assertSee('The path can switch when more than one shelter is open.', false)
        ->assertSee('data-lenis-prevent', false)
        ->assertSee('.rg-hazard-nearest-btn strong { color: #f0f5f6; }', false);
});

test('outside-area GPS reverse geocoding returns barangay municipality and country details', function () {
    Http::fake([
        'https://nominatim.openstreetmap.org/reverse*' => Http::response([
            'address' => [
                'village' => 'Langagan',
                'county' => 'Sanchez Mira',
                'state' => 'Cagayan',
                'country' => 'Philippines',
            ],
        ]),
    ]);

    $this->getJson(route('public.hazard.barangay', [
        'lat' => 18.5476385,
        'lng' => 121.2481054,
    ]))
        ->assertOk()
        ->assertJsonPath('inside', false)
        ->assertJsonPath('barangay', 'Langagan')
        ->assertJsonPath('municipality', 'Sanchez Mira')
        ->assertJsonPath('province', 'Cagayan')
        ->assertJsonPath('country', 'Philippines');
});

test('nearest center lists every open evacuation center by distance', function () {
    \App\Models\EvacuationCenter::query()->create([
        'name' => 'Far Shelter',
        'latitude' => 18.60,
        'longitude' => 121.50,
        'is_open' => true,
    ]);
    \App\Models\EvacuationCenter::query()->create([
        'name' => 'Near Shelter',
        'latitude' => 18.451,
        'longitude' => 121.341,
        'is_open' => true,
    ]);
    \App\Models\EvacuationCenter::query()->create([
        'name' => 'Closed Shelter',
        'latitude' => 18.450,
        'longitude' => 121.340,
        'is_open' => false,
    ]);

    $this->getJson(route('public.hazard.nearest', ['lat' => 18.45, 'lng' => 121.34]))
        ->assertOk()
        ->assertJsonPath('nearest_center.name', 'Near Shelter')
        ->assertJsonCount(2, 'open_centers')
        ->assertJsonPath('open_centers.0.name', 'Near Shelter')
        ->assertJsonPath('open_centers.1.name', 'Far Shelter')
        ->assertJsonMissing(['name' => 'Closed Shelter']);
});

test('public hazard snapshot returns zones centers and risk without incident coordinates', function () {
    $response = $this->getJson(route('public.hazard.snapshot'))
        ->assertOk()
        ->assertJsonStructure([
            'zones',
            'centers',
            'risk' => ['barangays', 'total_open'],
            'open_reports',
            'updated_at',
        ]);

    $payload = $response->json();
    $encoded = json_encode($payload['risk']);
    expect($encoded)->not->toContain('"latitude"');
    expect($encoded)->not->toContain('"longitude"');
});

test('public live map pins open reports inside Pamplona without personal details', function () {
    \Illuminate\Support\Facades\Storage::fake('local');

    $type = \App\Models\IncidentType::factory()->create([
        'name' => 'Fire',
        'icon' => 'bi-fire',
        'color' => '#dc2626',
    ]);
    $staff = \App\Models\User::factory()->create([
        'role' => 'administrator',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $incident = \App\Models\Incident::query()->create([
        'tracking_number' => 'RAN-PIN-0001',
        'tracking_pin' => \Illuminate\Support\Facades\Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => \App\Enums\IncidentStatus::Submitted,
        'priority' => 'medium',
        'description' => 'PRIVATE-YARD-NOTE-DO-NOT-PUBLISH',
        'reporter_name' => 'Juan Dela Cruz Private',
        'reporter_phone' => '09170001111',
        'barangay' => 'Centro',
        'latitude' => 18.472,
        'longitude' => 121.325,
        'is_anonymous' => false,
        'reported_at' => now(),
    ]);

    $photoPath = 'incidents/'.$incident->id.'/evidence/gps.jpg';
    $videoPath = 'incidents/'.$incident->id.'/evidence/gps.mp4';
    $staffPath = 'incidents/'.$incident->id.'/evidence/staff.jpg';
    $plainPath = 'incidents/'.$incident->id.'/evidence/plain.jpg';
    \Illuminate\Support\Facades\Storage::disk('local')->put($photoPath, 'fake-image-bytes');
    \Illuminate\Support\Facades\Storage::disk('local')->put($videoPath, 'fake-video-bytes');
    \Illuminate\Support\Facades\Storage::disk('local')->put($staffPath, 'staff-only-bytes');
    \Illuminate\Support\Facades\Storage::disk('local')->put($plainPath, 'plain-photo-bytes');

    $photo = \App\Models\Evidence::query()->create([
        'incident_id' => $incident->id,
        'type' => 'photo',
        'file_path' => $photoPath,
        'original_filename' => 'secret-name.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 16,
        'is_gps_capture' => true,
    ]);
    $video = \App\Models\Evidence::query()->create([
        'incident_id' => $incident->id,
        'type' => 'video',
        'file_path' => $videoPath,
        'original_filename' => 'secret-clip.mp4',
        'mime_type' => 'video/mp4',
        'file_size' => 16,
        'is_gps_capture' => true,
    ]);
    $staffFile = \App\Models\Evidence::query()->create([
        'incident_id' => $incident->id,
        'uploaded_by' => $staff->id,
        'type' => 'photo',
        'file_path' => $staffPath,
        'original_filename' => 'staff-note.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 16,
        'is_gps_capture' => true,
    ]);
    $plain = \App\Models\Evidence::query()->create([
        'incident_id' => $incident->id,
        'type' => 'photo',
        'file_path' => $plainPath,
        'original_filename' => 'gallery.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 16,
        'is_gps_capture' => false,
    ]);

    \App\Models\Incident::query()->create([
        'tracking_number' => 'RAN-NOGPS-0001',
        'tracking_pin' => \Illuminate\Support\Facades\Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => \App\Enums\IncidentStatus::Submitted,
        'priority' => 'low',
        'description' => 'No coordinates.',
        'barangay' => 'Centro',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    $resolved = \App\Models\Incident::query()->create([
        'tracking_number' => 'RAN-DONE-0001',
        'tracking_pin' => \Illuminate\Support\Facades\Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => \App\Enums\IncidentStatus::Resolved,
        'priority' => 'medium',
        'description' => 'Already resolved.',
        'barangay' => 'Centro',
        'latitude' => 18.48,
        'longitude' => 121.33,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    $resolvedPath = 'incidents/'.$resolved->id.'/evidence/old.jpg';
    \Illuminate\Support\Facades\Storage::disk('local')->put($resolvedPath, 'old-image-bytes');
    $resolvedFile = \App\Models\Evidence::query()->create([
        'incident_id' => $resolved->id,
        'type' => 'photo',
        'file_path' => $resolvedPath,
        'original_filename' => 'old.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 16,
        'is_gps_capture' => true,
    ]);
    \App\Models\Incident::query()->create([
        'tracking_number' => 'RAN-OUT-0001',
        'tracking_pin' => \Illuminate\Support\Facades\Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => \App\Enums\IncidentStatus::Submitted,
        'priority' => 'medium',
        'description' => 'Outside Pamplona.',
        'barangay' => 'Langagan',
        'latitude' => 18.55,
        'longitude' => 121.25,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    \App\Models\Incident::query()->create([
        'tracking_number' => 'RAN-AOR-0001',
        'tracking_pin' => \Illuminate\Support\Facades\Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => \App\Enums\IncidentStatus::Submitted,
        'priority' => 'medium',
        'description' => 'Marked outside the area.',
        'barangay' => 'Centro',
        'latitude' => 18.49,
        'longitude' => 121.34,
        'meta' => ['within_jurisdiction' => false],
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    $response = $this->getJson(route('public.hazard.snapshot'))->assertOk();
    $payload = $response->json();
    $encoded = json_encode($payload);

    expect($payload['open_reports'])->toHaveCount(1)
        ->and($payload['open_reports'][0]['tracking_number'])->toBe('RAN-PIN-0001')
        ->and($payload['open_reports'][0]['type'])->toBe('Fire')
        ->and($payload['open_reports'][0]['status'])->toBe('Submitted')
        ->and($payload['open_reports'][0]['barangay'])->toBe('Centro')
        ->and($payload['open_reports'][0]['latitude'])->toBe(18.472)
        ->and($payload['open_reports'][0]['longitude'])->toBe(121.325)
        ->and($payload['open_reports'][0]['media'])->toHaveCount(2)
        ->and($payload['open_reports'][0]['media'][0]['kind'])->toBe('image')
        ->and($payload['open_reports'][0]['media'][1]['kind'])->toBe('video')
        ->and($payload['risk']['total_open'])->toBe(2)
        ->and($encoded)->not->toContain('PRIVATE-YARD-NOTE-DO-NOT-PUBLISH')
        ->and($encoded)->not->toContain('Juan Dela Cruz Private')
        ->and($encoded)->not->toContain('09170001111')
        ->and($encoded)->not->toContain('secret-name.jpg')
        ->and($encoded)->not->toContain('RAN-NOGPS-0001')
        ->and($encoded)->not->toContain('RAN-DONE-0001')
        ->and($encoded)->not->toContain('RAN-OUT-0001')
        ->and($encoded)->not->toContain('RAN-AOR-0001')
        ->and(json_encode($payload['risk']))->not->toContain('"latitude"');

    $this->get(route('public.hazard.report-media', $photo))
        ->assertOk()
        ->assertHeader('content-disposition', 'inline; filename="gps-photo.jpg"');
    $this->get(route('public.hazard.report-media', $video))
        ->assertOk()
        ->assertHeader('content-disposition', 'inline; filename="gps-video.mp4"');
    $this->get(route('public.hazard.report-media', $staffFile))->assertNotFound();
    $this->get(route('public.hazard.report-media', $plain))->assertNotFound();
    $this->get(route('public.hazard.report-media', $resolvedFile))->assertNotFound();
});

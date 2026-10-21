<?php

use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Assignment;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use App\Services\SituationalMapService;
use Illuminate\Support\Facades\Hash;

test('track live units requires verified track session', function () {
    $type = IncidentType::factory()->create();
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-LIVE-0001',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Assigned,
        'priority' => 'medium',
        'description' => 'Live units gate test.',
        'latitude' => 18.47,
        'longitude' => 121.32,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    $this->getJson(route('public.track.units', $incident->tracking_number))
        ->assertForbidden();
});

test('track live units returns public-safe labels after lookup', function () {
    $type = IncidentType::factory()->create();
    $agency = Agency::query()->create([
        'name' => 'BFP Pamplona',
        'code' => 'BFPP',
        'is_active' => true,
    ]);
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-LIVE-0002',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::InProgress,
        'priority' => 'high',
        'description' => 'Live units payload test.',
        'latitude' => 18.47,
        'longitude' => 121.32,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    User::factory()->create([
        'role' => UserRole::Agency,
        'agency_id' => $agency->id,
        'last_lat' => 18.471,
        'last_lng' => 121.321,
        'last_location_at' => now(),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    Assignment::query()->create([
        'incident_id' => $incident->id,
        'agency_id' => $agency->id,
        'assigned_by' => User::factory()->create([
            'role' => UserRole::Administrator,
            'is_active' => true,
            'email_verified_at' => now(),
        ])->id,
        'is_active' => true,
        'field_phase' => 'en_route',
        'assigned_at' => now(),
    ]);

    $this->withSession(['track_verified.'.$incident->id => true])
        ->getJson(route('public.track.units', $incident->tracking_number))
        ->assertOk()
        ->assertJsonStructure(['units', 'scene', 'updated_at']);

    $units = app(SituationalMapService::class)->liveUnitsForIncident($incident, forPublic: true);
    expect($units)->not->toBeEmpty();
    expect($units[0]['label'])->toBe('BFP Pamplona');
});

test('admin live units shows the agency account and personnel who are sharing location', function () {
    $type = IncidentType::factory()->create();
    $agency = Agency::query()->create([
        'name' => 'Pamplona Rescue',
        'code' => 'PRES',
        'is_active' => true,
    ]);
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-LIVE-0003',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::InProgress,
        'priority' => 'high',
        'description' => 'Staff dispatch feed.',
        'barangay' => 'Bidduang',
        'latitude' => 18.51,
        'longitude' => 121.32,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    $admin = User::factory()->create([
        'role' => UserRole::Administrator,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    User::factory()->create([
        'name' => 'Agency Desk',
        'role' => UserRole::Agency,
        'agency_id' => $agency->id,
        'last_lat' => 18.505,
        'last_lng' => 121.318,
        'last_location_at' => now(),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    User::factory()->create([
        'name' => 'Field Responder',
        'role' => UserRole::Personnel,
        'agency_id' => $agency->id,
        'last_lat' => 18.508,
        'last_lng' => 121.322,
        'last_location_at' => now(),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    Assignment::query()->create([
        'incident_id' => $incident->id,
        'agency_id' => $agency->id,
        'assigned_by' => $admin->id,
        'is_active' => true,
        'field_phase' => 'en_route',
        'assigned_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.incidents.live_units', $incident))
        ->assertOk()
        ->assertJsonPath('assigned.0.label', 'Pamplona Rescue');

    $labels = collect($response->json('units'))->pluck('label')->all();
    expect($labels)->toContain('Pamplona Rescue — Agency Desk');
    expect($labels)->toContain('Pamplona Rescue — Field Responder');
});

test('agency situational map excludes outside jurisdiction reports while keeping them in dispatches', function () {
    $agency = Agency::query()->create([
        'name' => 'Pamplona Response',
        'code' => 'PRSP',
        'is_active' => true,
    ]);
    $agencyUser = User::factory()->agency()->create(['agency_id' => $agency->id]);
    $administrator = User::factory()->administrator()->create();
    $type = IncidentType::factory()->create();

    foreach ([
        ['RAN-MAP-INSIDE', 'Bidduang', ['within_jurisdiction' => true]],
        ['RAN-MAP-OUTSIDE', 'Sanchez Mira', ['within_jurisdiction' => false]],
    ] as [$trackingNumber, $barangay, $meta]) {
        $incident = Incident::query()->create([
            'tracking_number' => $trackingNumber,
            'tracking_pin' => Hash::make('111111'),
            'incident_type_id' => $type->id,
            'status' => IncidentStatus::InProgress,
            'priority' => 'high',
            'description' => 'Situational map jurisdiction test.',
            'barangay' => $barangay,
            'latitude' => 18.51,
            'longitude' => 121.32,
            'meta' => $meta,
            'is_anonymous' => true,
            'reported_at' => now(),
        ]);
        Assignment::query()->create([
            'incident_id' => $incident->id,
            'agency_id' => $agency->id,
            'assigned_by' => $administrator->id,
            'is_active' => true,
            'assigned_at' => now(),
        ]);
    }

    $this->actingAs($agencyUser)
        ->getJson(route('agency.dashboard.api'))
        ->assertOk()
        ->assertJsonCount(1, 'situational_incidents')
        ->assertJsonPath('situational_incidents.0.tracking_number', 'RAN-MAP-INSIDE')
        ->assertJsonCount(2, 'active_dispatches');
});

test('agency location sharing routes the live responder pin to agency admin and public case maps', function () {
    $type = IncidentType::factory()->create();
    $agency = Agency::query()->create([
        'name' => 'Pamplona Response',
        'code' => 'PRES',
        'is_active' => true,
    ]);
    $agencyUser = User::factory()->create([
        'name' => 'Agency Dispatcher',
        'role' => UserRole::Agency,
        'agency_id' => $agency->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $administrator = User::factory()->create([
        'role' => UserRole::Administrator,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-LIVE-0004',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::InProgress,
        'priority' => 'high',
        'description' => 'End-to-end dispatch map test.',
        'barangay' => 'Bidduang',
        'latitude' => 18.51,
        'longitude' => 121.32,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    Assignment::query()->create([
        'incident_id' => $incident->id,
        'agency_id' => $agency->id,
        'assigned_by' => $administrator->id,
        'is_active' => true,
        'acknowledged_at' => now(),
        'field_phase' => 'accepted',
        'assigned_at' => now(),
    ]);

    $this->actingAs($agencyUser)
        ->get(route('agency.incidents.show', $incident))
        ->assertOk()
        ->assertSee('id="agency-incident-map"', false)
        ->assertSee('Notes for MDRRMO and the responding team. This is not shown on the public tracking page.', false)
        ->assertDontSee('Type comments visible to public/admin', false)
        ->assertSee('js/raniag-dispatch-map.js', false)
        ->assertSee('js/raniag-location-ping.js', false);

    $this->actingAs($agencyUser)
        ->postJson(route('agency.location_ping'), ['lat' => 18.508, 'lng' => 121.318])
        ->assertOk()
        ->assertJsonPath('ok', true);

    $this->postJson(route('agency.incidents.field_phase', $incident), ['field_phase' => 'en_route'])
        ->assertOk()
        ->assertJsonPath('field_phase', 'en_route');

    $agencyResponse = $this->getJson(route('agency.incidents.live_units', $incident))
        ->assertOk()
        ->assertJsonPath('units.0.latitude', 18.508)
        ->assertJsonPath('units.0.longitude', 121.318)
        ->assertJsonPath('units.0.field_phase', 'en_route');
    expect($agencyResponse->json('units.0.label'))->toBe('Pamplona Response — Agency Dispatcher');

    $this->actingAs($administrator)
        ->get(route('admin.incidents.show', $incident))
        ->assertOk()
        ->assertSee('rg-admin-case-layout', false)
        ->assertSee('id="case-situation"', false)
        ->assertSee('id="show-incident-map"', false)
        ->assertSee('live-units', false);

    $adminResponse = $this->getJson(route('admin.incidents.live_units', $incident))
        ->assertOk()
        ->assertJsonPath('units.0.latitude', 18.508)
        ->assertJsonPath('assigned.0.label', 'Pamplona Response');
    expect($adminResponse->json('units.0.label'))->toBe('Pamplona Response — Agency Dispatcher');

    $this->withSession(['track_verified.'.$incident->id => true])
        ->get(route('public.track.case', $incident->tracking_number))
        ->assertOk()
        ->assertSee('id="track-live-map"', false)
        ->assertSee('js/public-track-map.js', false)
        ->assertSee('js/incident-map-icons.js', false)
        ->assertSee('icon: '.json_encode($type->icon), false)
        ->assertSee('id="rg-track-status" data-live-refresh', false)
        ->assertSee('data-live-refresh-target="#case-assignment-status"', false)
        ->assertSee('data-live-refresh-target="#case-history-content"', false)
        ->assertSee('data-live-refresh-interval="4000"', false)
        ->assertSee('pollMs: 3000', false);

    $this->withSession(['track_verified.'.$incident->id => true])
        ->getJson(route('public.track.units', $incident->tracking_number))
        ->assertOk()
        ->assertJsonPath('units.0.latitude', 18.508)
        ->assertJsonPath('units.0.field_phase', 'en_route')
        ->assertJsonPath('units.0.label', 'Pamplona Response');
});

test('a responder location is not drawn on a case that was not marked en route', function () {
    $type = IncidentType::factory()->create();
    $agency = Agency::query()->create([
        'name' => 'Bureau of Fire Protection',
        'code' => 'BFPX',
        'is_active' => true,
    ]);
    $administrator = User::factory()->create([
        'role' => UserRole::Administrator,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    User::factory()->create([
        'name' => 'Fire Inspector',
        'role' => UserRole::Agency,
        'agency_id' => $agency->id,
        'last_lat' => 18.55,
        'last_lng' => 121.36,
        'last_location_at' => now(),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $moving = Incident::query()->create([
        'tracking_number' => 'RAN-LIVE-MOVE',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::InProgress,
        'priority' => 'high',
        'description' => 'The case the responder actually marked en route.',
        'latitude' => 18.47,
        'longitude' => 121.32,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    $waiting = Incident::query()->create([
        'tracking_number' => 'RAN-LIVE-WAIT',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Assigned,
        'priority' => 'high',
        'description' => 'Assigned, but not accepted or en route.',
        'latitude' => 18.46,
        'longitude' => 121.34,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    Assignment::query()->create([
        'incident_id' => $moving->id,
        'agency_id' => $agency->id,
        'assigned_by' => $administrator->id,
        'is_active' => true,
        'field_phase' => 'en_route',
        'assigned_at' => now(),
    ]);
    Assignment::query()->create([
        'incident_id' => $waiting->id,
        'agency_id' => $agency->id,
        'assigned_by' => $administrator->id,
        'is_active' => true,
        'field_phase' => null,
        'assigned_at' => now(),
    ]);

    $service = app(SituationalMapService::class);

    expect($service->liveUnitsForIncident($moving))->not->toBeEmpty();
    expect($service->liveUnitsForIncident($waiting))->toBeEmpty();
    expect($service->liveUnitsForIncident($waiting, forPublic: true))->toBeEmpty();
});

test('a moving en route or on scene fix updates every live monitoring map', function () {
    $type = IncidentType::factory()->create();
    $agency = Agency::query()->create([
        'name' => 'Bureau of Fire Protection',
        'code' => 'BFPM',
        'is_active' => true,
    ]);
    $administrator = User::factory()->create([
        'role' => UserRole::Administrator,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $agencyUser = User::factory()->create([
        'name' => 'Desk Officer',
        'role' => UserRole::Agency,
        'agency_id' => $agency->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $personnel = User::factory()->create([
        'name' => 'Field Responder',
        'role' => UserRole::Personnel,
        'agency_id' => $agency->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $agencyCase = Incident::query()->create([
        'tracking_number' => 'RAN-MOVE-AGY',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::InProgress,
        'priority' => 'high',
        'description' => 'Agency movement feed.',
        'latitude' => 18.50,
        'longitude' => 121.30,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    $personnelCase = Incident::query()->create([
        'tracking_number' => 'RAN-MOVE-PER',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::InProgress,
        'priority' => 'high',
        'description' => 'Personnel movement feed.',
        'latitude' => 18.48,
        'longitude' => 121.31,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    Assignment::query()->create([
        'incident_id' => $agencyCase->id,
        'agency_id' => $agency->id,
        'assigned_by' => $administrator->id,
        'is_active' => true,
        'acknowledged_at' => now(),
        'field_phase' => 'en_route',
        'assigned_at' => now(),
    ]);
    Assignment::query()->create([
        'incident_id' => $personnelCase->id,
        'assigned_to' => $personnel->id,
        'assigned_by' => $administrator->id,
        'is_active' => true,
        'acknowledged_at' => now(),
        'field_phase' => 'on_scene',
        'assigned_at' => now(),
    ]);

    $this->actingAs($personnel)
        ->get(route('personnel.incidents.show', $personnelCase))
        ->assertOk()
        ->assertSee('id="personnel-incident-map"', false)
        ->assertSee('js/raniag-dispatch-map.js', false)
        ->assertSee('js/raniag-location-ping.js', false)
        ->assertSee('unitsUrl', false)
        ->assertSee('pollMs: 5000', false);

    $this->actingAs($agencyUser)
        ->postJson(route('agency.location_ping'), ['lat' => 18.501, 'lng' => 121.301])
        ->assertOk();

    $this->actingAs($agencyUser)->getJson(route('agency.incidents.live_units', $agencyCase))
        ->assertOk()
        ->assertJsonPath('units.0.latitude', 18.501);
    $this->actingAs($administrator)->getJson(route('admin.incidents.live_units', $agencyCase))
        ->assertOk()
        ->assertJsonPath('units.0.latitude', 18.501);
    $this->withSession(['track_verified.'.$agencyCase->id => true])
        ->getJson(route('public.track.units', $agencyCase->tracking_number))
        ->assertOk()
        ->assertJsonPath('units.0.latitude', 18.501);

    $this->actingAs($agencyUser)
        ->postJson(route('agency.location_ping'), ['lat' => 18.529, 'lng' => 121.348])
        ->assertOk();

    $this->actingAs($agencyUser)->getJson(route('agency.incidents.live_units', $agencyCase))
        ->assertOk()
        ->assertJsonPath('units.0.latitude', 18.529)
        ->assertJsonPath('units.0.longitude', 121.348);
    $this->actingAs($administrator)->getJson(route('admin.incidents.live_units', $agencyCase))
        ->assertOk()
        ->assertJsonPath('units.0.latitude', 18.529)
        ->assertJsonPath('units.0.longitude', 121.348);
    $this->withSession(['track_verified.'.$agencyCase->id => true])
        ->getJson(route('public.track.units', $agencyCase->tracking_number))
        ->assertOk()
        ->assertJsonPath('units.0.latitude', 18.529)
        ->assertJsonPath('units.0.longitude', 121.348);

    $this->actingAs($personnel)
        ->postJson(route('personnel.location_ping'), ['lat' => 18.481, 'lng' => 121.311])
        ->assertOk();
    $this->actingAs($personnel)->getJson(route('personnel.incidents.live_units', $personnelCase))
        ->assertOk()
        ->assertJsonPath('units.0.latitude', 18.481)
        ->assertJsonPath('units.0.field_phase', 'on_scene');
    $this->actingAs($administrator)->getJson(route('admin.incidents.live_units', $personnelCase))
        ->assertOk()
        ->assertJsonPath('units.0.latitude', 18.481);

    $this->actingAs($personnel)
        ->postJson(route('personnel.location_ping'), ['lat' => 18.486, 'lng' => 121.319])
        ->assertOk();
    $this->actingAs($personnel)->getJson(route('personnel.incidents.live_units', $personnelCase))
        ->assertOk()
        ->assertJsonPath('units.0.latitude', 18.486)
        ->assertJsonPath('units.0.longitude', 121.319);
    $this->actingAs($administrator)->getJson(route('admin.incidents.live_units', $personnelCase))
        ->assertOk()
        ->assertJsonPath('units.0.latitude', 18.486)
        ->assertJsonPath('units.0.longitude', 121.319);
});

test('investigation notes stay with staff and off the public tracking page', function () {
    $type = IncidentType::factory()->create();
    $agency = Agency::query()->create([
        'name' => 'Bureau of Fire Protection',
        'code' => 'BFPN',
        'is_active' => true,
    ]);
    $agencyUser = User::factory()->create([
        'name' => 'Fire Inspector',
        'role' => UserRole::Agency,
        'agency_id' => $agency->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $administrator = User::factory()->create([
        'role' => UserRole::Administrator,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-NOTE-0001',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::InProgress,
        'priority' => 'high',
        'description' => 'Staff note visibility test.',
        'latitude' => 18.47,
        'longitude' => 121.32,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    Assignment::query()->create([
        'incident_id' => $incident->id,
        'agency_id' => $agency->id,
        'assigned_by' => $administrator->id,
        'is_active' => true,
        'acknowledged_at' => now(),
        'field_phase' => 'accepted',
        'assigned_at' => now(),
    ]);

    $this->actingAs($agencyUser)
        ->patch(route('agency.incidents.update_status', $incident), [
            'status' => IncidentStatus::InProgress->value,
            'comment' => 'Internal yard check',
        ])
        ->assertRedirect();

    $note = $incident->statusUpdates()->where('comment', 'Internal yard check')->first();
    expect($note)->not->toBeNull()
        ->and($note->is_public)->toBeFalse();

    $this->withSession(['track_verified.'.$incident->id => true])
        ->get(route('public.track.case', $incident->tracking_number))
        ->assertOk()
        ->assertDontSee('Internal yard check');
});

test('situational public risk awareness has no exact coordinates', function () {
    $type = IncidentType::factory()->create();
    Incident::query()->create([
        'tracking_number' => 'RAN-RISK-0001',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Submitted,
        'priority' => 'medium',
        'description' => 'Risk awareness aggregation test.',
        'barangay' => 'Centro',
        'latitude' => 18.472,
        'longitude' => 121.325,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    $risk = app(SituationalMapService::class)->publicRiskAwareness();
    expect($risk['total_open'])->toBeGreaterThan(0);
    $json = json_encode($risk);
    expect($json)->not->toContain('"latitude"');
    expect($json)->not->toContain('"longitude"');
});

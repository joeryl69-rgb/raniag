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

<?php

use App\Enums\IncidentStatus;
use App\Models\EvacuationCenter;
use App\Models\Incident;
use App\Models\IncidentType;
use Illuminate\Support\Facades\Hash;

test('community dashboard only counts incidents inside Pamplona', function () {
    $type = IncidentType::factory()->create(['name' => 'Fire']);
    $base = [
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Submitted,
        'priority' => 'high',
        'description' => 'Community dashboard scope.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ];

    Incident::query()->create($base + [
        'tracking_number' => 'RAN-COM-0001',
        'barangay' => 'Bidduang',
    ]);
    Incident::query()->create($base + [
        'tracking_number' => 'RAN-COM-0002',
        'barangay' => 'Langagan',
    ]);

    $response = $this->getJson(route('public.dashboard.data', ['period' => 'week']))
        ->assertOk()
        ->assertJsonPath('period.key', 'week')
        ->assertJsonPath('period.caption', 'Last 8 weeks')
        ->assertJsonPath('total_all_time', 1)
        ->assertJsonPath('most_active.barangay', 'Bidduang')
        ->assertJsonPath('most_active.type', 'Fire')
        ->assertJsonPath('barangay_counts.0.barangay', 'Bidduang');

    expect(collect($response->json('recent_activity'))->pluck('barangay'))
        ->not->toContain('Langagan');
});

test('community dashboard exposes evacuation-center locations and open status only', function () {
    EvacuationCenter::query()->create([
        'name' => 'Centro Open Shelter',
        'barangay' => 'Centro',
        'address' => 'Municipal Hall, Centro',
        'latitude' => 18.451,
        'longitude' => 121.341,
        'is_open' => true,
        'notes' => 'Staff-only note',
    ]);
    EvacuationCenter::query()->create([
        'name' => 'Closed School Shelter',
        'barangay' => 'Abulig',
        'address' => 'Abulig Elementary School',
        'latitude' => 18.462,
        'longitude' => 121.352,
        'is_open' => false,
        'notes' => 'Another staff-only note',
    ]);

    $response = $this->getJson(route('public.dashboard.data'))
        ->assertOk()
        ->assertJsonCount(2, 'evacuation_centers')
        ->assertJsonPath('evacuation_centers.0.name', 'Centro Open Shelter')
        ->assertJsonPath('evacuation_centers.0.is_open', true)
        ->assertJsonPath('evacuation_centers.0.address', 'Municipal Hall, Centro')
        ->assertJsonPath('evacuation_centers.1.name', 'Closed School Shelter')
        ->assertJsonPath('evacuation_centers.1.is_open', false);

    expect($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and(json_encode($response->json('evacuation_centers')))->not->toContain('Staff-only note');
});

test('community dashboard renders a status-aware evacuation-center map', function () {
    $this->get(route('public.dashboard'))
        ->assertOk()
        ->assertSee('id="community-center-map"', false)
        ->assertSee('id="pd-centers-list"', false)
        ->assertSee('Evacuation centers', false)
        ->assertSee('public-community-centers.js', false)
        ->assertSee('window.setInterval(load, 30000)', false);
});

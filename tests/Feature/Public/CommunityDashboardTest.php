<?php

use App\Enums\IncidentStatus;
use App\Models\EvacuationCenter;
use App\Models\Evacuee;
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

test('community dashboard exposes public shelter availability and internal route links only', function () {
    $open = EvacuationCenter::query()->create([
        'name' => 'Centro Open Shelter',
        'barangay' => 'Centro',
        'address' => 'Municipal Hall, Centro',
        'latitude' => 18.451,
        'longitude' => 121.341,
        'capacity' => 200,
        'is_open' => true,
        'color' => '#a855f7',
        'notes' => 'Staff-only note',
    ]);
    Evacuee::query()->create([
        'evacuation_center_id' => $open->id,
        'full_name' => 'Active occupant',
        'checked_in_at' => now(),
    ]);
    Evacuee::query()->create([
        'evacuation_center_id' => $open->id,
        'full_name' => 'Checked out occupant',
        'checked_in_at' => now()->subHour(),
        'checked_out_at' => now(),
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
        ->assertJsonPath('evacuation_centers.0.color', '#a855f7')
        ->assertJsonPath('evacuation_centers.0.capacity', 200)
        ->assertJsonPath('evacuation_centers.0.checked_in_count', 1)
        ->assertJsonPath('evacuation_centers.0.route_url', route('public.hazard.map', ['route_to_center' => $open->id], false))
        ->assertJsonPath('evacuation_centers.1.name', 'Closed School Shelter')
        ->assertJsonPath('evacuation_centers.1.is_open', false)
        ->assertJsonPath('evacuation_centers.1.route_url', null);

    expect($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and(json_encode($response->json('evacuation_centers')))->not->toContain('Staff-only note')
        ->and(json_encode($response->json('evacuation_centers')))->not->toContain('Active occupant');
});

test('community dashboard renders a status-aware evacuation-center map', function () {
    $this->get(route('public.dashboard'))
        ->assertOk()
        ->assertSee('id="community-center-map"', false)
        ->assertSee('id="pd-centers-list"', false)
        ->assertSee('Evacuation centers', false)
        ->assertSee('public-community-centers.js', false)
        ->assertSeeInOrder([
            'id="pd-most-active"',
            'id="pd-centers-title"',
            'class="rg-community-grid"',
        ], false)
        ->assertSee('window.setInterval(load, 30000)', false);
});

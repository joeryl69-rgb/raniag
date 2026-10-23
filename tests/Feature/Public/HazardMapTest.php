<?php

use Illuminate\Support\Facades\Http;

test('public hazard map page renders live risk chrome', function () {
    $this->get(route('public.hazard.map'))
        ->assertOk()
        ->assertSee('What is happening in Pamplona', false)
        ->assertSee('Barangay risk', false)
        ->assertSee('id="layer-geofence"', false)
        ->assertSee('Pamplona AOR', false)
        ->assertSee('class="rg-hazard-tabs" role="tablist"', false)
        ->assertSee('class="rg-hazard-tab is-active" data-hazard-tab="risk"', false)
        ->assertSee('id="hazard-map"', false)
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
            'updated_at',
        ]);

    $payload = $response->json();
    $encoded = json_encode($payload['risk']);
    expect($encoded)->not->toContain('"latitude"');
    expect($encoded)->not->toContain('"longitude"');
});

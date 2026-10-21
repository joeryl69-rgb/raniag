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

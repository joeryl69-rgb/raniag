<?php

test('public hazard map page renders live risk chrome', function () {
    $this->get(route('public.hazard.map'))
        ->assertOk()
        ->assertSee('Hazard &amp; risk map', false)
        ->assertSee('Risk awareness', false)
        ->assertSee('id="hazard-map"', false)
        ->assertSee('data-lenis-prevent', false);
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

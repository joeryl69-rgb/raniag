<?php

test('public hazard map page renders live chrome', function () {
    $this->get(route('public.hazard.map'))
        ->assertOk()
        ->assertSee('Hazard &amp; evacuation map', false)
        ->assertSee('Live', false)
        ->assertSee('id="hazard-map"', false)
        ->assertSee('data-lenis-prevent', false);
});

test('public hazard snapshot returns json', function () {
    $this->getJson(route('public.hazard.snapshot'))
        ->assertOk()
        ->assertJsonStructure(['zones', 'centers', 'updated_at']);
});

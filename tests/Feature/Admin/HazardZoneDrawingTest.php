<?php

use App\Models\HazardZone;
use App\Models\IncidentType;
use App\Models\User;

test('hazard map creation uses the stepped authoring panel', function () {
    $administrator = User::factory()->administrator()->create();

    $this->actingAs($administrator)
        ->get(route('admin.hazard.index'))
        ->assertOk()
        ->assertSee('Map the boundary')
        ->assertSee('Trace the area, then name it')
        ->assertSee('1 · Type')
        ->assertSee('2 · Place')
        ->assertSee('3 · Boundary')
        ->assertSee('4 · Public map')
        ->assertSee('Click or tap "Click or tap to use the map" before drawing. The boundary tools stay off until the map is on.', false)
        ->assertSee('Map locked')
        ->assertSee('id="map-lock-note"', false)
        ->assertSee('id="tool-polygon"', false)
        ->assertSee('id="tool-box"', false)
        ->assertSee('id="boundary-note"', false)
        ->assertSee('hz-mode-switch', false)
        ->assertSee('id="zone-cancel-edit"', false)
        ->assertSee('Edit hazard area', false)
        ->assertSee('Drag the corners to reshape this area, then save.', false)
        ->assertDontSee('id="editZoneModal"', false);
});

test('hazard area creation rejects incomplete or non polygon geojson', function () {
    $administrator = User::factory()->administrator()->create();
    $incidentType = IncidentType::factory()->create();

    $this->actingAs($administrator)
        ->from(route('admin.hazard.index'))
        ->post(route('admin.hazard.zones.store'), [
            'incident_type_id' => $incidentType->id,
            'name' => 'Unclosed boundary',
            'geometry_json' => json_encode([
                'type' => 'Polygon',
                'coordinates' => [[[121.3, 18.4], [121.4, 18.4], [121.4, 18.5], [121.3, 18.5]]],
            ]),
        ])
        ->assertRedirect(route('admin.hazard.index'))
        ->assertSessionHasErrors('geometry_json');

    expect(HazardZone::query()->count())->toBe(0);
});

test('hazard area creation stores a closed polygon boundary', function () {
    $administrator = User::factory()->administrator()->create();
    $incidentType = IncidentType::factory()->create();
    $geometry = [
        'type' => 'Polygon',
        'coordinates' => [[
            [121.3, 18.4],
            [121.4, 18.4],
            [121.4, 18.5],
            [121.3, 18.5],
            [121.3, 18.4],
        ]],
    ];

    $this->actingAs($administrator)
        ->from(route('admin.hazard.index'))
        ->post(route('admin.hazard.zones.store'), [
            'incident_type_id' => $incidentType->id,
            'name' => 'Test flood boundary',
            'geometry_json' => json_encode($geometry),
        ])
        ->assertRedirect(route('admin.hazard.index'));

    expect(HazardZone::query()->firstOrFail()->geometry)->toBe($geometry);
});

test('an existing hazard area can be renamed without redrawing its boundary', function () {
    $administrator = User::factory()->administrator()->create();
    $incidentType = IncidentType::factory()->create();
    $replacement = IncidentType::factory()->create(['name' => 'Edited flood type']);
    $geometry = [
        'type' => 'Polygon',
        'coordinates' => [[
            [121.3, 18.4],
            [121.4, 18.4],
            [121.4, 18.5],
            [121.3, 18.5],
            [121.3, 18.4],
        ]],
    ];

    $this->actingAs($administrator)
        ->post(route('admin.hazard.zones.store'), [
            'incident_type_id' => $incidentType->id,
            'name' => 'Original area',
            'geometry_json' => json_encode($geometry),
        ]);

    $zone = HazardZone::query()->firstOrFail();

    $this->from(route('admin.hazard.index'))
        ->patch(route('admin.hazard.zones.details', $zone), [
            'incident_type_id' => $replacement->id,
            'name' => 'Renamed area',
            'barangay' => 'Centro',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.hazard.index'));

    $zone->refresh();
    expect($zone->name)->toBe('Renamed area')
        ->and($zone->barangay)->toBe('Centro')
        ->and($zone->geometry)->toBe($geometry)
        ->and($zone->type?->name)->toBe('Edited flood type');
});

test('editing a hazard area saves the boundary drawn on the map', function () {
    $administrator = User::factory()->administrator()->create();
    $incidentType = IncidentType::factory()->create();
    $original = [
        'type' => 'Polygon',
        'coordinates' => [[
            [121.3, 18.4],
            [121.4, 18.4],
            [121.4, 18.5],
            [121.3, 18.5],
            [121.3, 18.4],
        ]],
    ];
    $reshaped = [
        'type' => 'Polygon',
        'coordinates' => [[
            [121.31, 18.41],
            [121.42, 18.41],
            [121.42, 18.52],
            [121.31, 18.52],
            [121.31, 18.41],
        ]],
    ];

    $this->actingAs($administrator)
        ->post(route('admin.hazard.zones.store'), [
            'incident_type_id' => $incidentType->id,
            'name' => 'Riverside',
            'geometry_json' => json_encode($original),
        ]);

    $zone = HazardZone::query()->firstOrFail();

    $this->patch(route('admin.hazard.zones.details', $zone), [
        'incident_type_id' => $incidentType->id,
        'name' => 'Riverside',
        'is_active' => '1',
        'geometry_json' => json_encode($reshaped),
    ])->assertRedirect();

    expect($zone->fresh()->geometry)->toBe($reshaped);
});

test('editing a hazard area closes an open boundary before saving it', function () {
    $administrator = User::factory()->administrator()->create();
    $incidentType = IncidentType::factory()->create();
    $original = [
        'type' => 'Polygon',
        'coordinates' => [[
            [121.3, 18.4],
            [121.4, 18.4],
            [121.4, 18.5],
            [121.3, 18.5],
            [121.3, 18.4],
        ]],
    ];
    $open = [
        'type' => 'Polygon',
        'coordinates' => [[
            [121.31, 18.41],
            [121.42, 18.41],
            [121.36, 18.52],
        ]],
    ];

    $this->actingAs($administrator)
        ->post(route('admin.hazard.zones.store'), [
            'incident_type_id' => $incidentType->id,
            'name' => 'Open edge',
            'geometry_json' => json_encode($original),
        ]);

    $zone = HazardZone::query()->firstOrFail();

    $this->post(route('admin.hazard.zones.details', $zone), [
        'incident_type_id' => $incidentType->id,
        'name' => 'Open edge',
        'is_active' => '1',
        'geometry_json' => json_encode($open),
    ])->assertRedirect();

    $saved = $zone->fresh()->geometry;
    expect($saved['coordinates'][0])->toHaveCount(4)
        ->and($saved['coordinates'][0][0])->toBe($saved['coordinates'][0][3]);
});

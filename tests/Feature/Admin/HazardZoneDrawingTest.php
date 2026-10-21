<?php

use App\Models\HazardZone;
use App\Models\IncidentType;
use App\Models\User;

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

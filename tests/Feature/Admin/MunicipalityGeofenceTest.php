<?php

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\GeofenceService;
use App\Services\IncidentService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function municipalityGeofenceSquare(float $west, float $south, float $east, float $north): array
{
    return [
        'type' => 'Polygon',
        'coordinates' => [[
            [$west, $south],
            [$west, $north],
            [$east, $north],
            [$east, $south],
            [$west, $south],
        ]],
    ];
}

test('administrators can edit the single Pamplona boundary used by maps and jurisdiction checks', function () {
    $admin = User::factory()->administrator()->create();
    $customBoundary = municipalityGeofenceSquare(121.40, 18.50, 121.43, 18.53);

    $this->actingAs($admin)
        ->get(route('admin.geofence.index'))
        ->assertOk()
        ->assertSee('Pamplona municipal boundary')
        ->assertSee('Reset to default')
        ->assertSee('leaflet.draw.js', false)
        ->assertSee('id="boundary-adjust"', false)
        ->assertSee('id="boundary-replace"', false)
        ->assertSee('id="boundary-finish"', false)
        ->assertSee('id="boundary-cancel"', false)
        ->assertSee('validateBoundaryGeometry', false)
        ->assertSee('Adjust outline', false)
        ->assertSee('Redraw boundary', false)
        ->assertSee('Map tools', false)
        ->assertSeeInOrder([
            'id="boundary-tool-status"',
            'class="rg-geofence-map-wrap"',
            'id="municipal-boundary-map"',
        ], false)
        ->assertSee('.rg-geofence-tools { position: relative; display: grid;', false)
        ->assertSee('grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr));', false)
        ->assertDontSee('id="boundary-undo"', false)
        ->assertDontSee('id="boundary-redo"', false)
        ->assertSee('municipal-geofence.js', false)
        ->assertSee('Barangay boundaries remain unchanged.');

    $this->put(route('admin.geofence.update'), [
        'geometry' => json_encode($customBoundary, JSON_THROW_ON_ERROR),
    ])->assertRedirect(route('admin.geofence.index'));

    $successResponse = $this->get(route('admin.geofence.index'))->assertOk();
    expect(substr_count($successResponse->getContent(), 'Pamplona’s municipal boundary was updated across the system.'))->toBe(1);

    expect(app(GeofenceService::class)->isWithinPamplona(18.51, 121.41))->toBeTrue()
        ->and(app(GeofenceService::class)->isWithinPamplona(18.47, 121.32))->toBeFalse()
        ->and(SystemSetting::query()->sole()->municipality_boundary['type'])->toBe('Polygon');

    $classificationResponse = $this->getJson(route('public.hazard.barangay', ['lat' => 18.51, 'lng' => 121.41]))
        ->assertOk()
        ->assertJsonPath('inside', true);
    expect($classificationResponse->headers->get('Cache-Control'))->toContain('no-store');

    $liveBoundaryResponse = $this->getJson(route('public.hazard.boundary'))
        ->assertOk()
        ->assertJsonPath('features.0.geometry.coordinates.0.0.0', 121.4);
    expect($liveBoundaryResponse->headers->get('Cache-Control'))->toContain('no-store');

    expect(app(GeofenceService::class)->resolveBarangay(18.47, 121.32))->not->toBeNull();
    Http::fake([
        'nominatim.openstreetmap.org/reverse*' => Http::response([
            'address' => ['municipality' => 'Sanchez Mira', 'state' => 'Cagayan', 'country' => 'Philippines'],
        ]),
    ]);
    $this->getJson(route('public.hazard.barangay', ['lat' => 18.47, 'lng' => 121.32]))
        ->assertOk()
        ->assertJsonPath('inside', false);

    $this->get(route('public.report.create'))
        ->assertOk()
        ->assertSee('window.RANIAG_BOUNDARY', false)
        ->assertSee('municipal-geofence.js', false)
        ->assertSee('hazard-map\\/barangay', false)
        ->assertSee('121.4', false);
    $this->get(route('public.hazard.map'))
        ->assertOk()
        ->assertSee('121.4', false)
        ->assertSee('boundaryUrl', false);

    foreach ([
        ['administrator', route('admin.dashboard.boundary')],
        ['agency', route('agency.dashboard.boundary')],
        ['personnel', route('personnel.dashboard.boundary')],
    ] as [$role, $url]) {
        $user = $role === 'administrator'
            ? $admin
            : User::factory()->create(['role' => UserRole::from($role)]);

        $response = $this->actingAs($user)
            ->getJson($url);

        $response
            ->assertOk()
            ->assertJsonPath('features.0.geometry.coordinates.0.0.0', 121.4);
        expect($response->headers->get('Cache-Control'))->toContain('no-store');
    }

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('BOUNDARY_REFRESH_MS = 10000', false)
        ->assertSee('setInterval(refreshBoundary, BOUNDARY_REFRESH_MS)', false);

    expect(ActivityLog::query()->where('event', 'municipality_boundary.updated')->count())->toBe(1);
});

test('administrators can reset the boundary to the original Pamplona GeoJSON', function () {
    $defaultBoundary = municipalityGeofenceSquare(121.30, 18.45, 121.35, 18.50);
    Storage::fake('local');
    Storage::disk('local')->put('geo/pamplona_boundary.geojson', json_encode([
        'type' => 'FeatureCollection',
        'features' => [[
            'type' => 'Feature',
            'properties' => [],
            'geometry' => $defaultBoundary,
        ]],
    ], JSON_THROW_ON_ERROR));
    Cache::flush();

    $admin = User::factory()->administrator()->create();
    $type = IncidentType::factory()->create();
    foreach ([
        ['RAN-RESET-NOW-INSIDE', 18.47, 121.32, false],
        ['RAN-RESET-NOW-OUTSIDE', 18.51, 121.41, true],
    ] as [$tracking, $latitude, $longitude, $oldClassification]) {
        Incident::query()->create([
            'tracking_number' => $tracking,
            'tracking_pin' => bcrypt('123456'),
            'incident_type_id' => $type->id,
            'status' => 'submitted',
            'priority' => 'medium',
            'description' => 'Municipal boundary reset regression test.',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'is_anonymous' => true,
            'meta' => ['within_jurisdiction' => $oldClassification],
            'reported_at' => now(),
        ]);
    }

    $this->actingAs($admin)
        ->put(route('admin.geofence.update'), [
            'geometry' => json_encode(municipalityGeofenceSquare(121.40, 18.50, 121.43, 18.53), JSON_THROW_ON_ERROR),
        ])
        ->assertRedirect(route('admin.geofence.index'));

    $this->delete(route('admin.geofence.reset'))
        ->assertRedirect(route('admin.geofence.index'));

    expect(SystemSetting::query()->sole()->municipality_boundary)->toBeNull()
        ->and(app(GeofenceService::class)->hasCustomBoundary())->toBeFalse()
        ->and(app(GeofenceService::class)->isWithinPamplona(18.47, 121.32))->toBeTrue()
        ->and(app(GeofenceService::class)->isWithinPamplona(18.51, 121.41))->toBeFalse()
        ->and(Incident::query()->where('tracking_number', 'RAN-RESET-NOW-INSIDE')->firstOrFail()->meta['within_jurisdiction'])->toBeTrue()
        ->and(Incident::query()->where('tracking_number', 'RAN-RESET-NOW-OUTSIDE')->firstOrFail()->meta['within_jurisdiction'])->toBeFalse()
        ->and(ActivityLog::query()->where('event', 'municipality_boundary.reset')->count())->toBe(1);
});

test('report submission does not assign a static Pamplona barangay outside the active boundary', function () {
    $admin = User::factory()->administrator()->create();
    $this->actingAs($admin)
        ->put(route('admin.geofence.update'), [
            'geometry' => json_encode(municipalityGeofenceSquare(121.40, 18.50, 121.43, 18.53), JSON_THROW_ON_ERROR),
        ])
        ->assertRedirect(route('admin.geofence.index'));

    $geofence = app(GeofenceService::class);
    expect($geofence->isWithinPamplona(18.47, 121.32))->toBeFalse()
        ->and($geofence->resolveBarangay(18.47, 121.32))->not->toBeNull();

    $this->mock(NotificationService::class, function ($mock) {
        $mock->shouldReceive('notifyAdminNewIncident')->once();
        $mock->shouldReceive('notifyReporterReportReceived')->once();
    });

    $incident = app(IncidentService::class)->submitAnonymousReport([
        'incident_type_id' => IncidentType::factory()->create()->id,
        'description' => 'Boundary precedence regression test.',
        'barangay' => 'Reported place label',
        'is_anonymous' => true,
        'latitude' => 18.47,
        'longitude' => 121.32,
    ]);

    expect($incident->fresh()->meta['within_jurisdiction'])->toBeFalse()
        ->and($incident->barangay)->toBe('Reported place label');
});

test('inside and outside report filters are recalculated when the municipality boundary changes', function () {
    Cache::flush();
    $admin = User::factory()->administrator()->create();
    $type = IncidentType::factory()->create();

    foreach ([
        ['RAN-BOUNDARY-NOW-INSIDE', 18.51, 121.41, false, null, 'outside_aor'],
        ['RAN-BOUNDARY-NOW-OUTSIDE', 18.47, 121.32, true, 'Bidduang', 'submitted'],
        ['RAN-BOUNDARY-UNKNOWN-LOCATION', null, null, null, null, 'submitted'],
    ] as [$tracking, $latitude, $longitude, $oldClassification, $barangay, $status]) {
        Incident::query()->create([
            'tracking_number' => $tracking,
            'tracking_pin' => bcrypt('123456'),
            'incident_type_id' => $type->id,
            'status' => $status,
            'priority' => 'medium',
            'description' => 'Municipal boundary filter regression test.',
            'barangay' => $barangay,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'is_anonymous' => true,
            'meta' => ['within_jurisdiction' => $oldClassification],
            'reported_at' => now(),
        ]);
    }

    $this->actingAs($admin)
        ->put(route('admin.geofence.update'), [
            'geometry' => json_encode(municipalityGeofenceSquare(121.40, 18.50, 121.43, 18.53), JSON_THROW_ON_ERROR),
        ])
        ->assertRedirect(route('admin.geofence.index'));

    expect(Incident::query()->where('tracking_number', 'RAN-BOUNDARY-NOW-INSIDE')->firstOrFail()->meta['within_jurisdiction'])->toBeTrue()
        ->and(Incident::query()->where('tracking_number', 'RAN-BOUNDARY-NOW-OUTSIDE')->firstOrFail()->meta['within_jurisdiction'])->toBeFalse();

    $this->get(route('admin.reports.index', ['applied' => '1', 'barangay' => 'inside']))
        ->assertOk()
        ->assertSee('RAN-BOUNDARY-NOW-INSIDE', false)
        ->assertDontSee('RAN-BOUNDARY-NOW-OUTSIDE', false);

    $this->get(route('admin.reports.index', ['applied' => '1', 'barangay' => 'outside']))
        ->assertOk()
        ->assertSee('RAN-BOUNDARY-NOW-OUTSIDE', false)
        ->assertDontSee('RAN-BOUNDARY-NOW-INSIDE', false);

    $this->getJson(route('public.dashboard.data'))
        ->assertOk()
        ->assertJsonPath('total_all_time', 1);

    $insideQueue = $this->get(route('admin.incidents.index', [
        'status' => 'all',
        'jurisdiction' => 'inside',
    ]))->assertOk()->viewData('incidents');
    expect($insideQueue->pluck('tracking_number')->all())->toContain('RAN-BOUNDARY-NOW-INSIDE')
        ->not->toContain('RAN-BOUNDARY-NOW-OUTSIDE');

    $outsideQueue = $this->get(route('admin.incidents.index', [
        'status' => 'all',
        'jurisdiction' => 'outside',
    ]))->assertOk()->viewData('incidents');
    expect($outsideQueue->pluck('tracking_number')->all())->toContain('RAN-BOUNDARY-NOW-OUTSIDE')
        ->not->toContain('RAN-BOUNDARY-NOW-INSIDE', 'RAN-BOUNDARY-UNKNOWN-LOCATION');
    expect($insideQueue->pluck('tracking_number')->all())->not->toContain('RAN-BOUNDARY-UNKNOWN-LOCATION');
});

test('only administrators can change the boundary and invalid open polygons are rejected', function () {
    $agencyUser = User::factory()->agency()->create();

    $this->actingAs($agencyUser)
        ->get(route('admin.geofence.index'))
        ->assertForbidden();

    $admin = User::factory()->administrator()->create();
    $openPolygon = [
        'type' => 'Polygon',
        'coordinates' => [[
            [121.40, 18.50],
            [121.40, 18.53],
            [121.43, 18.53],
            [121.43, 18.50],
        ]],
    ];

    $this->actingAs($admin)
        ->from(route('admin.geofence.index'))
        ->put(route('admin.geofence.update'), [
            'geometry' => json_encode($openPolygon, JSON_THROW_ON_ERROR),
        ])
        ->assertRedirect(route('admin.geofence.index'))
        ->assertSessionHasErrors('geometry');

    $crossingPolygon = [
        'type' => 'Polygon',
        'coordinates' => [[
            [121.40, 18.50],
            [121.43, 18.53],
            [121.40, 18.53],
            [121.43, 18.50],
            [121.40, 18.50],
        ]],
    ];

    $this->from(route('admin.geofence.index'))
        ->put(route('admin.geofence.update'), [
            'geometry' => json_encode($crossingPolygon, JSON_THROW_ON_ERROR),
        ])
        ->assertRedirect(route('admin.geofence.index'))
        ->assertSessionHasErrors('geometry');

    expect(SystemSetting::query()->exists())->toBeFalse();
});

test('the default Pamplona boundary can be saved as a valid custom outline', function () {
    $boundary = app(GeofenceService::class)->boundaryGeometry();
    expect($boundary)->not->toBeNull();

    $admin = User::factory()->administrator()->create();
    $this->actingAs($admin)
        ->put(route('admin.geofence.update'), [
            'geometry' => json_encode($boundary, JSON_THROW_ON_ERROR),
        ])
        ->assertRedirect(route('admin.geofence.index'));

    expect(SystemSetting::query()->sole()->municipality_boundary['type'])->toBe('Polygon');
});

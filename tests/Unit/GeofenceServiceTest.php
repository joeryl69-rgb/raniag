<?php

use App\Services\GeofenceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->geofence = new GeofenceService;
});

// --- pointInRing -------------------------------------------------------

it('detects a point inside a simple square ring', function () {
    $square = [[0, 0], [0, 10], [10, 10], [10, 0], [0, 0]];

    expect($this->geofence->pointInRing(5, 5, $square))->toBeTrue();
});

it('detects a point outside a simple square ring', function () {
    $square = [[0, 0], [0, 10], [10, 10], [10, 0], [0, 0]];

    expect($this->geofence->pointInRing(50, 50, $square))->toBeFalse();
});

it('treats a ring with fewer than 3 points as never containing a point', function () {
    $degenerate = [[0, 0], [10, 10]];

    expect($this->geofence->pointInRing(5, 5, $degenerate))->toBeFalse();
});

// --- pointInGeometry -----------------------------------------------------

it('finds a point inside a Polygon outer ring with no holes', function () {
    $geometry = [
        'type' => 'Polygon',
        'coordinates' => [
            [[0, 0], [0, 10], [10, 10], [10, 0], [0, 0]],
        ],
    ];

    expect($this->geofence->pointInGeometry(5, 5, $geometry))->toBeTrue();
});

it('excludes a point that falls inside a Polygon hole', function () {
    $geometry = [
        'type' => 'Polygon',
        'coordinates' => [
            [[0, 0], [0, 10], [10, 10], [10, 0], [0, 0]], // outer ring
            [[4, 4], [4, 6], [6, 6], [6, 4], [4, 4]],       // hole
        ],
    ];

    expect($this->geofence->pointInGeometry(5, 5, $geometry))->toBeFalse();
});

it('includes a point inside the outer ring but outside the hole', function () {
    $geometry = [
        'type' => 'Polygon',
        'coordinates' => [
            [[0, 0], [0, 10], [10, 10], [10, 0], [0, 0]],
            [[4, 4], [4, 6], [6, 6], [6, 4], [4, 4]],
        ],
    ];

    expect($this->geofence->pointInGeometry(1, 1, $geometry))->toBeTrue();
});

it('finds a point inside either polygon of a MultiPolygon', function () {
    $geometry = [
        'type' => 'MultiPolygon',
        'coordinates' => [
            [[[0, 0], [0, 10], [10, 10], [10, 0], [0, 0]]],
            [[[20, 20], [20, 30], [30, 30], [30, 20], [20, 20]]],
        ],
    ];

    expect($this->geofence->pointInGeometry(25, 25, $geometry))->toBeTrue()
        ->and($this->geofence->pointInGeometry(5, 5, $geometry))->toBeTrue()
        ->and($this->geofence->pointInGeometry(50, 50, $geometry))->toBeFalse();
});

it('returns false for an empty or malformed geometry', function () {
    expect($this->geofence->pointInGeometry(5, 5, []))->toBeFalse()
        ->and($this->geofence->pointInGeometry(5, 5, ['type' => 'Polygon', 'coordinates' => []]))->toBeFalse();
});

// --- isWithinPamplona ----------------------------------------------------

it('returns null when coordinates are missing', function () {
    expect($this->geofence->isWithinPamplona(null, 121.0))->toBeNull()
        ->and($this->geofence->isWithinPamplona(18.0, null))->toBeNull();
});

it('returns null when no boundary file is configured', function () {
    config(['raniag.pamplona_boundary_path' => null]);

    expect($this->geofence->isWithinPamplona(18.47, 121.32))->toBeNull();
});

it('returns null when the configured boundary file does not exist on disk', function () {
    Storage::fake('local');
    config(['raniag.pamplona_boundary_path' => 'geo/missing_boundary.geojson']);

    expect($this->geofence->isWithinPamplona(18.47, 121.32))->toBeNull();
});

it('returns true for coordinates inside the configured boundary and false for outside', function () {
    Storage::fake('local');
    Cache::flush();

    $boundary = [
        'type' => 'Polygon',
        'coordinates' => [
            [[121.30, 18.45], [121.30, 18.50], [121.35, 18.50], [121.35, 18.45], [121.30, 18.45]],
        ],
    ];

    Storage::disk('local')->put('geo/test_boundary.geojson', json_encode($boundary));
    config(['raniag.pamplona_boundary_path' => 'geo/test_boundary.geojson']);

    expect($this->geofence->isWithinPamplona(18.47, 121.32))->toBeTrue()
        ->and($this->geofence->isWithinPamplona(10.0, 100.0))->toBeFalse();
});

it('accepts a boundary file wrapped in a Feature or FeatureCollection', function () {
    Storage::fake('local');
    Cache::flush();

    $geometry = [
        'type' => 'Polygon',
        'coordinates' => [
            [[121.30, 18.45], [121.30, 18.50], [121.35, 18.50], [121.35, 18.45], [121.30, 18.45]],
        ],
    ];

    $featureCollection = [
        'type' => 'FeatureCollection',
        'features' => [
            ['type' => 'Feature', 'geometry' => $geometry],
        ],
    ];

    Storage::disk('local')->put('geo/fc_boundary.geojson', json_encode($featureCollection));
    config(['raniag.pamplona_boundary_path' => 'geo/fc_boundary.geojson']);

    expect($this->geofence->isWithinPamplona(18.47, 121.32))->toBeTrue();
});

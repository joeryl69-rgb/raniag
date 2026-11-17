const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.resolve(__dirname, '../../public/js/municipal-geofence.js'),
    'utf8'
);
const window = {};
vm.runInNewContext(source, { window });

const geofence = window.RANIAG_Geofence;

function square(west, south, east, north) {
    return {
        type: 'Polygon',
        coordinates: [[
            [west, south],
            [west, north],
            [east, north],
            [east, south],
            [west, south],
        ]],
    };
}

test('active municipal boundary overrides a static barangay polygon', () => {
    const place = geofence.resolvePlaceFromBoundaries(
        18.51,
        121.32,
        square(121.40, 18.50, 121.43, 18.53),
        {
            features: [{
                properties: { adm4_en: 'Static Barangay' },
                geometry: square(121.30, 18.45, 121.35, 18.55),
            }],
        },
        { municipality: 'Pamplona' }
    );

    assert.equal(place.inside, false);
    assert.equal(place.barangay, null);
});

test('inside active boundary resolves a matching barangay', () => {
    const place = geofence.resolvePlaceFromBoundaries(
        18.51,
        121.41,
        square(121.40, 18.50, 121.43, 18.53),
        {
            features: [{
                properties: { adm4_en: 'Test Barangay' },
                geometry: square(121.40, 18.50, 121.43, 18.53),
            }],
        },
        { municipality: 'Pamplona' }
    );

    assert.equal(place.inside, true);
    assert.equal(place.barangay, 'Test Barangay');
});

test('expanded active boundary still classifies inside points without a static barangay match', () => {
    const place = geofence.resolvePlaceFromBoundaries(
        18.51,
        121.41,
        square(121.40, 18.50, 121.43, 18.53),
        { features: [] },
        { municipality: 'Pamplona' }
    );

    assert.equal(place.inside, true);
    assert.equal(place.barangay, null);
    assert.equal(place.municipality, 'Pamplona');
});

test('static barangay lookup remains available when no municipal boundary is provided', () => {
    const place = geofence.resolvePlaceFromBoundaries(
        18.51,
        121.41,
        null,
        {
            features: [{
                properties: { adm4_en: 'Fallback Barangay' },
                geometry: square(121.40, 18.50, 121.43, 18.53),
            }],
        },
        null
    );

    assert.equal(place.inside, true);
    assert.equal(place.barangay, 'Fallback Barangay');
});

test('municipal boundary validation accepts a closed outline and rejects invalid geometry', () => {
    const valid = square(121.4, 18.5, 121.43, 18.53);
    assert.equal(geofence.validateBoundaryGeometry(valid).valid, true);
    assert.match(geofence.validateBoundaryGeometry({
        ...valid,
        coordinates: [[...valid.coordinates[0].slice(0, -1), [121.42, 18.52]]],
    }).message, /Close the polygon/);
    assert.match(geofence.validateBoundaryGeometry({
        type: 'Polygon',
        coordinates: [[
            [121.4, 18.5],
            [121.43, 18.53],
            [121.4, 18.53],
            [121.43, 18.5],
            [121.4, 18.5],
        ]],
    }).message, /crosses itself/);
});

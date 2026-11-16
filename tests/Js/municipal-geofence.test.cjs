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

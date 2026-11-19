const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.resolve(__dirname, '../../public/js/public-community-centers.js'),
    'utf8'
);
const list = { innerHTML: '', addEventListener() {} };
const markers = [];
const markerLayer = {
    markers,
    addTo() { return this; },
    clearLayers() { markers.length = 0; },
    removeLayer(marker) {
        const index = markers.indexOf(marker);
        if (index >= 0) markers.splice(index, 1);
    },
};
const map = {
    setView() { return this; },
    invalidateSize() {},
    fitBounds() {},
};
const mapElement = { addEventListener() {} };
const leaflet = {
    map() { return map; },
    layerGroup() { return markerLayer; },
    divIcon(options) { return { options }; },
    marker(position, options) {
        return {
            position,
            icon: options.icon,
            title: options.title,
            bindPopup() { return this; },
            addTo(layer) { layer.markers.push(this); return this; },
            setLatLng(nextPosition) { this.position = nextPosition; },
            setIcon(icon) { this.icon = icon; },
            setPopupContent() {},
        };
    },
};
const window = {
    L: leaflet,
    RANIAG_Mapbox: { addBasemap() { return { kind: 'mapbox' }; } },
    addEventListener() {},
};
const document = {
    getElementById(id) {
        if (id === 'pd-centers-list') return list;
        if (id === 'community-center-map') return mapElement;
        return null;
    },
};

vm.runInNewContext(source, { window, document });
window.RANIAG_CommunityCenters.init({});

test('community shelter cards show current availability and route inside RANIAG', () => {
    window.RANIAG_CommunityCenters.update([{
        id: 7,
        name: '<Cabaggan Elementary>',
        address: 'Cabaggan, Pamplona, Cagayan',
        barangay: 'Cabaggan',
        latitude: 18.451,
        longitude: 121.341,
        capacity: 200,
        checked_in_count: 19,
        is_open: true,
        route_url: '/hazard-map?route_to_center=7',
    }]);

    assert.match(list.innerHTML, /&lt;Cabaggan Elementary&gt;/);
    assert.match(list.innerHTML, /181 spaces available · 19 \/ 200 inside/);
    assert.match(list.innerHTML, /Cabaggan, Pamplona, Cagayan/);
    assert.doesNotMatch(list.innerHTML, /Barangay Cabaggan/);
    assert.match(list.innerHTML, /href="\/hazard-map\?route_to_center=7"/);
    assert.match(list.innerHTML, />Route here<\/a>/);
    assert.doesNotMatch(list.innerHTML, /google\.com|target="_blank"/);

    window.RANIAG_CommunityCenters.update([{
        id: 8,
        name: 'Closed School',
        address: 'Centro',
        latitude: 18.452,
        longitude: 121.342,
        capacity: 200,
        checked_in_count: 4,
        is_open: false,
        route_url: null,
    }]);

    assert.match(list.innerHTML, /Closed to arrivals · 4 inside/);
    assert.doesNotMatch(list.innerHTML, /Route here/);
});

test('community shelter pins use saved colors and a safe default', () => {
    window.RANIAG_CommunityCenters.update([{
        id: 9,
        name: 'Custom Color Shelter',
        latitude: 18.451,
        longitude: 121.341,
        color: '#a855f7',
        is_open: true,
    }]);

    assert.match(markers[0].icon.options.className, /is-open/);
    assert.match(markers[0].icon.options.html, /--center-pin-color:#a855f7/);

    window.RANIAG_CommunityCenters.update([{
        id: 10,
        name: 'Default Color Shelter',
        latitude: 18.452,
        longitude: 121.342,
        color: 'invalid',
        is_open: false,
    }]);

    assert.match(markers[0].icon.options.className, /is-closed/);
    assert.match(markers[0].icon.options.html, /--center-pin-color:#0f766e/);
});

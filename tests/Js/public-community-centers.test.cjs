const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.resolve(__dirname, '../../public/js/public-community-centers.js'),
    'utf8'
);
const list = { innerHTML: '' };
const window = {};
const document = {
    getElementById(id) {
        return id === 'pd-centers-list' ? list : null;
    },
};

vm.runInNewContext(source, { window, document });

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

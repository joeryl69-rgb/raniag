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

test('community shelter cards include useful details and direct directions', () => {
    window.RANIAG_CommunityCenters.update([{
        id: 7,
        name: '<Cabaggan Elementary>',
        address: 'Cabaggan, Pamplona, Cagayan',
        barangay: 'Cabaggan',
        latitude: 18.451,
        longitude: 121.341,
        capacity: 200,
        is_open: true,
    }]);

    assert.match(list.innerHTML, /&lt;Cabaggan Elementary&gt;/);
    assert.match(list.innerHTML, /Capacity: 200 people/);
    assert.match(list.innerHTML, /Cabaggan, Pamplona, Cagayan/);
    assert.doesNotMatch(list.innerHTML, /Barangay Cabaggan/);
    assert.match(list.innerHTML, /https:\/\/www\.google\.com\/maps\/dir\/\?api=1&amp;destination=18\.451%2C121\.341/);
    assert.match(list.innerHTML, /target="_blank" rel="noopener noreferrer"/);
});

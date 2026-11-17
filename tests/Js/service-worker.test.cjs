const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.resolve(__dirname, '../../public/sw.js'), 'utf8');
const cacheLookups = [];
const self = {
    location: { origin: 'https://raniag.test' },
    addEventListener() {},
};
const context = {
    self,
    caches: {
        match(request, options) {
            cacheLookups.push({ request, options });
            return Promise.resolve(new Response('cached'));
        },
    },
    URL,
    Request,
    Response,
    Promise,
    console,
};

vm.runInNewContext(`${source}\nself.__testing = { shouldBypassCache, matchCached };`, context);

test('active boundary lookups neither enter nor use the service-worker cache', async () => {
    assert.equal(self.__testing.shouldBypassCache('/hazard-map/barangay'), true);
    assert.equal(self.__testing.shouldBypassCache('/raniag/hazard-map/barangay'), true);

    const cached = await self.__testing.matchCached(
        new Request('https://raniag.test/hazard-map/barangay?lat=18.5&lng=121.4')
    );

    assert.equal(cached, undefined);
    assert.equal(cacheLookups.length, 0);
});

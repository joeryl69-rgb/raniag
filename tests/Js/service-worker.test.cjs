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

test('active boundary and community dashboard data bypass the service-worker cache', async () => {
    assert.equal(self.__testing.shouldBypassCache('/hazard-map/barangay'), true);
    assert.equal(self.__testing.shouldBypassCache('/raniag/hazard-map/barangay'), true);
    assert.equal(self.__testing.shouldBypassCache('/community-dashboard/data.json'), true);
    assert.equal(self.__testing.shouldBypassCache('/raniag/community-dashboard/data.json'), true);

    const cached = await self.__testing.matchCached(
        new Request('https://raniag.test/hazard-map/barangay?lat=18.5&lng=121.4')
    );
    const dashboardCached = await self.__testing.matchCached(
        new Request('https://raniag.test/community-dashboard/data.json')
    );
    const prefixedDashboardCached = await self.__testing.matchCached(
        new Request('https://raniag.test/raniag/community-dashboard/data.json')
    );

    assert.equal(cached, undefined);
    assert.equal(dashboardCached, undefined);
    assert.equal(prefixedDashboardCached, undefined);
    assert.equal(cacheLookups.length, 0);
});

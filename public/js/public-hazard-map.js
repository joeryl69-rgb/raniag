/**
 * Public live hazard map — Leaflet layers, live YOU tracking, view focus on toggles.
 */
(function () {
    const REDUCE = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    function evacIcon() {
        return L.divIcon({
            className: 'rg-evac-marker',
            html: '<div class="rg-evac-pin"></div>',
            iconSize: [28, 28],
            iconAnchor: [14, 28],
            popupAnchor: [0, -24],
        });
    }

    function init(cfg) {
        const leaflet = window.L;
        if (!leaflet || !cfg) return;

        const mapEl = document.getElementById('hazard-map');
        if (!mapEl) return;

        const map = leaflet.map(mapEl).setView(
            [cfg.map.default_lat, cfg.map.default_lng],
            cfg.map.default_zoom || 13
        );
        leaflet.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OSM',
        }).addTo(map);

        const zoneGroup = leaflet.layerGroup().addTo(map);
        const centerGroup = leaflet.layerGroup().addTo(map);
        const youGroup = leaflet.layerGroup().addTo(map);

        const zoneLayers = new Map();
        const centerLayers = new Map();
        let lastUpdated = Date.now();
        let youMarker = null;
        let youAccuracy = null;
        let youLatLng = null;
        let watchId = null;
        let nearestData = null;
        let tipMode = 'both';
        let didInitialYouFit = false;

        const chkZones = document.getElementById('layer-zones');
        const chkCenters = document.getElementById('layer-centers');
        const chkYou = document.getElementById('layer-you');
        const locateBtn = document.getElementById('hazard-locate-you');
        const geoStatus = document.getElementById('hazard-geo-status');
        const joTip = document.getElementById('hazard-jo-tip');
        const zoneSection = document.getElementById('zone-section');
        const centerSection = document.getElementById('center-section');

        function setUpdatedLabel() {
            const el = document.getElementById('hazard-updated');
            if (!el) return;
            const sec = Math.max(0, Math.round((Date.now() - lastUpdated) / 1000));
            el.textContent = sec < 5 ? 'Updated just now' : `Updated ${sec}s ago`;
        }

        function setGeoStatus(msg, isError) {
            if (!geoStatus) return;
            if (!msg) {
                geoStatus.classList.add('d-none');
                geoStatus.textContent = '';
                return;
            }
            geoStatus.textContent = msg;
            geoStatus.classList.toggle('text-danger', !!isError);
            geoStatus.classList.toggle('text-muted', !isError);
            geoStatus.classList.remove('d-none');
        }

        function updateJoTip(mode) {
            tipMode = mode || tipMode;
            if (!joTip) return;
            const tips = {
                centers: 'Viewing evacuation centers. Tap one for details, or use YOU to see the nearest open center.',
                zones: 'Viewing active hazard zones. Pulses mark the areas — tap a zone for the advisory.',
                you: 'Tracking your location. Toggle Evacuation to see centers near you.',
                both: 'Toggle layers to focus the map. Tap YOU to track where you are.',
                denied: 'Location is blocked. Enable GPS in the browser to use YOU.',
            };
            joTip.textContent = tips[tipMode] || tips.both;
        }

        function layersOn() {
            return {
                zones: !chkZones || chkZones.checked,
                centers: !chkCenters || chkCenters.checked,
                you: !chkYou || chkYou.checked,
            };
        }

        function syncListVisibility() {
            const on = layersOn();
            if (zoneSection) zoneSection.classList.toggle('rg-hazard-section-dim', !on.zones);
            if (centerSection) centerSection.classList.toggle('rg-hazard-section-dim', !on.centers);
        }

        function focusMode() {
            const on = layersOn();
            if (on.zones && !on.centers) return 'zones';
            if (on.centers && !on.zones) return 'centers';
            if (on.you && !on.zones && !on.centers) return 'you';
            return 'both';
        }

        function collectBoundsLayers() {
            const on = layersOn();
            const layers = [];
            if (on.zones) {
                const containing = nearestData?.hazard_zones || [];
                if (containing.length) {
                    containing.forEach((z) => {
                        const layer = zoneLayers.get(Number(z.id));
                        if (layer) layers.push(layer);
                    });
                }
                if (!layers.length) {
                    zoneLayers.forEach((layer) => layers.push(layer));
                }
            }
            if (on.centers) {
                const nearestId = nearestData?.nearest_center?.id;
                if (nearestId && centerLayers.has(Number(nearestId))) {
                    layers.push(centerLayers.get(Number(nearestId)));
                    centerLayers.forEach((layer, id) => {
                        if (Number(id) !== Number(nearestId)) layers.push(layer);
                    });
                } else {
                    centerLayers.forEach((layer) => layers.push(layer));
                }
            }
            if (on.you && youMarker) layers.push(youMarker);
            return layers;
        }

        function fitActiveView({ animate } = {}) {
            const layers = collectBoundsLayers();
            const on = layersOn();
            const mode = focusMode();
            updateJoTip(mode === 'both' && on.you ? 'both' : mode);
            syncListVisibility();

            if (!layers.length) {
                if (on.you && youLatLng) {
                    goToLatLng(youLatLng, 15, animate !== false);
                }
                return;
            }

            // Prefer you + nearest center when focusing evacuation
            if (mode === 'centers' && youLatLng && nearestData?.nearest_center) {
                const nc = nearestData.nearest_center;
                const bounds = leaflet.latLngBounds([
                    youLatLng,
                    [Number(nc.latitude), Number(nc.longitude)],
                ]);
                try {
                    map.fitBounds(bounds.pad(0.35), {
                        animate: !REDUCE && animate !== false,
                        maxZoom: 16,
                    });
                    return;
                } catch (e) { /* fall through */ }
            }

            try {
                const group = leaflet.featureGroup(layers);
                map.fitBounds(group.getBounds().pad(0.14), {
                    animate: !REDUCE && animate !== false,
                    maxZoom: 17,
                });
            } catch (e) { /* empty */ }
        }

        function goToLatLng(latlng, zoom, animate) {
            if (!latlng) return;
            const z = zoom || Math.max(map.getZoom(), 15);
            if (REDUCE || animate === false) {
                map.setView(latlng, z);
            } else {
                map.flyTo(latlng, z, { duration: 0.65 });
            }
        }

        function highlightCenter(id) {
            document.querySelectorAll('[data-center-id]').forEach((el) => {
                el.classList.toggle('is-active', Number(el.dataset.centerId) === Number(id));
            });
        }

        function highlightZone(id) {
            document.querySelectorAll('[data-zone-id]').forEach((el) => {
                el.classList.toggle('is-active', Number(el.dataset.zoneId) === Number(id));
            });
        }

        function flyToCenter(id, { openPopup } = {}) {
            const layer = centerLayers.get(Number(id));
            if (!layer) return;
            highlightCenter(id);
            goToLatLng(layer.getLatLng(), Math.max(map.getZoom(), 15), true);
            if (openPopup !== false) layer.openPopup();
        }

        function renderNearest() {
            const box = document.getElementById('nearest-box');
            const hazardBox = document.getElementById('containing-zones-box');
            if (!box) return;

            if (!nearestData?.nearest_center) {
                box.classList.add('d-none');
                box.innerHTML = '';
                box.onclick = null;
            } else {
                const nc = nearestData.nearest_center;
                box.classList.remove('d-none');
                box.innerHTML = `
                    <button type="button" class="rg-hazard-nearest-btn text-start w-100 bg-transparent border-0 p-0"
                            data-nearest-center-id="${nc.id}">
                        <span class="d-block small text-muted text-uppercase fw-semibold" style="letter-spacing:.04em;font-size:.68rem">Nearest open center</span>
                        <strong class="d-block">${esc(nc.name)}</strong>
                        <span class="text-muted" style="font-size:.78rem">~${esc(nc.distance_m)} m · tap to view on map</span>
                    </button>`;
                box.querySelector('[data-nearest-center-id]')?.addEventListener('click', () => {
                    if (chkCenters && !chkCenters.checked) {
                        chkCenters.checked = true;
                        map.addLayer(centerGroup);
                        syncListVisibility();
                    }
                    flyToCenter(nc.id);
                    updateJoTip('centers');
                });
            }

            if (hazardBox) {
                const zones = nearestData?.hazard_zones || [];
                if (!zones.length) {
                    hazardBox.classList.add('d-none');
                    hazardBox.innerHTML = '';
                } else {
                    hazardBox.classList.remove('d-none');
                    hazardBox.innerHTML = `
                        <div class="small fw-semibold mb-1">You may be inside</div>
                        <ul class="mb-0 ps-3 small">
                            ${zones.map((z) => `
                                <li>
                                    <button type="button" class="btn btn-link btn-sm p-0 align-baseline" data-containing-zone="${z.id}">
                                        ${esc(z.name)}${z.type ? ` (${esc(z.type)})` : ''}
                                    </button>
                                </li>`).join('')}
                        </ul>`;
                    hazardBox.querySelectorAll('[data-containing-zone]').forEach((btn) => {
                        btn.addEventListener('click', () => {
                            const id = Number(btn.dataset.containingZone);
                            if (chkZones && !chkZones.checked) {
                                chkZones.checked = true;
                                map.addLayer(zoneGroup);
                                syncListVisibility();
                            }
                            const layer = zoneLayers.get(id);
                            if (layer) {
                                highlightZone(id);
                                try {
                                    map.fitBounds(layer.getBounds().pad(0.2), { animate: !REDUCE });
                                    layer.openPopup();
                                } catch (e) { /* */ }
                            }
                            updateJoTip('zones');
                        });
                    });
                }
            }
        }

        async function fetchNearest(lat, lng) {
            try {
                const res = await fetch(`${cfg.nearestUrl}?lat=${lat}&lng=${lng}`);
                if (!res.ok) return;
                nearestData = await res.json();
                renderNearest();
            } catch (e) { /* offline */ }
        }

        function upsertYou(lat, lng, acc) {
            youLatLng = leaflet.latLng(lat, lng);
            if (youAccuracy) {
                youAccuracy.setLatLng(youLatLng);
                youAccuracy.setRadius(acc || 40);
            } else {
                youAccuracy = leaflet.circle(youLatLng, {
                    radius: acc || 40,
                    color: '#3d8bfd',
                    weight: 1,
                    fillColor: '#3d8bfd',
                    fillOpacity: 0.15,
                    className: REDUCE ? '' : 'rg-you-accuracy',
                }).addTo(youGroup);
            }
            if (youMarker) {
                youMarker.setLatLng(youLatLng);
            } else {
                youMarker = leaflet.circleMarker(youLatLng, {
                    radius: 7,
                    color: '#fff',
                    weight: 2,
                    fillColor: '#0b5ed7',
                    fillOpacity: 1,
                }).bindPopup('You').addTo(youGroup);
            }
        }

        function stopWatch() {
            if (watchId != null && navigator.geolocation) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
        }

        function startWatch() {
            if (!navigator.geolocation) {
                setGeoStatus('Geolocation is not supported on this device.', true);
                updateJoTip('denied');
                return;
            }
            if (watchId != null) return;

            setGeoStatus('Locating you…');
            watchId = navigator.geolocation.watchPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    const acc = pos.coords.accuracy || 40;
                    upsertYou(lat, lng, acc);
                    setGeoStatus('');
                    fetchNearest(lat, lng).finally(() => {
                        if (!didInitialYouFit && layersOn().you && youLatLng) {
                            didInitialYouFit = true;
                            fitActiveView({ animate: true });
                        }
                    });
                    if (layersOn().you && !map.hasLayer(youGroup)) {
                        map.addLayer(youGroup);
                    }
                },
                (err) => {
                    const denied = err?.code === 1;
                    setGeoStatus(
                        denied
                            ? 'Location permission denied. Enable GPS to use YOU.'
                            : 'Could not get your location. Try again.',
                        true
                    );
                    updateJoTip(denied ? 'denied' : 'both');
                },
                { enableHighAccuracy: true, maximumAge: 8000, timeout: 20000 }
            );
        }

        function renderLists(zones, centers) {
            const zList = document.getElementById('zone-list');
            const cList = document.getElementById('center-list');
            const zCount = document.getElementById('zone-count');
            const cCount = document.getElementById('center-count');
            if (zCount) zCount.textContent = `(${zones.length})`;
            if (cCount) cCount.textContent = `(${centers.length})`;

            if (zList) {
                zList.innerHTML = zones.length
                    ? zones.map((z) => `
                        <button type="button" class="rg-hazard-list-item text-start w-100 bg-transparent" data-zone-id="${z.id}">
                            <strong class="d-block small">${esc(z.name)}</strong>
                            <span class="text-muted" style="font-size:.78rem">${esc(z.type?.name || 'Hazard')}</span>
                        </button>`).join('')
                    : '<p class="small text-muted mb-0">No active hazard zones.</p>';
            }
            if (cList) {
                cList.innerHTML = centers.length
                    ? centers.map((c) => `
                        <button type="button" class="rg-hazard-list-item text-start w-100 bg-transparent" data-center-id="${c.id}">
                            <strong class="d-block small">${esc(c.name)}</strong>
                            <span class="text-muted" style="font-size:.78rem">Open evacuation center</span>
                        </button>`).join('')
                    : '<p class="small text-muted mb-0">No open centers right now.</p>';
            }
            syncListVisibility();
        }

        function syncZones(zones) {
            const ids = new Set(zones.map((z) => z.id));
            zoneLayers.forEach((layer, id) => {
                if (!ids.has(id)) {
                    zoneGroup.removeLayer(layer);
                    zoneLayers.delete(id);
                }
            });

            zones.forEach((z) => {
                try {
                    const color = z.color || z.type?.color || '#b45309';
                    if (zoneLayers.has(z.id)) {
                        const existing = zoneLayers.get(z.id);
                        existing.setStyle({ color, fillColor: color });
                        return;
                    }
                    const layer = leaflet.geoJSON(z.geometry, {
                        style: {
                            color,
                            fillColor: color,
                            weight: 2,
                            fillOpacity: REDUCE ? 0.28 : 0.25,
                            dashArray: '6 4',
                            className: REDUCE ? '' : 'rg-hazard-poly',
                        },
                    });
                    layer.bindPopup(`<strong>${esc(z.name)}</strong><br>${esc(z.type?.name || '')}<br>${esc(z.advisory_note || '')}`);
                    layer.on('mouseover click', () => highlightZone(z.id));
                    layer.addTo(zoneGroup);
                    zoneLayers.set(z.id, layer);
                } catch (e) { /* bad geometry */ }
            });
        }

        function syncCenters(centers) {
            const ids = new Set(centers.map((c) => c.id));
            centerLayers.forEach((layer, id) => {
                if (!ids.has(id)) {
                    centerGroup.removeLayer(layer);
                    centerLayers.delete(id);
                }
            });
            centers.forEach((c) => {
                if (centerLayers.has(c.id)) return;
                const marker = leaflet.marker([Number(c.latitude), Number(c.longitude)], { icon: evacIcon() })
                    .bindPopup(`<strong>${esc(c.name)}</strong><br>Open evacuation center`);
                marker.on('click', () => highlightCenter(c.id));
                marker.addTo(centerGroup);
                centerLayers.set(c.id, marker);
            });
        }

        function applyData(zones, centers, { fit } = {}) {
            syncZones(zones);
            syncCenters(centers);
            renderLists(zones, centers);
            lastUpdated = Date.now();
            setUpdatedLabel();
            if (fit) fitActiveView({ animate: false });
        }

        applyData(cfg.zones || [], cfg.centers || [], { fit: true });
        updateJoTip('both');

        document.getElementById('zone-list')?.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-zone-id]');
            if (!btn) return;
            const layer = zoneLayers.get(Number(btn.dataset.zoneId));
            if (layer) {
                highlightZone(btn.dataset.zoneId);
                try {
                    map.fitBounds(layer.getBounds().pad(0.2), { animate: !REDUCE });
                    layer.openPopup();
                } catch (err) { /* */ }
            }
        });
        document.getElementById('center-list')?.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-center-id]');
            if (!btn) return;
            flyToCenter(btn.dataset.centerId);
        });

        function onLayerToggle(which, checked) {
            if (which === 'zones') {
                if (checked) map.addLayer(zoneGroup);
                else map.removeLayer(zoneGroup);
            } else if (which === 'centers') {
                if (checked) map.addLayer(centerGroup);
                else map.removeLayer(centerGroup);
            } else if (which === 'you') {
                if (checked) {
                    map.addLayer(youGroup);
                    startWatch();
                } else {
                    map.removeLayer(youGroup);
                    stopWatch();
                }
            }
            fitActiveView({ animate: true });
        }

        chkZones?.addEventListener('change', (e) => onLayerToggle('zones', e.target.checked));
        chkCenters?.addEventListener('change', (e) => onLayerToggle('centers', e.target.checked));
        chkYou?.addEventListener('change', (e) => onLayerToggle('you', e.target.checked));

        locateBtn?.addEventListener('click', () => {
            if (chkYou && !chkYou.checked) {
                chkYou.checked = true;
                map.addLayer(youGroup);
            }
            startWatch();
            if (youLatLng) {
                goToLatLng(youLatLng, 16, true);
                youMarker?.openPopup();
                updateJoTip('you');
            } else {
                setGeoStatus('Locating you…');
            }
        });

        // Start tracking if YOU is on by default
        if (!chkYou || chkYou.checked) {
            startWatch();
        }

        function invalidate() {
            map.invalidateSize();
        }
        setTimeout(invalidate, 200);
        window.addEventListener('resize', invalidate);

        setInterval(setUpdatedLabel, 1000);

        async function softRefresh() {
            if (document.visibilityState === 'hidden') return;
            try {
                const res = await fetch(cfg.snapshotUrl, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                applyData(data.zones || [], data.centers || [], { fit: false });
                if (youLatLng) {
                    fetchNearest(youLatLng.lat, youLatLng.lng);
                }
            } catch (e) { /* ignore */ }
        }
        setInterval(softRefresh, 60000);

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                stopWatch();
            } else if (!chkYou || chkYou.checked) {
                startWatch();
            }
        });
        window.addEventListener('pagehide', stopWatch);
    }

    window.RANIAG_HazardMap = { init };
})();

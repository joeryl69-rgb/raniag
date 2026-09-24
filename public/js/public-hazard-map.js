/**
 * Public live hazard + risk awareness map — Leaflet layers, Mapbox tiles/route, live location.
 */
(function () {
    const REDUCE = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const Mapbox = window.RANIAG_Mapbox || null;

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

        const basemap = Mapbox ? Mapbox.addBasemap(map, cfg.map) : null;
        const mapboxToken = basemap?.token || String(cfg.map.mapbox_token || '').trim();

        if (!basemap) {
            leaflet.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OSM',
            }).addTo(map);
        }

        const riskGroup = leaflet.layerGroup().addTo(map);
        const zoneGroup = leaflet.layerGroup().addTo(map);
        const centerGroup = leaflet.layerGroup().addTo(map);
        const youGroup = leaflet.layerGroup().addTo(map);
        const routeGroup = leaflet.layerGroup().addTo(map);

        const zoneLayers = new Map();
        const centerLayers = new Map();
        let riskLayer = null;
        let lastUpdated = Date.now();
        let youMarker = null;
        let youAccuracy = null;
        let youLatLng = null;
        let watchId = null;
        let nearestData = null;
        let tipMode = 'both';
        let didInitialYouFit = false;
        let routeLayer = null;
        let routeProfile = (cfg.map.directions_profile === 'driving' ? 'driving' : 'walking');
        let routeFetchSeq = 0;
        let lastRouteAt = 0;
        let lastRouteLatLng = null;
        const ROUTE_MIN_MOVE_M = 25;
        const ROUTE_MIN_INTERVAL_MS = 12000;

        const chkZones = document.getElementById('layer-zones');
        const chkCenters = document.getElementById('layer-centers');
        const chkRisk = document.getElementById('layer-risk');
        const chkYou = document.getElementById('layer-you');
        const locateBtn = document.getElementById('hazard-locate-you');
        const geoStatus = document.getElementById('hazard-geo-status');
        const joTip = document.getElementById('hazard-jo-tip');
        const zoneSection = document.getElementById('zone-section');
        const centerSection = document.getElementById('center-section');
        const routeBox = document.getElementById('route-box');
        const routeSummary = document.getElementById('route-summary');
        const routeStatus = document.getElementById('route-status');
        const routeWalk = document.getElementById('route-walk');
        const routeDrive = document.getElementById('route-drive');
        const riskSummary = document.getElementById('risk-summary');
        const riskList = document.getElementById('risk-list');

        if (routeProfile === 'driving' && routeDrive) {
            routeDrive.checked = true;
        } else if (routeWalk) {
            routeWalk.checked = true;
        }

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
                centers: 'Viewing evacuation centers. Tap one for details, or use my current location to see the nearest open center.',
                zones: 'Viewing active hazard zones. Pulses mark the areas — tap a zone for the advisory.',
                risk: 'Risk awareness highlights barangays with open community reports — no exact addresses are shown.',
                you: 'My location is on. Route updates as you move — switch Walk or Drive anytime.',
                both: 'Toggle layers for zones, centers, and risk. Turn on My location for a live route to the nearest open center.',
                route: 'Live route to the nearest open center. It updates as you move.',
                off: 'My location is off. Turn it on to track yourself and show the route.',
                denied: 'Location is blocked. Enable GPS in the browser to use my current location.',
            };
            joTip.textContent = tips[tipMode] || tips.both;
        }

        function layersOn() {
            return {
                zones: !chkZones || chkZones.checked,
                centers: !chkCenters || chkCenters.checked,
                risk: !chkRisk || chkRisk.checked,
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
            if (on.zones && !on.centers && !on.risk) return 'zones';
            if (on.centers && !on.zones && !on.risk) return 'centers';
            if (on.risk && !on.zones && !on.centers) return 'risk';
            if (on.you && !on.zones && !on.centers && !on.risk) return 'you';
            return 'both';
        }

        function collectBoundsLayers() {
            const on = layersOn();
            const layers = [];
            if (on.risk && riskLayer) layers.push(riskLayer);
            if (on.zones) {
                const containing = nearestData?.hazard_zones || [];
                if (containing.length) {
                    containing.forEach((z) => {
                        const layer = zoneLayers.get(Number(z.id));
                        if (layer) layers.push(layer);
                    });
                }
                const zoneOnly = layers.filter((l) => l !== riskLayer);
                if (!zoneOnly.length) {
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
            if (routeLayer) layers.push(routeLayer);
            return layers;
        }

        function formatDistance(meters) {
            if (Mapbox) return Mapbox.formatDistance(meters);
            if (meters == null || Number.isNaN(meters)) return '';
            if (meters < 1000) return `${Math.round(meters)} m`;
            return `${(meters / 1000).toFixed(1)} km`;
        }

        function formatDuration(seconds) {
            if (Mapbox) return Mapbox.formatDuration(seconds);
            if (seconds == null || Number.isNaN(seconds)) return '';
            return `${Math.max(1, Math.round(seconds / 60))} min`;
        }

        function setRouteStatus(msg, isError) {
            if (!routeStatus) return;
            if (!msg) {
                routeStatus.classList.add('d-none');
                routeStatus.textContent = '';
                return;
            }
            routeStatus.textContent = msg;
            routeStatus.classList.toggle('text-danger', !!isError);
            routeStatus.classList.toggle('text-muted', !isError);
            routeStatus.classList.remove('d-none');
        }

        function clearRouteLineOnly() {
            routeGroup.clearLayers();
            routeLayer = null;
        }

        function clearRoute() {
            clearRouteLineOnly();
            lastRouteLatLng = null;
            lastRouteAt = 0;
        }

        function haversineMeters(a, b) {
            if (Mapbox) return Mapbox.haversineMeters(a, b);
            if (!a || !b) return Infinity;
            const R = 6371000;
            const dLat = ((b.lat - a.lat) * Math.PI) / 180;
            const dLng = ((b.lng - a.lng) * Math.PI) / 180;
            const lat1 = (a.lat * Math.PI) / 180;
            const lat2 = (b.lat * Math.PI) / 180;
            const h = Math.sin(dLat / 2) ** 2
                + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;
            return 2 * R * Math.asin(Math.min(1, Math.sqrt(h)));
        }

        function shouldRefreshRoute(force) {
            if (force) return true;
            if (!youLatLng) return false;
            if (!lastRouteLatLng || !lastRouteAt) return true;
            if (Date.now() - lastRouteAt >= ROUTE_MIN_INTERVAL_MS) return true;
            return haversineMeters(lastRouteLatLng, youLatLng) >= ROUTE_MIN_MOVE_M;
        }

        function setLocationEnabled(enabled) {
            if (chkYou) chkYou.checked = !!enabled;
            if (enabled) {
                map.addLayer(youGroup);
                if (!map.hasLayer(routeGroup)) map.addLayer(routeGroup);
                showRouteBox(!!mapboxToken);
                if (routeSummary) routeSummary.textContent = 'Locating you…';
                setRouteStatus('');
                setGeoStatus('Locating you…');
                updateJoTip('you');
                startWatch();
            } else {
                stopWatch();
                map.removeLayer(youGroup);
                clearRoute();
                youMarker = null;
                youAccuracy = null;
                youLatLng = null;
                nearestData = null;
                didInitialYouFit = false;
                youGroup.clearLayers();
                document.getElementById('nearest-box')?.classList.add('d-none');
                document.getElementById('containing-zones-box')?.classList.add('d-none');
                if (routeSummary) routeSummary.textContent = 'Turn on My location to see a live path.';
                setRouteStatus('');
                setGeoStatus('');
                showRouteBox(!!mapboxToken);
                updateJoTip('off');
            }
            fitActiveView({ animate: true });
        }

        function showRouteBox(visible) {
            if (!routeBox) return;
            routeBox.classList.toggle('d-none', !visible);
        }

        async function fetchRoute({ force } = {}) {
            if (!layersOn().you) {
                clearRoute();
                if (routeSummary) routeSummary.textContent = 'Turn on My location to see a live path.';
                setRouteStatus('');
                return;
            }

            const nc = nearestData?.nearest_center;
            if (!mapboxToken || !youLatLng || !nc) {
                clearRoute();
                if (routeSummary) {
                    routeSummary.textContent = mapboxToken
                        ? (youLatLng ? 'Looking for the nearest open center…' : 'Turn on My location to see a live path.')
                        : 'Mapbox token not configured — route unavailable.';
                }
                showRouteBox(!!mapboxToken);
                setRouteStatus('');
                return;
            }

            if (!shouldRefreshRoute(force)) return;

            showRouteBox(true);
            if ((force || !routeLayer) && routeSummary) {
                routeSummary.textContent = 'Getting route…';
            }
            setRouteStatus('');
            const seq = ++routeFetchSeq;

            try {
                if (!Mapbox?.fetchDirections) throw new Error('No Mapbox helper');
                const result = await Mapbox.fetchDirections({
                    token: mapboxToken,
                    from: youLatLng,
                    to: { lat: Number(nc.latitude), lng: Number(nc.longitude) },
                    profile: routeProfile,
                    routeGroup,
                });
                if (seq !== routeFetchSeq || !layersOn().you) return;
                if (!result?.layer) throw new Error('No route');

                routeLayer = result.layer;
                lastRouteAt = Date.now();
                lastRouteLatLng = leaflet.latLng(youLatLng.lat, youLatLng.lng);

                const label = routeProfile === 'driving' ? 'drive' : 'walk';
                if (routeSummary) {
                    routeSummary.textContent = `${formatDistance(result.distance)} · ~${formatDuration(result.duration)} ${label} to ${nc.name} (updates as you move)`;
                }
                updateJoTip('route');
            } catch (e) {
                if (seq !== routeFetchSeq || !layersOn().you) return;
                const fallback = Mapbox?.showFallbackRoute?.({
                    from: youLatLng,
                    to: { lat: Number(nc.latitude), lng: Number(nc.longitude) },
                    routeGroup,
                    color: '#0b5ed7',
                });
                if (fallback) {
                    routeLayer = fallback.layer;
                    lastRouteAt = Date.now();
                    lastRouteLatLng = leaflet.latLng(youLatLng.lat, youLatLng.lng);
                    if (routeSummary) {
                        routeSummary.textContent = `${formatDistance(fallback.distance)} · ~${formatDuration(fallback.duration)} straight-line to ${nc.name}`;
                    }
                    setRouteStatus('Road route unavailable. Showing a straight line and an estimated time.', false);
                    updateJoTip('route');
                    return;
                }
                if (!routeLayer && routeSummary) {
                    routeSummary.textContent = `Nearest: ${nc.name} (~${formatDistance(nc.distance_m)} straight-line).`;
                    setRouteStatus('Could not load a route for this center.', true);
                }
            }
        }

        function fitActiveView({ animate } = {}) {
            const layers = collectBoundsLayers();
            const on = layersOn();
            const mode = focusMode();
            updateJoTip(mode === 'both' && on.you ? 'both' : mode);
            syncListVisibility();

            if (!layers.length) {
                if (on.you && youLatLng) goToLatLng(youLatLng, 15, animate !== false);
                return;
            }

            if ((mode === 'centers' || mode === 'you' || mode === 'both') && routeLayer) {
                try {
                    const layersWithRoute = [...layers];
                    if (!layersWithRoute.includes(routeLayer)) layersWithRoute.push(routeLayer);
                    map.fitBounds(leaflet.featureGroup(layersWithRoute).getBounds().pad(0.18), {
                        animate: !REDUCE && animate !== false,
                        maxZoom: 16,
                    });
                    return;
                } catch (e) { /* fall through */ }
            }

            try {
                map.fitBounds(leaflet.featureGroup(layers).getBounds().pad(0.14), {
                    animate: !REDUCE && animate !== false,
                    maxZoom: 17,
                });
            } catch (e) { /* empty */ }
        }

        function goToLatLng(latlng, zoom, animate) {
            if (!latlng) return;
            const z = zoom || Math.max(map.getZoom(), 15);
            if (REDUCE || animate === false) map.setView(latlng, z);
            else map.flyTo(latlng, z, { duration: 0.65 });
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

        function flyToCenter(id) {
            const layer = centerLayers.get(Number(id));
            if (!layer) return;
            highlightCenter(id);
            goToLatLng(layer.getLatLng(), Math.max(map.getZoom(), 15), true);
            layer.openPopup();
        }

        function renderRisk(risk) {
            const data = risk || { barangays: [], total_open: 0, geometries: null };
            const hot = (data.barangays || [])
                .filter((b) => (b.open_count || 0) > 0)
                .sort((a, b) => b.open_count - a.open_count);

            if (riskSummary) {
                riskSummary.textContent = data.total_open > 0
                    ? `${data.total_open} open report${data.total_open === 1 ? '' : 's'} across ${hot.length} barangay${hot.length === 1 ? '' : 's'}`
                    : 'No open community reports right now — stay aware and keep reporting hazards.';
            }
            if (riskList) {
                riskList.innerHTML = hot.length
                    ? hot.slice(0, 6).map((b) =>
                        `<div><strong>${esc(b.name)}</strong> · ${esc(b.open_count)} open</div>`
                    ).join('')
                    : '<div>All mapped barangays are calm.</div>';
            }

            riskGroup.clearLayers();
            riskLayer = null;
            if (!data.geometries?.features?.length) return;

            const fill = Mapbox?.riskFillColor || ((n) => (n > 0 ? '#f97316' : '#94a3b8'));
            const opac = Mapbox?.riskFillOpacity || ((n) => (n > 0 ? 0.35 : 0.06));

            riskLayer = leaflet.geoJSON(data.geometries, {
                style: (feature) => {
                    const n = feature?.properties?.open_count || 0;
                    return {
                        color: fill(n),
                        weight: n > 0 ? 1.5 : 0.8,
                        fillColor: fill(n),
                        fillOpacity: opac(n),
                        opacity: 0.85,
                    };
                },
                onEachFeature: (feature, layer) => {
                    const name = feature.properties?.name || 'Barangay';
                    const n = feature.properties?.open_count || 0;
                    layer.bindPopup(
                        `<strong>${esc(name)}</strong><br>` +
                        (n > 0
                            ? `${n} open community report${n === 1 ? '' : 's'} (locations stay private)`
                            : 'No open reports in this barangay')
                    );
                },
            }).addTo(riskGroup);

            if (chkRisk && !chkRisk.checked) map.removeLayer(riskGroup);
        }

        function renderNearest() {
            const box = document.getElementById('nearest-box');
            const hazardBox = document.getElementById('containing-zones-box');
            if (!box) return;

            if (!nearestData?.nearest_center) {
                box.classList.add('d-none');
                box.innerHTML = '';
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
                await fetchRoute({ force: true });
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
                }).bindPopup('My location').addTo(youGroup);
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
                    if (!layersOn().you) return;
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    upsertYou(lat, lng, pos.coords.accuracy || 40);
                    setGeoStatus('');
                    fetchNearest(lat, lng).finally(() => {
                        if (!didInitialYouFit && layersOn().you && youLatLng) {
                            didInitialYouFit = true;
                            fitActiveView({ animate: true });
                        }
                    });
                    if (layersOn().you && !map.hasLayer(youGroup)) map.addLayer(youGroup);
                },
                (err) => {
                    const denied = err?.code === 1;
                    setGeoStatus(
                        denied
                            ? 'Location permission denied. Enable GPS to use my current location.'
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
                            <span class="text-muted" style="font-size:.72rem">${esc(z.type?.name || 'Hazard')}</span>
                        </button>`).join('')
                    : '<div class="small text-muted">No active zones.</div>';
            }

            if (cList) {
                cList.innerHTML = centers.length
                    ? centers.map((c) => `
                        <button type="button" class="rg-hazard-list-item text-start w-100 bg-transparent" data-center-id="${c.id}">
                            <strong class="d-block small">${esc(c.name)}</strong>
                            <span class="text-muted" style="font-size:.72rem">${c.capacity != null ? `Capacity ${esc(c.capacity)}` : 'Open'}</span>
                        </button>`).join('')
                    : '<div class="small text-muted">No open centers.</div>';
            }
        }

        function renderZones(zones) {
            zoneGroup.clearLayers();
            zoneLayers.clear();
            (zones || []).forEach((z) => {
                if (!z.geometry) return;
                const color = z.color || z.type?.color || '#b45309';
                const layer = leaflet.geoJSON(z.geometry, {
                    style: {
                        color,
                        weight: 2,
                        fillColor: color,
                        fillOpacity: 0.22,
                        className: REDUCE ? '' : 'rg-hazard-zone-pulse',
                    },
                });
                const note = z.advisory_note ? `<div class="small mt-1">${esc(z.advisory_note)}</div>` : '';
                layer.bindPopup(`<strong>${esc(z.name)}</strong>${z.type?.name ? `<br><span class="text-muted">${esc(z.type.name)}</span>` : ''}${note}`);
                layer.addTo(zoneGroup);
                zoneLayers.set(Number(z.id), layer);
            });
        }

        function renderCenters(centers) {
            centerGroup.clearLayers();
            centerLayers.clear();
            (centers || []).forEach((c) => {
                const marker = leaflet.marker([Number(c.latitude), Number(c.longitude)], { icon: evacIcon() });
                const cap = c.capacity != null ? `<div class="small text-muted">Capacity: ${esc(c.capacity)}</div>` : '';
                marker.bindPopup(`<strong>${esc(c.name)}</strong><div class="small">Open evacuation center</div>${cap}`);
                marker.addTo(centerGroup);
                centerLayers.set(Number(c.id), marker);
            });
        }

        function applySnapshot(data) {
            renderZones(data.zones || []);
            renderCenters(data.centers || []);
            renderRisk(data.risk || cfg.risk);
            renderLists(data.zones || [], data.centers || []);
            lastUpdated = Date.now();
            setUpdatedLabel();
        }

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
                fitActiveView({ animate: true });
            } else if (which === 'centers') {
                if (checked) map.addLayer(centerGroup);
                else map.removeLayer(centerGroup);
                fitActiveView({ animate: true });
            } else if (which === 'risk') {
                if (checked) map.addLayer(riskGroup);
                else map.removeLayer(riskGroup);
                if (checked) updateJoTip('risk');
                fitActiveView({ animate: true });
            } else if (which === 'you') {
                setLocationEnabled(checked);
            }
        }

        chkZones?.addEventListener('change', (e) => onLayerToggle('zones', e.target.checked));
        chkCenters?.addEventListener('change', (e) => onLayerToggle('centers', e.target.checked));
        chkRisk?.addEventListener('change', (e) => onLayerToggle('risk', e.target.checked));
        chkYou?.addEventListener('change', (e) => onLayerToggle('you', e.target.checked));

        [routeWalk, routeDrive].forEach((el) => {
            el?.addEventListener('change', () => {
                if (!el.checked) return;
                routeProfile = el.value === 'driving' ? 'driving' : 'walking';
                if (!layersOn().you) {
                    if (routeSummary) routeSummary.textContent = 'Turn on My location to see a live path.';
                    return;
                }
                fetchRoute({ force: true }).then(() => fitActiveView({ animate: true }));
            });
        });

        showRouteBox(!!mapboxToken);
        updateJoTip(chkYou?.checked ? 'both' : 'off');

        locateBtn?.addEventListener('click', () => {
            if (!chkYou?.checked) {
                setLocationEnabled(true);
                return;
            }
            if (youLatLng) {
                goToLatLng(youLatLng, 16, true);
                youMarker?.openPopup();
                fetchRoute({ force: true }).then(() => fitActiveView({ animate: true }));
                updateJoTip('you');
            } else {
                setGeoStatus('Locating you…');
                startWatch();
            }
        });

        if (chkYou?.checked) setLocationEnabled(true);

        setTimeout(() => map.invalidateSize(), 200);
        window.addEventListener('resize', () => map.invalidateSize());
        setInterval(setUpdatedLabel, 1000);

        applySnapshot({
            zones: cfg.zones || [],
            centers: cfg.centers || [],
            risk: cfg.risk || null,
        });
        fitActiveView({ animate: false });

        async function softRefresh() {
            if (!cfg.snapshotUrl) return;
            try {
                const res = await fetch(cfg.snapshotUrl, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                applySnapshot(await res.json());
            } catch (e) { /* offline */ }
        }

        setInterval(softRefresh, 60000);
    }

    window.RANIAG_HazardMap = { init };
})();

/**
 * Incident show / dispatch map — Mapbox basemap, scene pin, live units, unit→scene route.
 */
(function (global) {
    'use strict';

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    function unitIcon(label) {
        const name = esc(label || 'Unit');
        return L.divIcon({
            className: 'rg-unit-marker',
            html: `<div class="rg-unit-pin"><i class="bi bi-broadcast-pin"></i></div><div class="rg-unit-label">${name}</div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 17],
        });
    }

    async function init(cfg) {
        const L = global.L;
        const Mapbox = global.RANIAG_Mapbox;
        if (!L || !cfg?.el || cfg.lat == null || cfg.lng == null) return;

        const el = typeof cfg.el === 'string' ? document.getElementById(cfg.el) : cfg.el;
        if (!el) return;

        const map = L.map(el).setView([cfg.lat, cfg.lng], 14);
        if (Mapbox) Mapbox.addBasemap(map, cfg.map || {});
        else {
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OSM',
            }).addTo(map);
        }

        const unitGroup = L.layerGroup().addTo(map);
        const routeGroup = L.layerGroup().addTo(map);
        const token = String(cfg.map?.mapbox_token || '').trim();

        const sceneIcon = global.RaniagIcons?.buildDivIcon
            ? global.RaniagIcons.buildDivIcon({
                icon: cfg.icon,
                color: cfg.color,
                outsideJurisdiction: cfg.outsideJurisdiction === true,
            })
            : undefined;

        const sceneMarker = L.marker([cfg.lat, cfg.lng], sceneIcon ? { icon: sceneIcon } : {})
            .addTo(map)
            .bindPopup(cfg.scenePopup || 'Incident location');

        const statusEl = cfg.statusEl
            ? (typeof cfg.statusEl === 'string' ? document.getElementById(cfg.statusEl) : cfg.statusEl)
            : null;
        const routeColors = ['#16a34a', '#2563eb', '#d97706', '#7c3aed', '#0891b2'];
        let localFix = null;
        let serverUnits = [];
        let serverAssigned = [];
        let fitted = false;
        const markerByKey = new Map();
        const routeBuckets = new Map();
        const routeDrawnAt = new Map();
        const routeResults = new Map();

        function asList(value) {
            if (Array.isArray(value)) return value;
            if (value && typeof value === 'object') return Object.values(value);
            return [];
        }

        function mergeUnits(units) {
            const list = asList(units);
            if (!localFix) return list;
            const kept = list.filter((u) => {
                const lat = Number(u.latitude);
                const lng = Number(u.longitude);
                if (Number.isNaN(lat) || Number.isNaN(lng)) return true;
                return Math.abs(lat - localFix.latitude) > 0.0008
                    || Math.abs(lng - localFix.longitude) > 0.0008;
            });
            return [localFix, ...kept];
        }

        function frameRoute(bounds) {
            const view = map.getBounds();
            if (fitted && view && view.contains(bounds)) return;
            const animate = fitted;
            fitted = true;
            try {
                map.fitBounds(bounds.pad(0.28), { maxZoom: 16, padding: [36, 36], animate });
            } catch (e) { /* */ }
        }

        async function drawRoute(unit, color) {
            if (!Mapbox) return null;
            const key = String(unit.id || unit.label || 'Unit');
            const from = { lat: Number(unit.latitude), lng: Number(unit.longitude) };
            const to = { lat: cfg.lat, lng: cfg.lng };
            let bucket = routeBuckets.get(key);
            if (!bucket) {
                bucket = L.layerGroup().addTo(routeGroup);
                routeBuckets.set(key, bucket);
            }
            const stamp = routeDrawnAt.get(key) || 0;
            if (routeResults.has(key) && (Date.now() - stamp) < 8000) {
                return routeResults.get(key);
            }
            routeDrawnAt.set(key, Date.now());
            bucket.clearLayers();
            let result = null;
            if (token) {
                try {
                    result = await Mapbox.fetchDirections({
                        token,
                        from,
                        to,
                        profile: 'driving',
                        routeGroup: bucket,
                        color,
                        replace: false,
                    });
                } catch (e) { /* directions unavailable */ }
            }
            if (!result && Mapbox.showFallbackRoute) {
                result = Mapbox.showFallbackRoute({
                    from,
                    to,
                    routeGroup: bucket,
                    color,
                    replace: false,
                });
            }
            if (result) routeResults.set(key, result);
            return result;
        }

        async function drawUnits(units, assigned) {
            const raw = mergeUnits(units);
            const list = Mapbox?.spreadUnitPositions ? Mapbox.spreadUnitPositions(raw) : raw;
            const bounds = L.latLngBounds([[cfg.lat, cfg.lng]]);
            const seen = new Set();
            const plotted = [];

            list.forEach((u) => {
                const lat = Number(u.latitude);
                const lng = Number(u.longitude);
                if (Number.isNaN(lat) || Number.isNaN(lng)) return;
                plotted.push(u);
                bounds.extend([lat, lng]);
                const key = String(u.id || u.assignment_id || u.label || 'Unit');
                seen.add(key);
                const existing = markerByKey.get(key);
                if (existing) {
                    existing.setLatLng([lat, lng]);
                    existing.setIcon(unitIcon(u.label));
                    existing.setPopupContent(`<strong>${esc(u.label)}</strong><br>${esc(u.field_phase || 'assigned')}`);
                } else {
                    const marker = L.marker([lat, lng], { icon: unitIcon(u.label), zIndexOffset: 800 })
                        .bindPopup(`<strong>${esc(u.label)}</strong><br>${esc(u.field_phase || 'assigned')}`)
                        .addTo(unitGroup);
                    markerByKey.set(key, marker);
                }
            });

            markerByKey.forEach((marker, key) => {
                if (!seen.has(key)) {
                    unitGroup.removeLayer(marker);
                    markerByKey.delete(key);
                }
            });
            routeBuckets.forEach((bucket, key) => {
                if (!seen.has(key)) {
                    routeGroup.removeLayer(bucket);
                    routeBuckets.delete(key);
                    routeDrawnAt.delete(key);
                    routeResults.delete(key);
                }
            });

            if (!plotted.length) {
                if (statusEl) {
                    const names = asList(assigned).map((row) => row && row.label).filter(Boolean);
                    statusEl.textContent = names.length
                        ? `${names.join(', ')} is assigned. The route is drawn when a responder marks En route and shares location.`
                        : 'No agency is assigned yet.';
                }
                return;
            }

            frameRoute(bounds);

            const routes = await Promise.all(plotted.map((unit, index) => (
                drawRoute(unit, routeColors[index % routeColors.length])
            )));

            if (!statusEl) return;
            if (plotted.length === 1 && routes[0] && Mapbox) {
                const kind = routes[0].fallback ? 'straight-line' : 'drive';
                const fresh = plotted[0].stale ? ' · last shared location' : '';
                statusEl.textContent =
                    `${plotted[0].label} · ${Mapbox.formatDistance(routes[0].distance)} · ~${Mapbox.formatDuration(routes[0].duration)} ${kind}${fresh}`;
            } else {
                const names = plotted.map((unit) => unit.label).filter(Boolean);
                statusEl.textContent = `${names.join(', ')} · ${plotted.length} live locations routed to the incident`;
            }
        }

        async function refresh() {
            if (!cfg.unitsUrl) {
                await drawUnits(cfg.units || [], []);
                return;
            }
            try {
                const glue = String(cfg.unitsUrl).includes('?') ? '&' : '?';
                const res = await fetch(`${cfg.unitsUrl}${glue}_=${Date.now()}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                if (!res.ok) {
                    if (statusEl && !unitGroup.getLayers().length) {
                        statusEl.textContent = 'Could not load the assigned agency on the map. Refresh this page.';
                    }
                    return;
                }
                const data = await res.json();
                serverUnits = asList(data.units);
                serverAssigned = asList(data.assigned);
                await drawUnits(serverUnits, serverAssigned);
            } catch (e) { /* offline */ }
        }

        document.addEventListener('raniag:gps', (event) => {
            const lat = Number(event.detail?.lat);
            const lng = Number(event.detail?.lng);
            if (Number.isNaN(lat) || Number.isNaN(lng)) return;
            localFix = {
                id: 'self',
                label: cfg.selfLabel || 'You',
                field_phase: 'en_route',
                latitude: lat,
                longitude: lng,
            };
            drawUnits(serverUnits, serverAssigned);
        });

        await refresh();
        if (cfg.unitsUrl) setInterval(refresh, cfg.pollMs || 15000);
        [100, 350, 800].forEach((ms) => setTimeout(() => map.invalidateSize(), ms));
        if (typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(() => map.invalidateSize()).observe(el.parentElement || el);
        }

        return { map, refresh, sceneMarker };
    }

    global.RANIAG_DispatchMap = { init };
})(window);

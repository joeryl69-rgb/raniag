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
            html: `<div class="rg-unit-pin"><i class="bi bi-truck"></i></div><div class="rg-unit-label">${name}</div>`,
            iconSize: [160, 58],
            iconAnchor: [80, 22],
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
        let localFix = null;
        let followMe = false;
        let fitted = false;
        let lastRouteAt = 0;
        const markerByKey = new Map();

        async function drawUnits(units) {
            const list = Array.isArray(units) ? units : [];
            const bounds = L.latLngBounds([[cfg.lat, cfg.lng]]);
            let primary = null;
            let plotted = 0;
            const seen = new Set();

            list.forEach((u) => {
                const lat = Number(u.latitude);
                const lng = Number(u.longitude);
                if (Number.isNaN(lat) || Number.isNaN(lng)) return;
                plotted += 1;
                if (!primary) primary = u;
                bounds.extend([lat, lng]);
                const key = String(u.label || 'Unit');
                seen.add(key);
                const existing = markerByKey.get(key);
                if (existing) {
                    existing.setLatLng([lat, lng]);
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

            if (statusEl && !plotted) {
                statusEl.textContent = list.length
                    ? 'Assigned, but no GPS yet. Tap My location, then mark En route.'
                    : 'No live responder GPS yet. Tap My location on the map while en route.';
            }

            if (!plotted) return;

            const movedEnough = !lastRouteAt || (Date.now() - lastRouteAt) > 12000;
            if (token && Mapbox && primary && movedEnough) {
                lastRouteAt = Date.now();
                try {
                    const result = await Mapbox.fetchDirections({
                        token,
                        from: { lat: Number(primary.latitude), lng: Number(primary.longitude) },
                        to: { lat: cfg.lat, lng: cfg.lng },
                        profile: 'driving',
                        routeGroup,
                        color: '#16a34a',
                    });
                    if (result && statusEl) {
                        statusEl.textContent =
                            `${esc(primary.label)} · ${Mapbox.formatDistance(result.distance)} · ~${Mapbox.formatDuration(result.duration)} drive`;
                    }
                } catch (e) {
                    if (statusEl) {
                        statusEl.textContent = `${esc(primary.label)} is moving · route unavailable`;
                    }
                }
            } else if (statusEl && primary && !movedEnough) {
                /* keep the last distance/ETA while the pin keeps moving */
            } else if (statusEl) {
                statusEl.textContent = `${plotted} live unit${plotted === 1 ? '' : 's'} on the map`;
            }

            if (followMe && localFix) {
                map.panTo([localFix.latitude, localFix.longitude], { animate: true });
            } else if (!fitted) {
                fitted = true;
                try {
                    map.fitBounds(bounds.pad(0.2), { maxZoom: 15, padding: [28, 28] });
                } catch (e) { /* */ }
            }
        }

        async function refresh() {
            if (!cfg.unitsUrl) {
                await drawUnits(cfg.units || []);
                return;
            }
            try {
                const res = await fetch(cfg.unitsUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    if (statusEl && !unitGroup.getLayers().length) {
                        statusEl.textContent = `Responder feed failed (${res.status}). Refresh this page.`;
                    }
                    return;
                }
                const data = await res.json();
                const units = data.units || [];
                await drawUnits(units.length ? units : (localFix ? [localFix] : []));
            } catch (e) { /* offline */ }
        }

        if (cfg.locate) {
            const Locate = L.control({ position: 'topleft' });
            Locate.onAdd = function () {
                const btn = L.DomUtil.create('button', 'rg-map-locate');
                btn.type = 'button';
                btn.innerHTML = '<i class="bi bi-crosshair"></i> My location';
                L.DomEvent.disableClickPropagation(btn);
                L.DomEvent.on(btn, 'click', (event) => {
                    L.DomEvent.stop(event);
                    followMe = true;
                    btn.classList.add('is-on');
                    btn.innerHTML = '<i class="bi bi-crosshair"></i> Sharing';
                    global.RANIAG_LocationPing?.enableFromGesture();
                });
                return btn;
            };
            Locate.addTo(map);
        }

        document.addEventListener('raniag:gps', (event) => {
            const lat = Number(event.detail?.lat);
            const lng = Number(event.detail?.lng);
            if (Number.isNaN(lat) || Number.isNaN(lng)) return;
            localFix = {
                label: cfg.selfLabel || 'You',
                field_phase: 'en_route',
                latitude: lat,
                longitude: lng,
            };
            drawUnits([localFix]);
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

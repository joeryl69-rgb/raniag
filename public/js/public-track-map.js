/**
 * Public tracking page — scene pin + approaching agency units + Mapbox route.
 */
(function (global) {
    'use strict';

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    function unitIcon(label) {
        const name = esc(label || 'Responder');
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

        const el = document.getElementById(cfg.el);
        if (!el) return;

        const map = L.map(el).setView([cfg.lat, cfg.lng], 14);
        if (Mapbox) Mapbox.addBasemap(map, cfg.map || {});
        else {
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OSM',
            }).addTo(map);
        }

        L.circleMarker([cfg.lat, cfg.lng], {
            radius: 8,
            color: '#fff',
            weight: 2,
            fillColor: '#dc2626',
            fillOpacity: 1,
        }).bindPopup('Report location').addTo(map);

        const unitGroup = L.layerGroup().addTo(map);
        const routeGroup = L.layerGroup().addTo(map);
        const token = String(cfg.map?.mapbox_token || '').trim();
        const statusEl = document.getElementById(cfg.statusEl || 'track-units-status');

        async function paint(units) {
            unitGroup.clearLayers();
            routeGroup.clearLayers();
            const list = Array.isArray(units) ? units : [];

            const bounds = L.latLngBounds([[cfg.lat, cfg.lng]]);
            let plotted = 0;
            list.forEach((u) => {
                const lat = Number(u.latitude);
                const lng = Number(u.longitude);
                if (Number.isNaN(lat) || Number.isNaN(lng)) return;
                plotted += 1;
                bounds.extend([lat, lng]);
                L.marker([lat, lng], { icon: unitIcon(u.label), zIndexOffset: 800 })
                    .bindPopup(`<strong>${esc(u.label)}</strong><br>${esc(u.field_phase || 'assigned')}`)
                    .addTo(unitGroup);
            });

            if (statusEl) {
                statusEl.textContent = plotted
                    ? `${plotted} responding unit${plotted === 1 ? '' : 's'} on the map`
                    : (list.length
                        ? 'A unit is assigned, but their GPS has not arrived yet.'
                        : 'Responders appear here once they are en route and this device is sharing location.');
            }

            if (!plotted) return;

            const primary = list[0];
            if (token && Mapbox && primary) {
                try {
                    const result = await Mapbox.fetchDirections({
                        token,
                        from: { lat: Number(primary.latitude), lng: Number(primary.longitude) },
                        to: { lat: cfg.lat, lng: cfg.lng },
                        profile: 'driving',
                        routeGroup,
                    });
                    if (result && statusEl) {
                        statusEl.textContent =
                            `${esc(primary.label)} · ${Mapbox.formatDistance(result.distance)} · ~${Mapbox.formatDuration(result.duration)} away`;
                    } else if (statusEl) {
                        statusEl.textContent = `${esc(primary.label)} is on the map · route unavailable`;
                    }
                } catch (e) {
                    if (statusEl) statusEl.textContent = `${esc(primary.label)} is on the map · route unavailable`;
                }
            } else if (statusEl && primary) {
                statusEl.textContent = `${esc(primary.label)} is on the map`;
            }

            try {
                // Prefer a modest pad + capped zoom so a wide desktop
                // aspect-ratio frame doesn't over-fit into a stretched look.
                map.fitBounds(bounds.pad(0.18), {
                    maxZoom: 14,
                    padding: [28, 28],
                });
            } catch (e) { /* */ }
        }

        async function refresh() {
            if (!cfg.unitsUrl) {
                if (statusEl) statusEl.textContent = 'Responder feed is not configured.';
                return;
            }
            try {
                const res = await fetch(cfg.unitsUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    if (statusEl) statusEl.textContent = `Could not load responders (${res.status}).`;
                    return;
                }
                const data = await res.json();
                await paint(data.units || []);
            } catch (e) {
                if (statusEl) statusEl.textContent = 'Could not load responders.';
            }
        }

        if (Array.isArray(cfg.units) && cfg.units.length) {
            await paint(cfg.units);
        }
        await refresh();
        setInterval(refresh, cfg.pollMs || 12000);
        // Invalidate after layout settles so aspect-ratio containers paint correctly.
        [100, 350, 800].forEach((ms) => setTimeout(() => map.invalidateSize(), ms));
        if (typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(() => map.invalidateSize()).observe(el.parentElement || el);
        }
    }

    global.RANIAG_TrackMap = { init };
})(window);

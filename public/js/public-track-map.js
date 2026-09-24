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

    function unitIcon() {
        return L.divIcon({
            className: 'rg-unit-marker',
            html: '<div style="width:14px;height:14px;border-radius:50%;background:#0b5ed7;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.3)"></div>',
            iconSize: [14, 14],
            iconAnchor: [7, 7],
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

            if (statusEl) {
                statusEl.textContent = list.length
                    ? `${list.length} responding unit${list.length === 1 ? '' : 's'} approaching`
                    : 'Responders will appear here live once they are en route and sharing location.';
            }

            if (!list.length) return;

            const bounds = L.latLngBounds([[cfg.lat, cfg.lng]]);
            list.forEach((u) => {
                const lat = Number(u.latitude);
                const lng = Number(u.longitude);
                if (Number.isNaN(lat) || Number.isNaN(lng)) return;
                bounds.extend([lat, lng]);
                L.marker([lat, lng], { icon: unitIcon() })
                    .bindPopup(`<strong>${esc(u.label)}</strong><br>${esc(u.field_phase || 'assigned')}`)
                    .addTo(unitGroup);
            });

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
                    }
                } catch (e) { /* markers only */ }
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
            if (!cfg.unitsUrl) return;
            try {
                const res = await fetch(cfg.unitsUrl, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                await paint(data.units || []);
            } catch (e) { /* */ }
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

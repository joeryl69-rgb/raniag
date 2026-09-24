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

    function unitIcon() {
        return L.divIcon({
            className: 'rg-unit-marker',
            html: '<div style="width:16px;height:16px;border-radius:50%;background:#16a34a;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.35)"></div>',
            iconSize: [16, 16],
            iconAnchor: [8, 8],
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

        async function drawUnits(units) {
            unitGroup.clearLayers();
            routeGroup.clearLayers();
            const list = Array.isArray(units) ? units : [];

            if (statusEl) {
                statusEl.textContent = list.length
                    ? `${list.length} live unit${list.length === 1 ? '' : 's'} · updates every ~15s`
                    : 'No live responder GPS yet — units appear after they share location while en route.';
            }

            if (!list.length) {
                map.setView([cfg.lat, cfg.lng], 14);
                return;
            }

            const bounds = L.latLngBounds([[cfg.lat, cfg.lng]]);
            let primary = list[0];

            list.forEach((u) => {
                const lat = Number(u.latitude);
                const lng = Number(u.longitude);
                if (Number.isNaN(lat) || Number.isNaN(lng)) return;
                bounds.extend([lat, lng]);
                L.marker([lat, lng], { icon: unitIcon() })
                    .bindPopup(`<strong>${esc(u.label)}</strong><br>${esc(u.field_phase || 'assigned')}`)
                    .addTo(unitGroup);
            });

            if (token && Mapbox && primary) {
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
                            `${list.length} live unit${list.length === 1 ? '' : 's'} · ` +
                            `${Mapbox.formatDistance(result.distance)} · ~${Mapbox.formatDuration(result.duration)} drive`;
                    }
                } catch (e) { /* keep markers */ }
            }

            try {
                map.fitBounds(bounds.pad(0.25), { maxZoom: 15 });
            } catch (e) { /* */ }
        }

        async function refresh() {
            if (!cfg.unitsUrl) {
                await drawUnits(cfg.units || []);
                return;
            }
            try {
                const res = await fetch(cfg.unitsUrl, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                await drawUnits(data.units || []);
            } catch (e) { /* offline */ }
        }

        await refresh();
        if (cfg.unitsUrl) setInterval(refresh, cfg.pollMs || 15000);
        setTimeout(() => map.invalidateSize(), 200);

        return { map, refresh, sceneMarker };
    }

    global.RANIAG_DispatchMap = { init };
})(window);

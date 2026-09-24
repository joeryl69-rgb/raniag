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

        async function drawUnits(units) {
            unitGroup.clearLayers();
            routeGroup.clearLayers();
            const list = Array.isArray(units) ? units : [];

            const bounds = L.latLngBounds([[cfg.lat, cfg.lng]]);
            let primary = null;
            let plotted = 0;

            list.forEach((u) => {
                const lat = Number(u.latitude);
                const lng = Number(u.longitude);
                if (Number.isNaN(lat) || Number.isNaN(lng)) return;
                plotted += 1;
                if (!primary) primary = u;
                bounds.extend([lat, lng]);
                L.marker([lat, lng], { icon: unitIcon(u.label), zIndexOffset: 800 })
                    .bindPopup(`<strong>${esc(u.label)}</strong><br>${esc(u.field_phase || 'assigned')}`)
                    .addTo(unitGroup);
            });

            if (statusEl && !plotted) {
                statusEl.textContent = list.length
                    ? 'Assigned, but no GPS yet. On the responder phone, mark En route and allow location.'
                    : 'No live responder GPS yet — units appear after they share location while en route.';
            }

            if (!plotted) {
                map.setView([cfg.lat, cfg.lng], 14);
                return;
            }

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
                            `${esc(primary.label)} · ${Mapbox.formatDistance(result.distance)} · ~${Mapbox.formatDuration(result.duration)} drive`;
                    } else if (statusEl) {
                        statusEl.textContent = `${plotted} live unit${plotted === 1 ? '' : 's'} on the map · route unavailable`;
                    }
                } catch (e) {
                    if (statusEl) {
                        statusEl.textContent = `${plotted} live unit${plotted === 1 ? '' : 's'} on the map · route unavailable`;
                    }
                }
            } else if (statusEl) {
                statusEl.textContent = `${plotted} live unit${plotted === 1 ? '' : 's'} on the map`;
            }

            try {
                map.fitBounds(bounds.pad(0.2), { maxZoom: 14, padding: [24, 24] });
            } catch (e) { /* */ }
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

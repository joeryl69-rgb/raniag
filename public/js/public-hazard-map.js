/**
 * Public live hazard map — Leaflet layers, pulse styling, soft refresh.
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

        function setUpdatedLabel() {
            const el = document.getElementById('hazard-updated');
            if (!el) return;
            const sec = Math.max(0, Math.round((Date.now() - lastUpdated) / 1000));
            el.textContent = sec < 5 ? 'Updated just now' : `Updated ${sec}s ago`;
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
        }

        function syncZones(zones) {
            const ids = new Set(zones.map((z) => z.id));
            zoneLayers.forEach((layer, id) => {
                if (!ids.has(id)) {
                    zoneGroup.removeLayer(layer);
                    zoneLayers.delete(id);
                    const row = document.querySelector(`[data-zone-id="${id}"]`);
                    row?.classList.add('is-flash');
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
                    layer.on('mouseover click', () => {
                        document.querySelectorAll('[data-zone-id]').forEach((el) => el.classList.toggle('is-active', Number(el.dataset.zoneId) === Number(z.id)));
                    });
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
                marker.addTo(centerGroup);
                centerLayers.set(c.id, marker);
            });
        }

        function fitAll() {
            const layers = [...zoneLayers.values(), ...centerLayers.values()];
            if (!layers.length) return;
            const group = leaflet.featureGroup(layers);
            try {
                map.fitBounds(group.getBounds().pad(0.12));
            } catch (e) { /* empty */ }
        }

        function applyData(zones, centers, { fit } = {}) {
            syncZones(zones);
            syncCenters(centers);
            renderLists(zones, centers);
            lastUpdated = Date.now();
            setUpdatedLabel();
            if (fit) fitAll();
        }

        applyData(cfg.zones || [], cfg.centers || [], { fit: true });

        document.getElementById('zone-list')?.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-zone-id]');
            if (!btn) return;
            const layer = zoneLayers.get(Number(btn.dataset.zoneId));
            if (layer) {
                map.fitBounds(layer.getBounds().pad(0.2));
                layer.openPopup();
            }
        });
        document.getElementById('center-list')?.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-center-id]');
            if (!btn) return;
            const layer = centerLayers.get(Number(btn.dataset.centerId));
            if (layer) {
                map.setView(layer.getLatLng(), Math.max(map.getZoom(), 15));
                layer.openPopup();
            }
        });

        document.getElementById('layer-zones')?.addEventListener('change', (e) => {
            if (e.target.checked) map.addLayer(zoneGroup);
            else map.removeLayer(zoneGroup);
        });
        document.getElementById('layer-centers')?.addEventListener('change', (e) => {
            if (e.target.checked) map.addLayer(centerGroup);
            else map.removeLayer(centerGroup);
        });
        document.getElementById('layer-you')?.addEventListener('change', (e) => {
            if (e.target.checked) map.addLayer(youGroup);
            else map.removeLayer(youGroup);
        });

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(async (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                const acc = pos.coords.accuracy || 40;
                youAccuracy = leaflet.circle([lat, lng], {
                    radius: acc,
                    color: '#3d8bfd',
                    weight: 1,
                    fillColor: '#3d8bfd',
                    fillOpacity: 0.15,
                    className: REDUCE ? '' : 'rg-you-accuracy',
                }).addTo(youGroup);
                youMarker = leaflet.circleMarker([lat, lng], {
                    radius: 7,
                    color: '#fff',
                    weight: 2,
                    fillColor: '#0b5ed7',
                    fillOpacity: 1,
                }).bindPopup('You').addTo(youGroup);

                try {
                    const res = await fetch(`${cfg.nearestUrl}?lat=${lat}&lng=${lng}`);
                    const data = await res.json();
                    const box = document.getElementById('nearest-box');
                    if (box && data.nearest_center) {
                        box.classList.remove('d-none');
                        box.innerHTML = `Nearest open center: <strong>${esc(data.nearest_center.name)}</strong> (~${data.nearest_center.distance_m} m)`;
                    }
                } catch (e) { /* offline */ }
            });
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
            } catch (e) { /* ignore */ }
        }
        setInterval(softRefresh, 60000);
    }

    window.RANIAG_HazardMap = { init };
})();

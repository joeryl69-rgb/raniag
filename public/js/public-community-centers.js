(function () {
    'use strict';

    let map = null;
    let markerLayer = null;
    let mapElement = null;
    let leaflet = null;
    let centersById = new Map();
    let hasFramedCenters = false;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (character) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        }[character]));
    }

    function centerLocation(center) {
        const address = String(center.address || '').trim();
        const barangay = String(center.barangay || '').trim();
        const normalizedAddress = ` ${address.toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim()} `;
        const mentions = (value) => normalizedAddress.includes(` ${String(value).toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim()} `);
        const parts = [address];

        if (barangay && !mentions(barangay)) parts.push(`Barangay ${barangay}`);
        if (!mentions('Pamplona')) parts.push('Pamplona');
        if (!mentions('Cagayan')) parts.push('Cagayan');

        if (address || barangay) return parts.filter(Boolean).join(' · ');

        const latitude = Number(center.latitude);
        const longitude = Number(center.longitude);
        return Number.isFinite(latitude) && Number.isFinite(longitude)
            ? `Pinned location ${latitude.toFixed(5)}, ${longitude.toFixed(5)} · Pamplona, Cagayan`
            : 'Address not provided';
    }

    function centerAvailability(center) {
        const checkedIn = Number(center.checked_in_count);
        const capacity = Number(center.capacity);
        const hasCheckedIn = Number.isFinite(checkedIn) && checkedIn >= 0;
        const occupants = hasCheckedIn ? checkedIn.toLocaleString() : null;

        if (center.is_open !== true) {
            return hasCheckedIn && checkedIn > 0
                ? `Closed to arrivals · ${occupants} inside`
                : 'Closed to arrivals';
        }
        if (!Number.isFinite(capacity) || capacity < 1) {
            return hasCheckedIn && checkedIn > 0
                ? `${occupants} inside · Capacity not listed`
                : 'Capacity not listed';
        }

        const spaces = Math.max(0, capacity - (hasCheckedIn ? checkedIn : 0));
        return `${spaces.toLocaleString()} spaces available · ${occupants ?? '—'} / ${capacity.toLocaleString()} inside`;
    }

    function routeUrl(center) {
        const url = String(center.route_url || '').trim();
        return center.is_open === true && url ? url : null;
    }

    function centerIcon(isOpen) {
        const status = isOpen ? 'is-open' : 'is-closed';
        return leaflet.divIcon({
            className: `rg-center-marker ${status}`,
            html: `<span aria-hidden="true"><i class="bi bi-house-door-fill"></i></span>`,
            iconSize: [30, 30],
            iconAnchor: [15, 29],
            popupAnchor: [0, -27],
        });
    }

    function popupContent(center) {
        const isOpen = center.is_open === true;
        const status = isOpen ? 'Open' : 'Closed';
        const route = routeUrl(center);
        return `<strong>${escapeHtml(center.name)}</strong>
            <div class="rg-center-location">${escapeHtml(centerLocation(center))}</div>
            <div class="rg-center-popup-details">
                <span class="rg-center-popup-status${isOpen ? '' : ' is-closed'}">${status}</span>
                <span class="rg-center-capacity">${escapeHtml(centerAvailability(center))}</span>
            </div>
            ${route ? `<a class="rg-center-directions mt-2" href="${escapeHtml(route)}"><i class="bi bi-sign-turn-right" aria-hidden="true"></i>Route here</a>` : ''}`;
    }

    function renderList(centers) {
        const list = document.getElementById('pd-centers-list');
        if (!list) return;
        if (!centers.length) {
            list.innerHTML = '<div class="text-muted small">No evacuation centers are listed yet.</div>';
            return;
        }

        list.innerHTML = centers.map((center) => {
            const isOpen = center.is_open === true;
            const status = isOpen ? 'Open' : 'Closed';
            const statusClass = isOpen ? 'is-open' : 'is-closed';
            const route = routeUrl(center);
            return `<article class="rg-center-item">
                <button type="button" class="rg-center-select" data-community-center-id="${Number(center.id)}" aria-label="Show ${escapeHtml(center.name)} on the map">
                    <strong>${escapeHtml(center.name)}</strong>
                    <span class="rg-center-status ${statusClass}">${status}</span>
                </button>
                <small class="rg-center-location">${escapeHtml(centerLocation(center))}</small>
                <div class="rg-center-details">
                    <span class="rg-center-capacity">${escapeHtml(centerAvailability(center))}</span>
                    ${route ? `<a class="rg-center-directions" href="${escapeHtml(route)}" aria-label="Route to ${escapeHtml(center.name)}"><i class="bi bi-sign-turn-right" aria-hidden="true"></i>Route here</a>` : ''}
                </div>
            </article>`;
        }).join('');
    }

    function focusCenterOnMap(event) {
        const button = event.target.closest('button[data-community-center-id]');
        if (!button) return;
        const marker = centersById.get(Number(button.dataset.communityCenterId));
        if (!marker) return;
        map.setView(marker.getLatLng(), Math.max(map.getZoom(), 15), { animate: true });
        marker.openPopup();
    }

    function init(mapConfig) {
        mapElement = document.getElementById('community-center-map');
        leaflet = window.L && typeof window.L.map === 'function'
            ? window.L
            : window.RANIAG_Leaflet;
        if (!mapElement || !leaflet) {
            console.error('Community evacuation-center map could not initialize: Leaflet is unavailable.');
            return false;
        }

        const mapOptions = mapConfig || {};
        const center = [Number(mapOptions.default_lat) || 18.45, Number(mapOptions.default_lng) || 121.34];
        map = leaflet.map(mapElement).setView(center, Number(mapOptions.default_zoom) || 13);
        window.RaniagMapScroll?.guard(map);

        const basemap = window.RANIAG_Mapbox?.addBasemap(map, mapOptions, { control: false });
        if (!basemap || basemap.kind === 'none') {
            leaflet.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);
        }

        markerLayer = leaflet.layerGroup().addTo(map);
        mapElement.addEventListener('click', focusCenterOnMap);
        document.getElementById('pd-centers-list')?.addEventListener('click', focusCenterOnMap);

        window.addEventListener('resize', () => map.invalidateSize());
        return true;
    }

    function update(centers) {
        const rows = Array.isArray(centers) ? centers.filter((center) => {
            return Number.isFinite(Number(center.latitude))
                && Number.isFinite(Number(center.longitude));
        }) : [];
        renderList(rows);
        if (!map || !markerLayer) return;

        const currentIds = new Set();
        const validBounds = [];
        rows.forEach((center) => {
            const id = Number(center.id);
            if (!id) return;
            currentIds.add(id);
            const position = [Number(center.latitude), Number(center.longitude)];
            validBounds.push(position);
            const existing = centersById.get(id);
            if (existing) {
                existing.setLatLng(position);
                existing.setIcon(centerIcon(center.is_open === true));
                existing.setPopupContent(popupContent(center));
                return;
            }

            const marker = leaflet.marker(position, {
                icon: centerIcon(center.is_open === true),
                title: String(center.name || 'Evacuation center'),
            }).bindPopup(popupContent(center));
            marker.addTo(markerLayer);
            centersById.set(id, marker);
        });

        centersById.forEach((marker, id) => {
            if (currentIds.has(id)) return;
            markerLayer.removeLayer(marker);
            centersById.delete(id);
        });

        map.invalidateSize();
        if (!hasFramedCenters && validBounds.length) {
            hasFramedCenters = true;
            if (validBounds.length === 1) {
                map.setView(validBounds[0], 15);
            } else {
                map.fitBounds(leaflet.latLngBounds(validBounds).pad(0.12), { maxZoom: 15 });
            }
        }
    }

    window.RANIAG_CommunityCenters = { init, update };
})();

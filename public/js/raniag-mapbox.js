/**
 * Shared Mapbox / Leaflet helpers for RANIAG maps.
 * Uses Mapbox Streets raster tiles + Directions when a pk.* token is set;
 * otherwise falls back to OpenStreetMap.
 */
(function (global) {
    'use strict';

    function normalizeStyle(style) {
        return String(style || 'mapbox/streets-v12').replace(/^mapbox:\/\//, '');
    }

    function addBasemap(map, mapConfig, options) {
        const L = global.L;
        if (!L || !map) return { kind: 'none' };

        const opts = options || {};
        const token = String(mapConfig?.mapbox_token || '').trim();
        const style = normalizeStyle(mapConfig?.mapbox_style);

        if (token) {
            const layer = L.tileLayer(
                `https://api.mapbox.com/styles/v1/${style}/tiles/{z}/{x}/{y}?access_token=${encodeURIComponent(token)}`,
                {
                    tileSize: 512,
                    zoomOffset: -1,
                    maxZoom: 22,
                    attribution: '&copy; <a href="https://www.mapbox.com/about/maps/">Mapbox</a> &copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>',
                }
            );
            if (opts.addTo !== false) layer.addTo(map);
            return { kind: 'mapbox', layer, token, style };
        }

        const layer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OSM',
        });
        if (opts.addTo !== false) layer.addTo(map);
        return { kind: 'osm', layer, token: '', style };
    }

    function formatDistance(meters) {
        if (meters == null || Number.isNaN(meters)) return '';
        if (meters < 1000) return `${Math.round(meters)} m`;
        return `${(meters / 1000).toFixed(meters >= 10000 ? 0 : 1)} km`;
    }

    function formatDuration(seconds) {
        if (seconds == null || Number.isNaN(seconds)) return '';
        const mins = Math.max(1, Math.round(seconds / 60));
        if (mins < 60) return `${mins} min`;
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        return m ? `${h} hr ${m} min` : `${h} hr`;
    }

    function haversineMeters(a, b) {
        if (!a || !b) return Infinity;
        const R = 6371000;
        const dLat = ((b.lat - a.lat) * Math.PI) / 180;
        const dLng = ((b.lng - a.lng) * Math.PI) / 180;
        const lat1 = (a.lat * Math.PI) / 180;
        const lat2 = (b.lat * Math.PI) / 180;
        const h =
            Math.sin(dLat / 2) ** 2 +
            Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;
        return 2 * R * Math.asin(Math.min(1, Math.sqrt(h)));
    }

    /**
     * Fetch a Mapbox Directions route and draw it onto routeGroup.
     * Returns { layer, distance, duration } or null.
     */
    async function fetchDirections(opts) {
        const L = global.L;
        const token = String(opts.token || '').trim();
        if (!L || !token || !opts.from || !opts.to || !opts.routeGroup) return null;

        const profile = opts.profile === 'walking' ? 'walking' : 'driving';
        const from = `${opts.from.lng},${opts.from.lat}`;
        const to = `${opts.to.lng},${opts.to.lat}`;
        const url =
            `https://api.mapbox.com/directions/v5/mapbox/${profile}/${from};${to}` +
            `?geometries=geojson&overview=full&access_token=${encodeURIComponent(token)}`;

        const res = await fetch(url);
        if (!res.ok) throw new Error(`Directions HTTP ${res.status}`);
        const data = await res.json();
        const route = data.routes && data.routes[0];
        if (!route?.geometry) throw new Error('No route');

        const layer = L.geoJSON(route.geometry, {
            style: {
                color: opts.color || '#0b5ed7',
                weight: opts.weight || 5,
                opacity: opts.opacity ?? 0.85,
            },
        });
        opts.routeGroup.clearLayers();
        layer.addTo(opts.routeGroup);

        return {
            layer,
            distance: route.distance,
            duration: route.duration,
            profile,
            fallback: false,
        };
    }

    /**
     * Straight line plus a drive-time estimate when Directions is unavailable.
     * Replaces the previous line only after the new one is ready.
     */
    function showFallbackRoute(opts) {
        const L = global.L;
        if (!L || !opts?.routeGroup || !opts.from || !opts.to) return null;
        const distance = haversineMeters(opts.from, opts.to);
        if (!Number.isFinite(distance)) return null;
        const layer = L.polyline(
            [[opts.from.lat, opts.from.lng], [opts.to.lat, opts.to.lng]],
            {
                color: opts.color || '#0b5ed7',
                weight: opts.weight || 4,
                opacity: opts.opacity ?? 0.85,
                dashArray: '8 10',
            }
        );
        opts.routeGroup.clearLayers();
        layer.addTo(opts.routeGroup);
        return {
            layer,
            distance,
            duration: (distance / 1000) / 28 * 3600,
            profile: 'estimate',
            fallback: true,
        };
    }

    /** Soft choropleth fill for open-incident awareness (0 = calm). */
    function riskFillColor(openCount) {
        const n = Number(openCount) || 0;
        if (n <= 0) return '#94a3b8';
        if (n === 1) return '#fbbf24';
        if (n <= 3) return '#f97316';
        return '#dc2626';
    }

    function riskFillOpacity(openCount) {
        const n = Number(openCount) || 0;
        if (n <= 0) return 0.06;
        if (n === 1) return 0.28;
        if (n <= 3) return 0.4;
        return 0.52;
    }

    global.RANIAG_Mapbox = {
        addBasemap,
        fetchDirections,
        showFallbackRoute,
        formatDistance,
        formatDuration,
        haversineMeters,
        riskFillColor,
        riskFillOpacity,
        normalizeStyle,
    };
})(window);

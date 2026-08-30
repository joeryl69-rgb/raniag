/**
 * Single source of truth for rendering an incident's map marker (icon +
 * color). Every page that plots incidents on a Leaflet map — the
 * dashboard/agency/personnel situational map and each role's incident
 * detail page — calls window.RaniagIcons.buildDivIcon() instead of each
 * keeping its own copy of this logic, so a pin looks identical (same
 * shape, same icon-resolution rules, same fallback) everywhere it shows
 * up. Styling lives in the shared ".raniag-marker-pin" class in
 * public/css/public.css.
 */
(function (window) {
    // Legacy fallback: a handful of old rows may still hold the pre-Bootstrap-
    // Icons slug values (e.g. "fire") instead of a "bi-*" class. New rows
    // always store a valid bi-* class directly (see App\Support\IconLibrary),
    // so this map only exists to keep old data rendering correctly.
    const LEGACY_ICON_MAP = {
        fire: 'bi-fire',
        water: 'bi-droplet-fill',
        shield: 'bi-shield-fill',
        'heart-pulse': 'bi-heart-pulse-fill',
        car: 'bi-car-front-fill',
        'triangle-alert': 'bi-exclamation-triangle-fill',
        building: 'bi-building-fill',
        'circle-help': 'bi-question-circle-fill',
    };

    const FALLBACK_ICON = 'bi-geo-alt-fill';
    const FALLBACK_COLOR = '#64748b';

    function resolveGlyph(rawIcon) {
        if (rawIcon && typeof rawIcon === 'string' && rawIcon.startsWith('bi-')) {
            return rawIcon;
        }
        return LEGACY_ICON_MAP[rawIcon] || FALLBACK_ICON;
    }

    function priorityClass(priority) {
        const p = (priority || '').toString().toLowerCase();
        if (p === 'critical') return 'p-critical';
        if (p === 'high') return 'p-high';
        if (p === 'low') return 'p-low';
        if (p === 'medium') return 'p-medium';
        return '';
    }

    /**
     * @param {Object} opts
     * @param {string} [opts.icon]        Incident type icon (bi-* class or legacy slug)
     * @param {string} [opts.color]       Incident type hex color
     * @param {string} [opts.priority]    critical|high|medium|low — adds a ring color
     * @param {boolean} [opts.outsideJurisdiction] Adds the "outside AOR" ring color
     * @param {number} [opts.size]        Pixel size of the pin (default 30)
     * @returns {L.DivIcon}
     */
    function buildDivIcon(opts) {
        opts = opts || {};
        const glyph = resolveGlyph(opts.icon);
        const color = opts.outsideJurisdiction ? '#fd7e14' : (opts.color || FALLBACK_COLOR);
        const size = opts.size || 30;
        const classes = ['raniag-marker-pin'];
        const pClass = priorityClass(opts.priority);
        if (pClass) classes.push(pClass);
        if (opts.outsideJurisdiction) classes.push('is-outside');

        return L.divIcon({
            html: '<div class="' + classes.join(' ') + '" style="background:' + color + '"><i class="bi ' + glyph + '"></i></div>',
            className: 'raniag-marker-marker',
            iconSize: [size, size],
            iconAnchor: [size / 2, size],
            popupAnchor: [0, -(size - 6)],
        });
    }

    window.RaniagIcons = {
        LEGACY_ICON_MAP,
        FALLBACK_ICON,
        FALLBACK_COLOR,
        resolveGlyph,
        priorityClass,
        buildDivIcon,
    };
})(window);

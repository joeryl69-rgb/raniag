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
        // The incident type's own color is always the fill, matching the
    // Incident Types admin page and the badge shown everywhere else in the
    // system. "Outside AOR" is indicated separately via the .is-outside
    // ring class below, not by overriding the fill color.
    const color = opts.color || FALLBACK_COLOR;
        const size = opts.size || 30;
        const classes = ['raniag-marker-pin'];
        const pClass = priorityClass(opts.priority);
        if (pClass) classes.push(pClass);
        if (opts.outsideJurisdiction) classes.push('is-outside');

        // The pin is a square rotated -45deg with one sharp corner
        // (border-radius: 50% 50% 50% 0), which is the classic "teardrop"
        // trick. Rotating about the box's center moves that sharp
        // corner — the visual tip that should sit exactly on the incident's
        // coordinates — to size/Math.SQRT2 px below center, not to the
        // box's raw bottom edge. Anchoring at [size/2, size] (as if it were
        // an unrotated pin) placed the tip ~0.2*size px too high, so the
        // marker never quite sat on the true GPS point.
        const tipY = size / 2 + size / Math.SQRT2;

        return L.divIcon({
            html: '<div class="' + classes.join(' ') + '" style="background:' + color + '"><i class="bi ' + glyph + '"></i></div>',
            className: 'raniag-marker-marker',
            iconSize: [size, size],
            iconAnchor: [size / 2, tipY],
            popupAnchor: [0, -tipY],
        });
    }

    /**
     * Color used by the round "N reports here" cluster badge (see
     * buildClusterIcon below). Keyed off the highest-priority incident in
     * the group so a cluster containing a critical report still reads as
     * urgent from a distance, same as a single pin would.
     */
    function priorityBadgeColor(priority) {
        const p = (priority || '').toString().toLowerCase();
        if (p === 'critical') return '#b91c1c';
        if (p === 'high') return '#dc3545';
        if (p === 'low') return '#0d6efd';
        return '#f59e0b'; // medium / unspecified
    }

    /**
     * A plain numbered circle marker used in place of several overlapping
     * incident pins that share (or nearly share) one set of coordinates —
     * e.g. redundant submissions reported for the same accident scene.
     * Clicking it is wired up by the calling map to "spiderfy" the group
     * into its individual pins instead of leaving them stacked and
     * unclickable.
     *
     * @param {Object} opts
     * @param {number} opts.count       Number of incidents in the group
     * @param {string} [opts.priority]  Highest priority in the group, drives badge color
     * @param {number} [opts.size]      Pixel diameter of the badge (default 34)
     * @returns {L.DivIcon}
     */
    function buildClusterIcon(opts) {
        opts = opts || {};
        const size = opts.size || 34;
        const color = priorityBadgeColor(opts.priority);
        const count = opts.count || 2;

        return L.divIcon({
            html: '<div class="raniag-marker-cluster" style="width:' + size + 'px;height:' + size + 'px;background:' + color + '"><span>' + count + '</span></div>',
            className: 'raniag-marker-marker',
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2],
            popupAnchor: [0, -(size / 2)],
        });
    }

    window.RaniagIcons = {
        LEGACY_ICON_MAP,
        FALLBACK_ICON,
        FALLBACK_COLOR,
        resolveGlyph,
        priorityClass,
        buildDivIcon,
        priorityBadgeColor,
        buildClusterIcon,
    };
})(window);

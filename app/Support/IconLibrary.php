<?php

namespace App\Support;

/**
 * Single source of truth for the Bootstrap Icons used across RANIAG
 * (incident type badges, dispatch lists, incident detail pages, and map
 * markers). Keeping one catalog here — instead of each view hard-coding
 * its own icon name or defaulting to a generic triangle — is what keeps
 * icons consistent everywhere the icon is rendered.
 */
class IconLibrary
{
    /** Fallback icon used whenever a type has no icon configured. */
    public const FALLBACK = 'bi-exclamation-triangle-fill';

    /** Fallback marker color used whenever a type has no color configured. */
    public const FALLBACK_COLOR = '#64748b';

    /** Small "quick pick" grid shown by default in the icon picker. */
    public const DEFAULT_SET = [
        'bi-exclamation-triangle-fill', 'bi-fire', 'bi-droplet-fill', 'bi-lightning-fill',
        'bi-car-front-fill', 'bi-heart-pulse-fill', 'bi-hospital-fill', 'bi-shield-fill-exclamation',
        'bi-tsunami', 'bi-wind', 'bi-cloud-lightning-rain-fill', 'bi-tree-fill',
        'bi-building-fill-exclamation', 'bi-signpost-split-fill', 'bi-person-fill-exclamation',
        'bi-house-fill-exclamation', 'bi-truck-front-fill', 'bi-bricks', 'bi-radioactive',
        'bi-exclamation-octagon-fill', 'bi-exclamation-diamond-fill', 'bi-bug-fill',
        'bi-water', 'bi-thermometer-sun', 'bi-lungs-fill', 'bi-bandaid-fill',
    ];

    /**
     * Larger searchable catalog, grouped by category, so the admin can
     * search for a fitting icon instead of being limited to DEFAULT_SET.
     * All names are valid classes in the self-hosted bootstrap-icons build
     * (public/vendor/bootstrap-icons, loaded app-wide — see
     * resources/views/layouts/app.blade.php), so no external site or
     * network fetch is ever needed to use any icon in this list.
     */
    public const CATALOG = [
        'Emergency & Alerts' => [
            'bi-exclamation-triangle-fill', 'bi-exclamation-octagon-fill', 'bi-exclamation-diamond-fill',
            'bi-exclamation-circle-fill', 'bi-shield-fill-exclamation', 'bi-shield-fill-x',
            'bi-shield-lock-fill', 'bi-megaphone-fill', 'bi-bell-fill', 'bi-broadcast',
            'bi-siren-fill', 'bi-flag-fill', 'bi-alarm-fill',
        ],
        'Fire & Hazards' => [
            'bi-fire', 'bi-radioactive', 'bi-lightning-fill', 'bi-lightning-charge-fill',
            'bi-cloud-lightning-rain-fill', 'bi-thermometer-sun', 'bi-thermometer-high',
            'bi-fuel-pump-fill', 'bi-battery-charging',
        ],
        'Water & Weather' => [
            'bi-water', 'bi-droplet-fill', 'bi-droplet-half', 'bi-tsunami', 'bi-wind',
            'bi-cloud-rain-heavy-fill', 'bi-cloud-drizzle-fill', 'bi-cloud-haze2-fill',
            'bi-umbrella-fill', 'bi-moisture', 'bi-thermometer-snow', 'bi-snow2',
        ],
        'Medical & Health' => [
            'bi-heart-pulse-fill', 'bi-hospital-fill', 'bi-bandaid-fill', 'bi-lungs-fill',
            'bi-capsule-pill', 'bi-clipboard2-pulse-fill', 'bi-eyedropper', 'bi-virus',
            'bi-virus2', 'bi-person-wheelchair', 'bi-thermometer-half',
        ],
        'Crime & Public Safety' => [
            'bi-shield-fill-exclamation', 'bi-person-fill-exclamation', 'bi-person-fill-lock',
            'bi-handcuffs' , 'bi-eye-fill', 'bi-cone-striped', 'bi-lock-fill', 'bi-key-fill',
        ],
        'Traffic & Transport' => [
            'bi-car-front-fill', 'bi-truck-front-fill', 'bi-bus-front-fill', 'bi-bicycle',
            'bi-signpost-split-fill', 'bi-cone-striped', 'bi-stoplights-fill', 'bi-p-square-fill',
        ],
        'Structures & Infrastructure' => [
            'bi-building-fill-exclamation', 'bi-house-fill-exclamation', 'bi-bricks',
            'bi-tools', 'bi-cone', 'bi-signpost-2-fill', 'bi-tree-fill', 'bi-bridge',
            'bi-lightbulb-fill', 'bi-plug-fill',
        ],
        'General / Other' => [
            'bi-question-circle-fill', 'bi-circle-fill', 'bi-info-circle-fill', 'bi-bug-fill',
            'bi-geo-alt-fill', 'bi-clipboard2-fill', 'bi-people-fill', 'bi-box-seam-fill',
        ],
    ];

    /** Full flat list (deduplicated) — used for validation and search. */
    public static function all(): array
    {
        $flat = array_merge(self::DEFAULT_SET, ...array_values(self::CATALOG));

        return array_values(array_unique($flat));
    }

    /** Resolve a possibly-empty icon value to a safe, always-renderable class. */
    public static function resolve(?string $icon): string
    {
        $icon = trim((string) $icon);

        return $icon !== '' ? $icon : self::FALLBACK;
    }

    /** Resolve a possibly-empty color value to a safe, always-renderable hex color. */
    public static function resolveColor(?string $color): string
    {
        $color = trim((string) $color);

        return preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) ? $color : self::FALLBACK_COLOR;
    }
}

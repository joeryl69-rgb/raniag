<?php

namespace App\Support;

/**
 * Sample color themes for the per-account "System Settings" screen
 * (resources/views/settings/appearance.blade.php). Every value maps
 * 1:1 to a --raniag-* CSS custom property already used throughout
 * public/css/public.css (109 usages across every layout — public,
 * admin, agency, personnel), so switching a preset re-colors the
 * whole interface with no exceptions, not just a few components.
 */
class ThemePresets
{
    public const DEFAULT_KEY = 'ocean';
    public const DEFAULT_FONT_KEY = 'figtree';
    public const DEFAULT_FONT_SIZE = 'normal';

    /**
     * Font choices for the System Settings screen. Each stack
     * leads with a bunny.net-hosted webfont already safe to load (no new
     * <link> needed beyond what's already in layouts/app.blade.php for
     * Figtree) and falls back to sensible system fonts.
     */
    public const FONTS = [
        'figtree' => [
            'label' => 'Figtree (Default)',
            'preview' => 'Aa',
            'stack' => "'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
        ],
        'inter' => [
            'label' => 'Inter',
            'preview' => 'Aa',
            'stack' => "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
        ],
        'system' => [
            'label' => 'System UI',
            'preview' => 'Aa',
            'stack' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif",
        ],
        'serif' => [
            'label' => 'Serif',
            'preview' => 'Aa',
            'stack' => "'Georgia', 'Times New Roman', serif",
        ],
    ];

    /** Relative root font-size per size option — everything using rem scales with it. */
    public const FONT_SIZES = [
        'small' => ['label' => 'Small', 'root_px' => '14px'],
        'normal' => ['label' => 'Default', 'root_px' => '16px'],
        'large' => ['label' => 'Large', 'root_px' => '18px'],
    ];

    public const PRESETS = [
        'ocean' => [
            'label' => 'Ocean Blue (Default)',
            'swatch' => '#0b5ed7',
            'vars' => [
                '--raniag-primary' => '#0b5ed7',
                '--raniag-primary-dark' => '#084298',
                '--raniag-primary-light' => '#e7f0fd',
                '--raniag-accent' => '#3d8bfd',
                '--raniag-accent-dark' => '#0a53c4',
                '--raniag-sidebar' => '#0f172a',
                '--raniag-sidebar-active' => '#1e293b',
                '--raniag-surface' => '#f4f7fb',
                '--raniag-border' => '#dde5ea',
            ],
        ],
        'emerald' => [
            'label' => 'Emerald Green',
            'swatch' => '#0f766e',
            'vars' => [
                '--raniag-primary' => '#0f766e',
                '--raniag-primary-dark' => '#115e59',
                '--raniag-primary-light' => '#e6fffa',
                '--raniag-accent' => '#14b8a6',
                '--raniag-accent-dark' => '#0d9488',
                '--raniag-sidebar' => '#0f2f2c',
                '--raniag-sidebar-active' => '#134e4a',
                '--raniag-surface' => '#f0fdfa',
                '--raniag-border' => '#cbe9e2',
            ],
        ],
        'sunset' => [
            'label' => 'Sunset Amber',
            'swatch' => '#c2410c',
            'vars' => [
                '--raniag-primary' => '#c2410c',
                '--raniag-primary-dark' => '#9a3412',
                '--raniag-primary-light' => '#ffedd5',
                '--raniag-accent' => '#f59e0b',
                '--raniag-accent-dark' => '#d97706',
                '--raniag-sidebar' => '#431407',
                '--raniag-sidebar-active' => '#7c2d12',
                '--raniag-surface' => '#fff7ed',
                '--raniag-border' => '#fed7aa',
            ],
        ],
        'crimson' => [
            'label' => 'Crimson Red',
            'swatch' => '#b91c1c',
            'vars' => [
                '--raniag-primary' => '#b91c1c',
                '--raniag-primary-dark' => '#7f1d1d',
                '--raniag-primary-light' => '#fee2e2',
                '--raniag-accent' => '#ef4444',
                '--raniag-accent-dark' => '#dc2626',
                '--raniag-sidebar' => '#1f0a0a',
                '--raniag-sidebar-active' => '#450a0a',
                '--raniag-surface' => '#fef2f2',
                '--raniag-border' => '#fecaca',
            ],
        ],
        'royal' => [
            'label' => 'Royal Purple',
            'swatch' => '#6d28d9',
            'vars' => [
                '--raniag-primary' => '#6d28d9',
                '--raniag-primary-dark' => '#4c1d95',
                '--raniag-primary-light' => '#ede9fe',
                '--raniag-accent' => '#8b5cf6',
                '--raniag-accent-dark' => '#7c3aed',
                '--raniag-sidebar' => '#1e1033',
                '--raniag-sidebar-active' => '#2e1065',
                '--raniag-surface' => '#f5f3ff',
                '--raniag-border' => '#ddd6fe',
            ],
        ],
        'slate' => [
            'label' => 'Slate Gray',
            'swatch' => '#334155',
            'vars' => [
                '--raniag-primary' => '#334155',
                '--raniag-primary-dark' => '#1e293b',
                '--raniag-primary-light' => '#e2e8f0',
                '--raniag-accent' => '#64748b',
                '--raniag-accent-dark' => '#475569',
                '--raniag-sidebar' => '#0f172a',
                '--raniag-sidebar-active' => '#1e293b',
                '--raniag-surface' => '#f1f5f9',
                '--raniag-border' => '#cbd5e1',
            ],
        ],
    ];

    /**
     * Dark page colors for each preset. The sidebar and buttons keep the
     * theme's own accent; these only darken the page, cards, and fields.
     */
    public const DARK_SURFACES = [
        'ocean' => ['surface' => '#0b1220', 'card' => '#121a2c', 'raised' => '#182338', 'border' => '#2a3b56'],
        'emerald' => ['surface' => '#071412', 'card' => '#0e2220', 'raised' => '#14302c', 'border' => '#1f4741'],
        'sunset' => ['surface' => '#160d08', 'card' => '#24160e', 'raised' => '#321f14', 'border' => '#5a341c'],
        'crimson' => ['surface' => '#140808', 'card' => '#221010', 'raised' => '#301616', 'border' => '#5a2424'],
        'royal' => ['surface' => '#100818', 'card' => '#1a1028', 'raised' => '#241638', 'border' => '#3d2860'],
        'slate' => ['surface' => '#0e141b', 'card' => '#17202a', 'raised' => '#1e2a36', 'border' => '#334155'],
    ];

    public static function darkPalette(string $key): array
    {
        return self::DARK_SURFACES[$key] ?? self::DARK_SURFACES[self::DEFAULT_KEY];
    }

    public static function vars(string $key): array
    {
        return self::PRESETS[$key]['vars'] ?? self::PRESETS[self::DEFAULT_KEY]['vars'];
    }

    public static function isValidKey(string $key): bool
    {
        return array_key_exists($key, self::PRESETS);
    }

    public static function isValidFontKey(string $key): bool
    {
        return array_key_exists($key, self::FONTS);
    }

    public static function isValidFontSize(string $key): bool
    {
        return array_key_exists($key, self::FONT_SIZES);
    }

    public static function fontStack(string $key): string
    {
        return self::FONTS[$key]['stack'] ?? self::FONTS[self::DEFAULT_FONT_KEY]['stack'];
    }

    public static function fontRootPx(string $key): string
    {
        return self::FONT_SIZES[$key]['root_px'] ?? self::FONT_SIZES[self::DEFAULT_FONT_SIZE]['root_px'];
    }

    /** Renders the current theme's variables (plus color-mode / font adjustments) as a <style> body. */
    public static function cssVariables(string $key, bool $darkMode = false, string $fontKey = self::DEFAULT_FONT_KEY, string $fontSize = self::DEFAULT_FONT_SIZE, bool $followSystem = false): string
    {
        $vars = self::vars($key);
        $vars['--raniag-card'] = '#ffffff';
        $vars['--raniag-card-raised'] = '#f8fafc';
        $vars['--raniag-ink'] = '#0f172a';
        $vars['--raniag-muted'] = '#64748b';
        $vars['--raniag-card-shadow'] = '0 0.5rem 1.5rem rgba(15, 23, 42, 0.08)';
        $vars['--raniag-font-family'] = self::fontStack($fontKey);
        $vars['--raniag-font-root'] = self::fontRootPx($fontSize);
        $vars['--bs-body-color'] = '#1e293b';
        $vars['--bs-body-bg'] = $vars['--raniag-surface'];
        $vars['--bs-secondary-color'] = '#64748b';
        $vars['--bs-secondary-bg'] = '#ffffff';
        $vars['--bs-tertiary-bg'] = '#f8fafc';
        $vars['--bs-emphasis-color'] = '#0f172a';
        $vars['color-scheme'] = 'light';

        $lines = [];
        foreach ($vars as $name => $value) {
            $lines[] = "    {$name}: {$value};";
        }

        $css = ":root {\n".implode("\n", $lines)."\n}";

        if ($darkMode || $followSystem) {
            $dark = self::darkPalette($key);
            $css .= "\n:root[data-theme=\"dark\"] {\n"
                ."    --raniag-surface: {$dark['surface']};\n"
                ."    --raniag-border: {$dark['border']};\n"
                ."    --raniag-card: {$dark['card']};\n"
                ."    --raniag-card-raised: {$dark['raised']};\n"
                ."    --raniag-ink: #e8eef6;\n"
                ."    --raniag-muted: #c5d4e4;\n"
                ."    --raniag-primary-light: rgba(255, 255, 255, 0.08);\n"
                ."    --raniag-card-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.45);\n"
                ."    --bs-body-color: #e8eef6;\n"
                ."    --bs-body-bg: {$dark['surface']};\n"
                ."    --bs-secondary-color: #c5d4e4;\n"
                ."    --bs-secondary-bg: {$dark['card']};\n"
                ."    --bs-tertiary-bg: {$dark['raised']};\n"
                ."    --bs-emphasis-color: #f8fafc;\n"
                ."    color-scheme: dark;\n"
                ."}";
        }

        return $css;
    }
}

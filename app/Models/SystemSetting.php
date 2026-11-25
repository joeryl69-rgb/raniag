<?php

namespace App\Models;

use App\Support\ThemePresets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SystemSetting extends Model
{
    protected $fillable = [
        'theme_key',
        'dark_mode',
        'follow_system',
        'font_key',
        'font_size',
        'updated_by',
        'municipality_boundary',
        'municipality_boundary_updated_by',
        'municipality_boundary_updated_at',
        'system_name',
        'organization_name',
        'logo_path',
        'primary_color',
        'accent_color',
        'page_background_color',
        'header_text',
        'footer_text',
        'show_header_text',
        'show_footer_text',
        'contact_address',
        'contact_phone',
        'contact_email',
        'contact_hotline',
        'municipality_label',
        'province_label',
        'response_area_label',
        'public_home_content',
    ];

    protected function casts(): array
    {
        return [
            'dark_mode' => 'boolean',
            'follow_system' => 'boolean',
            'municipality_boundary' => 'array',
            'municipality_boundary_updated_at' => 'datetime',
            'show_header_text' => 'boolean',
            'show_footer_text' => 'boolean',
            'public_home_content' => 'array',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function municipalityBoundaryUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'municipality_boundary_updated_by');
    }

    /**
     * Singleton accessor — global branding and the editable municipal
     * boundary share one settings row. Personal appearance lives on User.
     */
    public static function current(): self
    {
        return once(function () {
            return static::unguarded(fn () => static::firstOrCreate(['id' => 1], [
                'theme_key' => ThemePresets::DEFAULT_KEY,
                'dark_mode' => false,
                'follow_system' => false,
                'font_key' => ThemePresets::DEFAULT_FONT_KEY,
                'font_size' => ThemePresets::DEFAULT_FONT_SIZE,
            ]));
        });
    }

    /**
     * The administrator-managed, instance-wide brand and public identity.
     * Geographic labels are presentation values; they do not affect map
     * geometry or jurisdiction checks.
     *
     * @return array<string, string|bool>
     */
    public function brandingValues(): array
    {
        $systemName = $this->system_name ?: config('system_branding.name', 'RANIAG');
        $organization = $this->organization_name ?: config('system_branding.organization', 'MDRRMO Pamplona');
        $municipality = $this->municipality_label ?: config('system_branding.municipality', 'Pamplona');
        $province = $this->province_label ?: config('system_branding.province', 'Cagayan');
        $primaryColor = $this->primary_color ?: config('system_branding.primary_color', '#086b78');
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $primaryColor)) {
            $primaryColor = '#086b78';
        }
        $accentColor = $this->accent_color ?: config('system_branding.accent_color', '#29aa96');
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $accentColor)) {
            $accentColor = '#29aa96';
        }
        $pageBackgroundColor = $this->page_background_color ?: config('system_branding.page_background_color', '#eaf0f2');
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $pageBackgroundColor)) {
            $pageBackgroundColor = '#eaf0f2';
        }

        return [
            'system_name' => $systemName,
            'organization_name' => $organization,
            'logo_url' => $this->logo_path
                ? Storage::disk('public')->url($this->logo_path)
                : asset('/images/icons/raniag-master.svg'),
            'primary_color' => $primaryColor,
            'primary_color_rgb' => implode(', ', array_map('hexdec', str_split(substr($primaryColor, 1), 2))),
            'accent_color' => $accentColor,
            'page_background_color' => $pageBackgroundColor,
            'header_text' => $this->header_text ?? '',
            'footer_text' => $this->footer_text ?? '',
            'show_header_text' => (bool) ($this->show_header_text ?? true),
            'show_footer_text' => (bool) ($this->show_footer_text ?? true),
            'contact_address' => $this->contact_address ?? '',
            'contact_phone' => $this->contact_phone ?? '',
            'contact_email' => $this->contact_email ?? '',
            'contact_hotline' => $this->contact_hotline ?? '',
            'municipality_label' => $municipality,
            'province_label' => $province,
            'response_area_label' => $this->response_area_label ?: config('system_branding.response_area', 'Cagayan Valley'),
            'location_label' => implode(', ', array_filter([$municipality, $province])),
        ];
    }

    /**
     * Editable public copy with optional live brand tokens for the public UI.
     *
     * @return array<string, mixed>
     */
    public function publicHomeContent(bool $resolveTokens = true): array
    {
        $content = self::mergePublicHomeContent(
            config('public_home', []),
            $this->public_home_content ?? []
        );

        if (! $resolveTokens) {
            return $content;
        }

        $tokens = [];
        foreach ($this->publicHomePersonalizations() as $personalization) {
            $tokens[$personalization['marker']] = $personalization['value'];
        }

        array_walk_recursive($content, static function (&$value) use ($tokens): void {
            if (is_string($value)) {
                $value = strtr($value, $tokens);
            }
        });

        return $content;
    }

    /**
     * @return array<string, array{marker: string, label: string, value: string, source: string}>
     */
    public function publicHomePersonalizations(): array
    {
        $branding = $this->brandingValues();

        return [
            'system_name' => [
                'marker' => '{system_name}',
                'label' => 'System name',
                'value' => $branding['system_name'],
                'source' => 'Branding & System Settings > System identity > System name',
            ],
            'organization' => [
                'marker' => '{organization}',
                'label' => 'Organization name',
                'value' => $branding['organization_name'],
                'source' => 'Branding & System Settings > System identity > Organization name',
            ],
            'municipality' => [
                'marker' => '{municipality}',
                'label' => 'Municipality',
                'value' => $branding['municipality_label'],
                'source' => 'Branding & System Settings > Location display labels > Municipality',
            ],
            'province' => [
                'marker' => '{province}',
                'label' => 'Province',
                'value' => $branding['province_label'],
                'source' => 'Branding & System Settings > Location display labels > Province',
            ],
            'region' => [
                'marker' => '{region}',
                'label' => 'Response area',
                'value' => $branding['response_area_label'],
                'source' => 'Branding & System Settings > Location display labels > Response area / region',
            ],
            'tagline' => [
                'marker' => '{tagline}',
                'label' => 'Tagline',
                'value' => (string) config('raniag.tagline', ''),
                'source' => 'System-wide tagline setting',
            ],
        ];
    }

    /**
     * Merge object-like sections while allowing editors to replace whole lists.
     *
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $saved
     * @return array<string, mixed>
     */
    private static function mergePublicHomeContent(array $defaults, array $saved): array
    {
        foreach ($saved as $key => $value) {
            if (
                is_array($value)
                && is_array($defaults[$key] ?? null)
                && ! array_is_list($value)
            ) {
                $defaults[$key] = self::mergePublicHomeContent($defaults[$key], $value);

                continue;
            }

            $defaults[$key] = $value;
        }

        return $defaults;
    }
}

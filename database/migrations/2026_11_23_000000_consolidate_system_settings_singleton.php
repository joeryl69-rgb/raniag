<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Older settings rows can exist because the singleton id was discarded
     * during mass assignment. Copy the newest saved sections to id 1 while
     * leaving the source rows intact for recovery.
     */
    public function up(): void
    {
        $rows = DB::table('system_settings')->orderBy('id')->get();

        if ($rows->isEmpty()) {
            return;
        }

        $canonical = $rows->firstWhere('id', 1);
        $defaults = [
            'theme_key' => 'ocean',
            'dark_mode' => false,
            'follow_system' => false,
            'font_key' => 'figtree',
            'font_size' => 'normal',
            'updated_by' => null,
            'municipality_boundary' => null,
            'municipality_boundary_updated_by' => null,
            'municipality_boundary_updated_at' => null,
            'system_name' => null,
            'organization_name' => null,
            'logo_path' => null,
            'primary_color' => config('system_branding.primary_color', '#086b78'),
            'accent_color' => config('system_branding.accent_color', '#29aa96'),
            'page_background_color' => config('system_branding.page_background_color', '#eaf0f2'),
            'header_text' => null,
            'footer_text' => null,
            'show_header_text' => true,
            'show_footer_text' => true,
            'contact_address' => null,
            'contact_phone' => null,
            'contact_email' => null,
            'contact_hotline' => null,
            'municipality_label' => null,
            'province_label' => null,
            'response_area_label' => null,
            'public_home_content' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $attributes = $canonical ? (array) $canonical : $defaults;

        $branding = $rows
            ->filter(fn (object $row): bool => $row->system_name !== null)
            ->sortBy(fn (object $row): string => sprintf(
                '%s-%020d',
                $row->updated_at ?? $row->created_at ?? '',
                $row->id,
            ))
            ->last();

        if ($branding) {
            foreach ([
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
            ] as $field) {
                $attributes[$field] = $branding->{$field};
            }
        }

        $boundary = $rows
            ->filter(fn (object $row): bool => $row->municipality_boundary_updated_at !== null
                || $row->municipality_boundary !== null)
            ->sortBy(fn (object $row): string => sprintf(
                '%s-%020d',
                $row->municipality_boundary_updated_at
                    ?? $row->updated_at
                    ?? $row->created_at
                    ?? '',
                $row->id,
            ))
            ->last();

        if ($boundary) {
            foreach ([
                'municipality_boundary',
                'municipality_boundary_updated_by',
                'municipality_boundary_updated_at',
            ] as $field) {
                $attributes[$field] = $boundary->{$field};
            }
        }

        $attributes['id'] = 1;

        if ($canonical) {
            DB::table('system_settings')->where('id', 1)->update($attributes);

            return;
        }

        DB::table('system_settings')->insert($attributes);
    }

    public function down(): void
    {
        // The source rows are retained; the merged singleton is not reversible.
    }
};

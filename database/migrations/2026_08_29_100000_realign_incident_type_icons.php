<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Earlier seed data stored icon values like "fire" / "water" instead of the
 * "bi-fire" / "bi-water" Bootstrap Icons classes the picker actually uses,
 * and some colors weren't in the picker's palette. This realigns any rows
 * seeded before that fix so every incident type renders with the icon and
 * color the admin actually sees (and can edit) in Admin > Incident Types.
 */
return new class extends Migration
{
    private const FIXES = [
        'fire' => ['icon' => 'bi-fire', 'color' => '#dc3545'],
        'flood' => ['icon' => 'bi-water', 'color' => '#0d6efd'],
        'crime' => ['icon' => 'bi-shield-fill-exclamation', 'color' => '#212529'],
        'medical' => ['icon' => 'bi-heart-pulse-fill', 'color' => '#198754'],
        'traffic' => ['icon' => 'bi-car-front-fill', 'color' => '#fd7e14'],
        'disaster' => ['icon' => 'bi-exclamation-triangle-fill', 'color' => '#6f42c1'],
        'infrastructure' => ['icon' => 'bi-building-fill-exclamation', 'color' => '#64748b'],
        'other' => ['icon' => 'bi-question-circle-fill', 'color' => '#adb5bd'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('incident_types')) {
            return;
        }

        foreach (self::FIXES as $slug => $fix) {
            DB::table('incident_types')->where('slug', $slug)->update($fix);
        }

        // Catch-all: any icon still missing the bi- prefix (custom/legacy rows)
        // gets a safe fallback instead of silently rendering no icon at all.
        DB::table('incident_types')
            ->whereNotNull('icon')
            ->where('icon', 'not like', 'bi-%')
            ->update(['icon' => 'bi-exclamation-triangle-fill']);

        DB::table('incident_types')
            ->where(function ($q) {
                $q->whereNull('color')->orWhere('color', 'not like', '#%');
            })
            ->update(['color' => '#64748b']);
    }

    public function down(): void
    {
        // Intentionally irreversible data fix.
    }
};

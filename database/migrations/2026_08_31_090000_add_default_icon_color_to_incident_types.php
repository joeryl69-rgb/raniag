<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Reset to Default" in Admin > Incident Types only worked for the 8
 * hardcoded seeded slugs (see IncidentTypeController::DEFAULT_PRESETS) —
 * any custom or newly-created type had no default to revert to, so the
 * button silently stayed hidden. These columns capture each type's own
 * icon/color the moment it is created, so every type (seeded, custom, or
 * created after this migration) always has a working reset target.
 */
return new class extends Migration
{
    private const SEED_DEFAULTS = [
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
        Schema::table('incident_types', function (Blueprint $table) {
            $table->string('default_icon', 64)->nullable()->after('color');
            $table->string('default_color', 16)->nullable()->after('default_icon');
        });

        if (! Schema::hasTable('incident_types')) {
            return;
        }

        // Seeded built-ins: their default is the original seed values,
        // regardless of whatever the admin may have already customized.
        foreach (self::SEED_DEFAULTS as $slug => $fix) {
            DB::table('incident_types')->where('slug', $slug)->update([
                'default_icon' => $fix['icon'],
                'default_color' => $fix['color'],
            ]);
        }

        // Every other existing row (custom, admin-created types): their
        // "default" becomes whatever icon/color they currently have, so
        // the reset button now works for them too instead of being hidden.
        DB::table('incident_types')
            ->whereNull('default_icon')
            ->update([
                'default_icon' => DB::raw('icon'),
                'default_color' => DB::raw('color'),
            ]);
    }

    public function down(): void
    {
        Schema::table('incident_types', function (Blueprint $table) {
            $table->dropColumn(['default_icon', 'default_color']);
        });
    }
};

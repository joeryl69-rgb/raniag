<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Safety-net backfill for App\Models\IncidentType rows created/migrated
 * outside the normal store()/seeder path (e.g. direct DB imports) that
 * still have a null default_icon/default_color. The "Reset to Default"
 * button in Admin > Incident Types stays hidden for any row lacking
 * both values (see resources/views/admin/incident_types/index.blade.php),
 * so this repeats the backfill done in
 * 2026_08_31_090000_add_default_icon_color_to_incident_types.php to
 * catch anything that slipped through. Idempotent — only touches rows
 * where default_icon/default_color is still null.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('incident_types')) {
            return;
        }

        DB::table('incident_types')
            ->whereNull('default_icon')
            ->orWhereNull('default_color')
            ->update([
                'default_icon' => DB::raw('COALESCE(default_icon, icon)'),
                'default_color' => DB::raw('COALESCE(default_color, color)'),
            ]);
    }

    public function down(): void
    {
        // No-op: this migration only repairs data, nothing to reverse.
    }
};

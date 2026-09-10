<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Incident priority used to be fixed at 'medium' for every public report
 * regardless of incident type (see StoreIncidentReportRequest, pre-fix).
 * This column lets each incident type carry its OWN default priority, set
 * by the admin in Admin > Incident Types — mirroring how default_icon/
 * default_color already work per type. A newly submitted report now takes
 * its priority from the selected incident type's default_priority instead
 * of a hardcoded value, and the admin decides that mapping per agency need.
 */
return new class extends Migration
{
    // Seed-time guess at a sensible starting priority per built-in type.
    // Admin can change any of these afterward in Admin > Incident Types —
    // this is only the initial value, not a fixed rule.
    private const SEED_DEFAULTS = [
        'fire' => 'critical',
        'flood' => 'high',
        'crime' => 'high',
        'medical' => 'critical',
        'traffic' => 'medium',
        'disaster' => 'critical',
        'infrastructure' => 'medium',
        'other' => 'low',
    ];

    public function up(): void
    {
        Schema::table('incident_types', function (Blueprint $table) {
            $table->string('default_priority', 16)->default('medium')->after('default_color');
        });

        if (! Schema::hasTable('incident_types')) {
            return;
        }

        foreach (self::SEED_DEFAULTS as $slug => $priority) {
            DB::table('incident_types')->where('slug', $slug)->update([
                'default_priority' => $priority,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('incident_types', function (Blueprint $table) {
            $table->dropColumn('default_priority');
        });
    }
};

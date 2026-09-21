<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('field_phase', 32)->nullable()->after('acknowledged_by');
        });

        Schema::table('incident_types', function (Blueprint $table) {
            $table->json('resolution_checklist')->nullable()->after('sort_order');
        });

        Schema::table('sms_logs', function (Blueprint $table) {
            $table->string('direction', 16)->default('outbound')->after('message');
            $table->string('thread_note', 500)->nullable()->after('direction');
        });

        // Sensible default checklists for seeded types (one line each).
        $defaults = [
            'fire' => ['Scene secured', 'Fire suppressed / extinguished', 'Injuries assessed', 'Cause noted if known'],
            'flood' => ['Area assessed', 'Residents warned/evacuated if needed', 'Drainage/obstruction checked', 'Photos taken'],
            'medical' => ['Patient assessed', 'First aid / transport arranged', 'Scene hazard cleared', 'Hospital notified if needed'],
            'crime' => ['Scene preserved', 'PNP coordinated', 'Witnesses noted', 'Evidence secured'],
            'traffic' => ['Traffic controlled', 'Injuries assessed', 'Vehicles cleared/secured', 'PNP/LTO notified if needed'],
            'disaster' => ['Immediate hazards identified', 'Evacuation guidance given', 'MDRRMO ops notified', 'Damage noted'],
            'infrastructure' => ['Hazard marked/secured', 'Utility/agency contacted', 'Public access restricted', 'Photos taken'],
            'other' => ['Scene assessed', 'Immediate hazard mitigated', 'Follow-up owner identified', 'Photos taken'],
        ];

        foreach ($defaults as $slug => $items) {
            \Illuminate\Support\Facades\DB::table('incident_types')
                ->where('slug', $slug)
                ->whereNull('resolution_checklist')
                ->update(['resolution_checklist' => json_encode($items)]);
        }
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('field_phase');
        });

        Schema::table('incident_types', function (Blueprint $table) {
            $table->dropColumn('resolution_checklist');
        });

        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropColumn(['direction', 'thread_note']);
        });
    }
};

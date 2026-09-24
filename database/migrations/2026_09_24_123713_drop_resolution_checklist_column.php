<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The per-incident-type "Resolution checklist" was never part of the
     * real MDRRMO field process and confused agencies during resolution —
     * removing it entirely rather than just hiding it in the UI.
     */
    public function up(): void
    {
        Schema::table('incident_types', function (Blueprint $table) {
            if (Schema::hasColumn('incident_types', 'resolution_checklist')) {
                $table->dropColumn('resolution_checklist');
            }
        });
    }

    public function down(): void
    {
        Schema::table('incident_types', function (Blueprint $table) {
            $table->json('resolution_checklist')->nullable()->after('sort_order');
        });
    }
};

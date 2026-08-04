<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            // List of content keys the requesting agency wants included in the
            // generated PDF (e.g. incident_details, evidence_photos, call_taker_form,
            // dispatch_form, narrative_report, endorsement_sheet). Null/empty means
            // "include everything available" (preserves old behavior for existing rows).
            $table->json('requested_sections')->nullable()->after('request_note');
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropColumn('requested_sections');
        });
    }
};

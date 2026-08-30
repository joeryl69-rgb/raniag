<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_documents', function (Blueprint $table) {
            $table->longText('extracted_text')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('incident_documents', function (Blueprint $table) {
            $table->dropColumn('extracted_text');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_records', function (Blueprint $table) {
            $table->json('snapshot')->nullable()->after('chapters');
        });
    }

    public function down(): void
    {
        Schema::table('report_records', function (Blueprint $table) {
            $table->dropColumn('snapshot');
        });
    }
};

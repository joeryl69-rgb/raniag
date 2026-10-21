<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_records', function (Blueprint $table) {
            $table->string('report_kind', 24)->default('custom')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('report_records', function (Blueprint $table) {
            $table->dropColumn('report_kind');
        });
    }
};

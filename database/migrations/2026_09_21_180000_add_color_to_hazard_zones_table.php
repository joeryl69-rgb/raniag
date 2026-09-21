<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hazard_zones', function (Blueprint $table) {
            $table->string('color', 16)->nullable()->after('geometry');
        });
    }

    public function down(): void
    {
        Schema::table('hazard_zones', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};

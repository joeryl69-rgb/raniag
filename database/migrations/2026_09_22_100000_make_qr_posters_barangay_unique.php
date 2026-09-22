<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_posters', function (Blueprint $table) {
            $table->dropIndex(['barangay']);
            $table->unique('barangay');
        });
    }

    public function down(): void
    {
        Schema::table('qr_posters', function (Blueprint $table) {
            $table->dropUnique(['barangay']);
            $table->index('barangay');
        });
    }
};

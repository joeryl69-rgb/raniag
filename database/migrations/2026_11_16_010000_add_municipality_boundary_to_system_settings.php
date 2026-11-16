<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->json('municipality_boundary')->nullable();
            $table->foreignId('municipality_boundary_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('municipality_boundary_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('municipality_boundary_updated_by');
            $table->dropColumn(['municipality_boundary', 'municipality_boundary_updated_at']);
        });
    }
};

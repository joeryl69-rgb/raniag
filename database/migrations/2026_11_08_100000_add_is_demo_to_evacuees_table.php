<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evacuees', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('full_name');
            $table->index(['evacuation_center_id', 'is_demo', 'checked_out_at']);
        });
    }

    public function down(): void
    {
        Schema::table('evacuees', function (Blueprint $table) {
            $table->dropIndex(['evacuation_center_id', 'is_demo', 'checked_out_at']);
            $table->dropColumn('is_demo');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->tinyInteger('priority')->default(0)->after('file_size')->comment('0=normal, 1=high priority');
            $table->boolean('is_gps_capture')->default(false)->after('priority')->comment('True if from GPS camera capture');
            $table->index(['incident_id', 'priority', 'is_gps_capture']);
        });
    }

    public function down(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->dropIndex(['incident_id', 'priority', 'is_gps_capture']);
            $table->dropColumn(['priority', 'is_gps_capture']);
        });
    }
};

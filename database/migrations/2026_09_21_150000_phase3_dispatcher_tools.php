<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->boolean('is_available')->default(true)->after('is_active');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_available')->default(true)->after('is_active');
            $table->decimal('last_lat', 10, 7)->nullable()->after('is_available');
            $table->decimal('last_lng', 10, 7)->nullable()->after('last_lat');
            $table->timestamp('last_location_at')->nullable()->after('last_lng');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->boolean('is_drill')->default(false)->after('is_anonymous');
            $table->string('after_action_pdf_path')->nullable()->after('meta');
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn('is_available');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_available', 'last_lat', 'last_lng', 'last_location_at']);
        });
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn(['is_drill', 'after_action_pdf_path']);
        });
    }
};

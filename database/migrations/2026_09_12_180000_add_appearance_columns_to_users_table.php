<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Appearance (theme/dark-mode/follow-system/font) used to live on a
     * single global system_settings row shared by literally every account —
     * an admin's choice changed dark mode for agency and personnel accounts
     * too. These columns make it a per-user preference instead, nullable so
     * "not set yet" cleanly falls back to the app defaults in code
     * (App\Support\ThemePresets::DEFAULT_*).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme_key', 32)->nullable()->after('avatar_path');
            $table->boolean('dark_mode')->nullable()->after('theme_key');
            $table->boolean('follow_system')->nullable()->after('dark_mode');
            $table->string('font_key', 32)->nullable()->after('follow_system');
            $table->string('font_size', 16)->nullable()->after('font_key');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['theme_key', 'dark_mode', 'follow_system', 'font_key', 'font_size']);
        });
    }
};

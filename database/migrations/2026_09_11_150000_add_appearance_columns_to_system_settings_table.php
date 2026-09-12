<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the columns needed to make "Follow system appearance" a real,
     * persisted, per-visit setting (previously it only flipped the
     * dark_mode checkbox once at save time — a one-off snapshot, not an
     * ongoing sync) plus the new font family / font size controls on the
     * System Settings screen.
     */
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->boolean('follow_system')->default(false)->after('dark_mode');
            $table->string('font_key', 32)->default('figtree')->after('follow_system');
            $table->string('font_size', 16)->default('normal')->after('font_key');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['follow_system', 'font_key', 'font_size']);
        });
    }
};

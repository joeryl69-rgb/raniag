<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->string('alert_level', 20)->default('normal')->after('font_size');
            $table->string('alert_note', 280)->nullable()->after('alert_level');
        });

        Schema::create('public_hotlines', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('number', 40);
            $table->string('detail', 160)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_hotlines');

        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['alert_level', 'alert_note']);
        });
    }
};

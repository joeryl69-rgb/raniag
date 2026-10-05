<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->boolean('sound_enabled')->default(true)->after('user_agent');
            $table->boolean('vibrate_enabled')->default(true)->after('sound_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['sound_enabled', 'vibrate_enabled']);
        });
    }
};

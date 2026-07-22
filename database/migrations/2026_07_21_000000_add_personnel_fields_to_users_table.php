<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role_title')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role_title')->nullable()->after('phone');
                $table->string('team_assignment')->nullable()->after('role_title');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'team_assignment')) {
                $table->dropColumn('team_assignment');
            }
            if (Schema::hasColumn('users', 'role_title')) {
                $table->dropColumn('role_title');
            }
        });
    }
};

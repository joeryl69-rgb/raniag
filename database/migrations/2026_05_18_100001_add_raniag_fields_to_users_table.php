<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('agency')->after('email');
            $table->foreignId('agency_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->string('phone', 32)->nullable()->after('agency_id');
            $table->boolean('is_active')->default(true)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agency_id');
            $table->dropColumn(['role', 'phone', 'is_active']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the hardcoded role-title arrays that used to live in
     * AgencyController/PersonnelController with a real table, so an
     * administrator can add/rename/retire personnel roles without a
     * code change. `users.role_title` (see
     * 2026_07_21_000000_add_personnel_fields_to_users_table) stays a
     * plain string column on purpose — it is not converted to a
     * foreign key — so renaming or deactivating a role here never
     * breaks existing personnel accounts that already carry the old
     * title.
     */
    public function up(): void
    {
        Schema::create('personnel_roles', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_roles');
    }
};

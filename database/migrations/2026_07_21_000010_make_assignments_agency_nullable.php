<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assignments')) {
            $driver = DB::getDriverName();
            // Only run MODIFY on MySQL. SQLite (used in tests) doesn't support ALTER ... MODIFY.
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `assignments` MODIFY `agency_id` BIGINT UNSIGNED NULL');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assignments')) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `assignments` MODIFY `agency_id` BIGINT UNSIGNED NOT NULL');
            }
        }
    }
};

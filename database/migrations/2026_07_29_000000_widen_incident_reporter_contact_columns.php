<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen reporter_email/reporter_phone from VARCHAR to TEXT.
     *
     * Laravel's `encrypted` Eloquent cast stores ciphertext (base64 AES-256-GCM,
     * ~3-4x the plaintext length plus IV/tag overhead), which will not fit in
     * the original varchar(255)/varchar(32) columns. Raw ALTER TABLE is used
     * instead of Schema::table()->change() so this doesn't require installing
     * doctrine/dbal just for one migration.
     */
    public function up(): void
    {
        if (! Schema::hasTable('incidents')) {
            return;
        }

        DB::statement('ALTER TABLE incidents MODIFY reporter_email TEXT NULL');
        DB::statement('ALTER TABLE incidents MODIFY reporter_phone TEXT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('incidents')) {
            return;
        }

        DB::statement('ALTER TABLE incidents MODIFY reporter_email VARCHAR(255) NULL');
        DB::statement('ALTER TABLE incidents MODIFY reporter_phone VARCHAR(32) NULL');
    }
};

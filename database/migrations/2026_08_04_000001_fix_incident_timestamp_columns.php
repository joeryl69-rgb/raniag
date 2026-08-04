<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL implicitly gives the FIRST timestamp() column in a table
        // "DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" unless one
        // is set explicitly. That silently rewrote reported_at to "now" on
        // every status update. dateTime columns don't have that behavior.
        // Raw SQL used here to avoid requiring doctrine/dbal just for a
        // column type change.
        DB::statement('ALTER TABLE incidents MODIFY reported_at DATETIME NOT NULL');
        DB::statement('ALTER TABLE incidents MODIFY resolved_at DATETIME NULL');
        DB::statement('ALTER TABLE incidents MODIFY closed_at DATETIME NULL');

        // Repair data already corrupted by the bug: the incident's true
        // original report time is still intact in status_updates (a
        // separate table the MySQL auto-update quirk never touched) as
        // the created_at of its first "submitted" entry.
        DB::statement(<<<'SQL'
            UPDATE incidents i
            JOIN (
                SELECT incident_id, MIN(created_at) AS first_submitted_at
                FROM status_updates
                WHERE to_status = 'submitted'
                GROUP BY incident_id
            ) su ON su.incident_id = i.id
            SET i.reported_at = su.first_submitted_at
            WHERE su.first_submitted_at < i.reported_at
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE incidents MODIFY reported_at TIMESTAMP NOT NULL');
        DB::statement('ALTER TABLE incidents MODIFY resolved_at TIMESTAMP NULL');
        DB::statement('ALTER TABLE incidents MODIFY closed_at TIMESTAMP NULL');
    }
};

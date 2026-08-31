<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the public feedback form into a full Support Center: a short
 * human-readable ticket number, where the ticket came from (public vs. a
 * signed-in agency/personnel account), and — for agency submissions — a
 * link back to the submitting agency/user so admins can see who filed it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_submissions', function (Blueprint $table) {
            $table->string('ticket_no', 20)->nullable()->unique()->after('id');
            $table->string('submitted_via', 20)->default('public')->after('category'); // public | agency
            $table->foreignId('agency_id')->nullable()->after('submitted_via')->constrained('agencies')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->after('agency_id')->constrained('users')->nullOnDelete();
        });

        // Backfill ticket numbers for any rows created before this column existed.
        foreach (DB::table('feedback_submissions')->whereNull('ticket_no')->orderBy('id')->cursor() as $row) {
            DB::table('feedback_submissions')->where('id', $row->id)->update([
                'ticket_no' => 'RG-'.\Illuminate\Support\Carbon::parse($row->created_at)->format('ymd').'-'.strtoupper(substr(md5($row->id.$row->created_at), 0, 5)),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('feedback_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agency_id');
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropColumn(['ticket_no', 'submitted_via']);
        });
    }
};

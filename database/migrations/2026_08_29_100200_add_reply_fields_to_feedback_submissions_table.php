<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_submissions', function (Blueprint $table) {
            $table->longText('admin_reply')->nullable()->after('admin_notes');
            $table->foreignId('replied_by')->nullable()->after('admin_reply')->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable()->after('replied_by');
        });
    }

    public function down(): void
    {
        Schema::table('feedback_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replied_by');
            $table->dropColumn(['admin_reply', 'replied_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requesting_agency_id')->nullable()->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('request_type')->default('single'); // single (required for now) / bulk (future)
            $table->string('status')->default('pending'); // pending/approved/sent/failed
            $table->text('admin_comment')->nullable();

            $table->string('generated_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('failed_reason')->nullable();

            $table->timestamps();

            $table->index(['incident_id', 'requesting_agency_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};

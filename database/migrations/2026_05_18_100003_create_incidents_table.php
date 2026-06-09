<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number', 32)->unique();
            $table->foreignId('incident_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 32)->default('submitted');
            $table->string('priority', 16)->default('medium');
            $table->string('title')->nullable();
            $table->text('description');
            $table->string('location_address')->nullable();
            $table->string('barangay')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('reporter_name')->nullable();
            $table->string('reporter_email')->nullable();
            $table->string('reporter_phone', 32)->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->timestamp('reported_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'reported_at']);
            $table->index('agency_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};

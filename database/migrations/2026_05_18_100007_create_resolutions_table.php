<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resolved_by')->constrained('users')->restrictOnDelete();
            $table->text('summary');
            $table->text('actions_taken')->nullable();
            $table->timestamp('resolved_at');
            $table->timestamps();

            $table->index('incident_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolutions');
    }
};

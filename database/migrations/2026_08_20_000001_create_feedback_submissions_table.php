<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('category', 32)->default('feedback'); // feedback | concern | suggestion | bug
            $table->string('subject', 150);
            $table->text('message');
            $table->string('submitter_name', 150)->nullable();
            $table->string('submitter_email', 150)->nullable();
            $table->string('status', 20)->default('new'); // new | reviewed | resolved
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_submissions');
    }
};

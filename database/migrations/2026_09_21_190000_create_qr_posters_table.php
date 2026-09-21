<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_posters', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('barangay');
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('barangay');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_posters');
    }
};

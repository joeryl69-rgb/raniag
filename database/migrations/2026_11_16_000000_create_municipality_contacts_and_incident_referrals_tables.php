<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipality_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('municipality', 120);
            $table->string('office_name', 160);
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('hotline', 40)->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['municipality', 'office_name']);
            $table->index('is_active');
        });

        Schema::create('incident_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('municipality_contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('municipality', 120);
            $table->string('office_name', 160);
            $table->string('hotline', 40)->nullable();
            $table->string('location_address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('public_note');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_referrals');
        Schema::dropIfExists('municipality_contacts');
    }
};

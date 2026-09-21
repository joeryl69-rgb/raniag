<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hazard_zone_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 16)->default('#b45309');
            $table->timestamps();
        });

        Schema::create('hazard_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hazard_zone_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('barangay')->nullable();
            $table->json('geometry'); // GeoJSON Polygon/MultiPolygon
            $table->text('advisory_note')->nullable();
            $table->string('advisory_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('evacuation_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('barangay')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('is_open')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('evacuees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evacuation_center_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('sex', 16)->nullable();
            $table->string('barangay')->nullable();
            $table->boolean('is_vulnerable')->default(false);
            $table->string('vulnerability_notes')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();
        });

        // Seed a few hazard zone types
        foreach ([
            ['Flood', 'flood', '#0d6efd'],
            ['Landslide', 'landslide', '#92400e'],
            ['Storm surge', 'storm-surge', '#0369a1'],
        ] as [$name, $slug, $color]) {
            \Illuminate\Support\Facades\DB::table('hazard_zone_types')->insert([
                'name' => $name,
                'slug' => $slug,
                'color' => $color,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('evacuees');
        Schema::dropIfExists('evacuation_centers');
        Schema::dropIfExists('hazard_zones');
        Schema::dropIfExists('hazard_zone_types');
    }
};

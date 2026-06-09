<?php

namespace Database\Factories;

use App\Models\IncidentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IncidentType>
 */
class IncidentTypeFactory extends Factory
{
    protected $model = IncidentType::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'icon' => 'circle-help',
            'color' => '#6c757d',
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}

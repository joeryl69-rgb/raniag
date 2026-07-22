<?php

namespace Database\Seeders;

use App\Models\IncidentType;
use Illuminate\Database\Seeder;

class IncidentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Fire', 'slug' => 'fire', 'description' => 'Fire-related emergencies', 'icon' => 'fire', 'color' => '#dc3545', 'sort_order' => 1],
            ['name' => 'Flood', 'slug' => 'flood', 'description' => 'Flooding and water-related incidents', 'icon' => 'water', 'color' => '#0d6efd', 'sort_order' => 2],
            ['name' => 'Crime', 'slug' => 'crime', 'description' => 'Criminal activity and public safety threats', 'icon' => 'shield', 'color' => '#212529', 'sort_order' => 3],
            ['name' => 'Medical Emergency', 'slug' => 'medical', 'description' => 'Medical and health emergencies', 'icon' => 'heart-pulse', 'color' => '#198754', 'sort_order' => 4],
            ['name' => 'Traffic Accident', 'slug' => 'traffic', 'description' => 'Road and traffic incidents', 'icon' => 'car', 'color' => '#fd7e14', 'sort_order' => 5],
            ['name' => 'Natural Disaster', 'slug' => 'disaster', 'description' => 'Earthquake, landslide, and other disasters', 'icon' => 'triangle-alert', 'color' => '#6f42c1', 'sort_order' => 6],
            ['name' => 'Infrastructure', 'slug' => 'infrastructure', 'description' => 'Damaged roads, bridges, and public facilities', 'icon' => 'building', 'color' => '#6c757d', 'sort_order' => 7],
            ['name' => 'Other', 'slug' => 'other', 'description' => 'Other incidents not listed above', 'icon' => 'circle-help', 'color' => '#adb5bd', 'sort_order' => 99],
        ];

        foreach ($types as $type) {
            IncidentType::query()->updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'icon' => $type['icon'],
                    'color' => $type['color'],
                    'sort_order' => $type['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}

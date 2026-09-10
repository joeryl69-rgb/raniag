<?php

namespace Database\Seeders;

use App\Models\IncidentType;
use Illuminate\Database\Seeder;

class IncidentTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Icon values MUST be valid Bootstrap Icons classes (bi-*) and colors must be
        // hex — these are exactly what the icon/color picker in Admin > Incident Types
        // renders and validates against (see App\Support\IconLibrary), so seeded rows
        // always line up with what the admin sees and can edit, with no mismatch.
        // default_priority is just a starting suggestion (low/medium/high/critical) —
        // the admin can change it per type any time in Admin > Incident Types, and a
        // new public report takes its priority from whatever is set here at the time
        // it's submitted (see IncidentService::submitAnonymousReport).
        $types = [
            ['name' => 'Fire', 'slug' => 'fire', 'description' => 'Fire-related emergencies', 'icon' => 'bi-fire', 'color' => '#dc3545', 'default_priority' => 'critical', 'sort_order' => 1],
            ['name' => 'Flood', 'slug' => 'flood', 'description' => 'Flooding and water-related incidents', 'icon' => 'bi-water', 'color' => '#0d6efd', 'default_priority' => 'high', 'sort_order' => 2],
            ['name' => 'Crime', 'slug' => 'crime', 'description' => 'Criminal activity and public safety threats', 'icon' => 'bi-shield-fill-exclamation', 'color' => '#212529', 'default_priority' => 'high', 'sort_order' => 3],
            ['name' => 'Medical Emergency', 'slug' => 'medical', 'description' => 'Medical and health emergencies', 'icon' => 'bi-heart-pulse-fill', 'color' => '#198754', 'default_priority' => 'critical', 'sort_order' => 4],
            ['name' => 'Traffic Accident', 'slug' => 'traffic', 'description' => 'Road and traffic incidents', 'icon' => 'bi-car-front-fill', 'color' => '#fd7e14', 'default_priority' => 'medium', 'sort_order' => 5],
            ['name' => 'Natural Disaster', 'slug' => 'disaster', 'description' => 'Earthquake, landslide, and other disasters', 'icon' => 'bi-exclamation-triangle-fill', 'color' => '#6f42c1', 'default_priority' => 'critical', 'sort_order' => 6],
            ['name' => 'Infrastructure', 'slug' => 'infrastructure', 'description' => 'Damaged roads, bridges, and public facilities', 'icon' => 'bi-building-fill-exclamation', 'color' => '#64748b', 'default_priority' => 'medium', 'sort_order' => 7],
            ['name' => 'Other', 'slug' => 'other', 'description' => 'Other incidents not listed above', 'icon' => 'bi-question-circle-fill', 'color' => '#adb5bd', 'default_priority' => 'low', 'sort_order' => 99],
        ];

        foreach ($types as $type) {
            $existing = IncidentType::query()->where('slug', $type['slug'])->first();

            IncidentType::query()->updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                    // On first create, icon/color/priority start as the seed values
                    // (the admin hasn't customized anything yet). On re-seed, keep
                    // whatever the admin has already set rather than clobbering edits.
                    'icon' => $existing->icon ?? $type['icon'],
                    'color' => $existing->color ?? $type['color'],
                    'default_priority' => $existing->default_priority?->value ?? $type['default_priority'],
                    // default_icon/default_color are always the ORIGINAL seed values —
                    // that's what "Reset to Default" reverts to, regardless of how many
                    // times the admin has customized icon/color since.
                    'default_icon' => $type['icon'],
                    'default_color' => $type['color'],
                    'sort_order' => $type['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}

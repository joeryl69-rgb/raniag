<?php

use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('reports picture uses the same filters as the downloads', function () {
    $type = IncidentType::factory()->create(['name' => 'Fire']);
    $admin = User::factory()->create([
        'role' => UserRole::Administrator,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    Incident::query()->create([
        'tracking_number' => 'RAN-RPT-0001',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Submitted,
        'priority' => 'high',
        'description' => 'Reports picture.',
        'barangay' => 'Bidduang',
        'latitude' => 18.51,
        'longitude' => 121.32,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    $this->actingAs($admin)
        ->postJson(route('admin.reports.picture'), [
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->toDateString(),
            'aor_scope' => 'aor_only',
            'view_mode' => 'weekly',
        ])
        ->assertOk()
        ->assertJsonPath('open', 1)
        ->assertJsonPath('places.0.label', 'Bidduang')
        ->assertJsonPath('priority.1.label', 'High')
        ->assertJsonPath('priority.1.count', 1);
});

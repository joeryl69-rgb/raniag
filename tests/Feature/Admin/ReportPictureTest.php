<?php

use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('decision report downloads a pdf for the selected dates and sections', function () {
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
        'description' => 'Decision report.',
        'barangay' => 'Bidduang',
        'latitude' => 18.51,
        'longitude' => 121.32,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.reports.decision'), [
            'date_from' => now()->subDays(14)->toDateString(),
            'date_to' => now()->toDateString(),
            'aor_scope' => 'aor_only',
            'view_mode' => 'weekly',
            'sections' => ['summary', 'comparison', 'projection'],
            'compare_from' => now()->subDays(28)->toDateString(),
            'compare_to' => now()->subDays(15)->toDateString(),
        ])
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

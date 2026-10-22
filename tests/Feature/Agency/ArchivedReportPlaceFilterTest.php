<?php

use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Assignment;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('agency resolved reports separate official barangays from places outside Pamplona', function () {
    $agency = Agency::query()->create([
        'name' => 'Pamplona Response',
        'code' => 'PRSP',
        'is_active' => true,
    ]);
    $agencyUser = User::factory()->create([
        'role' => UserRole::Agency,
        'agency_id' => $agency->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $administrator = User::factory()->administrator()->create();
    $type = IncidentType::factory()->create();

    $inside = Incident::query()->create([
        'tracking_number' => 'RAN-INSIDE',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Resolved,
        'priority' => 'medium',
        'description' => 'Place filter coverage.',
        'barangay' => 'Bidduang',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    $outside = Incident::query()->create([
        'tracking_number' => 'RAN-OUTSIDE',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Resolved,
        'priority' => 'medium',
        'description' => 'Place filter coverage.',
        'barangay' => 'Langagan',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    foreach ([$inside, $outside] as $incident) {
        Assignment::query()->create([
            'incident_id' => $incident->id,
            'agency_id' => $agency->id,
            'assigned_by' => $administrator->id,
            'is_active' => false,
            'assigned_at' => now(),
        ]);
    }

    $this->actingAs($agencyUser)
        ->get(route('agency.archived_reports.index'))
        ->assertOk()
        ->assertSeeInOrder([
            'All barangays',
            'All outside Pamplona',
            'All inside Pamplona',
        ])
        ->assertDontSee('<option value="Bidduang"', false)
        ->assertDontSee('<option value="Langagan"', false)
        ->assertSee('<option value="outside"', false)
        ->assertSee('<option value="inside"', false)
        ->assertSee('rg-place-outside-tag', false)
        ->assertSee('RAN-INSIDE')
        ->assertSee('RAN-OUTSIDE');

    $this->actingAs($agencyUser)
        ->get(route('agency.archived_reports.index', ['barangay' => 'inside']))
        ->assertOk()
        ->assertSee('RAN-INSIDE')
        ->assertDontSee('RAN-OUTSIDE');

    $this->actingAs($agencyUser)
        ->get(route('agency.archived_reports.index', ['barangay' => 'outside']))
        ->assertOk()
        ->assertSee('RAN-OUTSIDE')
        ->assertDontSee('RAN-INSIDE');

    $this->actingAs($agencyUser)
        ->get(route('agency.archived_reports.index', ['barangay' => 'Bidduang']))
        ->assertOk()
        ->assertSee('RAN-INSIDE')
        ->assertDontSee('RAN-OUTSIDE');
});

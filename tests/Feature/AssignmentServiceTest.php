<?php

use App\Enums\IncidentStatus;
use App\Models\Agency;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\IncidentService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

test('assigning an agency only advances status when transition is legal', function () {
    $type = IncidentType::factory()->create();
    $admin = User::factory()->create([
        'role' => 'administrator',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $agency = Agency::query()->create([
        'name' => 'Test BFP',
        'code' => 'TBFP',
        'is_active' => true,
    ]);

    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-ASN-0001',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Received,
        'priority' => 'medium',
        'description' => 'Assignment service transition coverage incident.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    $assignment = app(AssignmentService::class)->assignToAgency($incident, $agency, $admin);

    expect($assignment->agency_id)->toBe($agency->id)
        ->and($incident->fresh()->status)->toBe(IncidentStatus::Assigned);
});

test('second agency assignment does not re-transition from assigned', function () {
    $type = IncidentType::factory()->create();
    $admin = User::factory()->create([
        'role' => 'administrator',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $agencyA = Agency::query()->create(['name' => 'Agency A', 'code' => 'AGA', 'is_active' => true]);
    $agencyB = Agency::query()->create(['name' => 'Agency B', 'code' => 'AGB', 'is_active' => true]);

    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-ASN-0002',
        'tracking_pin' => Hash::make('222222'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Received,
        'priority' => 'medium',
        'description' => 'Multi-agency assignment coverage incident.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    $service = app(AssignmentService::class);
    $service->assignToAgency($incident, $agencyA, $admin);
    $service->assignToAgency($incident->fresh(), $agencyB, $admin);

    expect($incident->fresh()->status)->toBe(IncidentStatus::Assigned)
        ->and($incident->fresh()->currentAssignments()->count())->toBe(2);
});

test('agency and assigned personnel both retain case access when multiple agency dispatches exist', function () {
    $type = IncidentType::factory()->create();
    $admin = User::factory()->create([
        'role' => 'administrator',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $agencyA = Agency::query()->create(['name' => 'Agency A', 'code' => 'AG1', 'is_active' => true]);
    $agencyB = Agency::query()->create(['name' => 'Agency B', 'code' => 'AG2', 'is_active' => true]);
    $agencyUser = User::factory()->agency()->create([
        'agency_id' => $agencyB->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $personnel = User::factory()->create([
        'role' => 'personnel',
        'agency_id' => null,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-ASN-0004',
        'tracking_pin' => Hash::make('444444'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Received,
        'priority' => 'medium',
        'description' => 'Access control regression case for second agency + personnel dispatch.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    $service = app(AssignmentService::class);
    $service->assignToAgency($incident, $agencyA, $admin);
    $service->assignToAgency($incident->fresh(), $agencyB, $admin);
    $service->assignToPersonnel($incident->fresh(), $personnel, $admin);

    expect(Gate::forUser($agencyUser)->allows('view', $incident->fresh()))
        ->toBeTrue()
        ->and(Gate::forUser($personnel)->allows('view', $incident->fresh()))
        ->toBeTrue();
});

test('acknowledging assignment moves assigned incident to in_progress', function () {
    $type = IncidentType::factory()->create();
    $agency = Agency::query()->create(['name' => 'Ack Agency', 'code' => 'ACK', 'is_active' => true]);
    $agencyUser = User::factory()->create([
        'role' => 'agency',
        'agency_id' => $agency->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-ASN-0003',
        'tracking_pin' => Hash::make('333333'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Assigned,
        'priority' => 'medium',
        'description' => 'Acknowledgment coverage incident.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);

    $assignment = \App\Models\Assignment::query()->create([
        'incident_id' => $incident->id,
        'agency_id' => $agency->id,
        'assigned_by' => $agencyUser->id,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $updated = app(IncidentService::class)->logAssignmentAcknowledged($assignment, $incident, $agencyUser);

    expect($updated->status)->toBe(IncidentStatus::InProgress)
        ->and($assignment->fresh()->acknowledged_at)->not->toBeNull();
});

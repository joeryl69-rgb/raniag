<?php

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use App\Services\IncidentService;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->type = IncidentType::factory()->create();
    $this->admin = User::factory()->create([
        'role' => 'administrator',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
});

function makeIncident(IncidentStatus $status = IncidentStatus::Submitted): Incident
{
    return Incident::query()->create([
        'tracking_number' => 'RAN-TST-'.strtoupper(substr(uniqid(), -4)),
        'tracking_pin' => Hash::make('123456'),
        'incident_type_id' => test()->type->id,
        'status' => $status,
        'priority' => 'medium',
        'description' => 'Status transition test incident with enough detail.',
        'is_anonymous' => true,
        'reported_at' => now()->subHours(2),
    ]);
}

test('recordStatusChange rejects illegal transitions', function () {
    $incident = makeIncident(IncidentStatus::Submitted);
    $service = app(IncidentService::class);

    expect(fn () => $service->recordStatusChange(
        incident: $incident,
        toStatus: IncidentStatus::Assigned,
        user: $this->admin,
    ))->toThrow(InvalidArgumentException::class);
});

test('recordStatusChange allows legal transitions and sets resolved_at', function () {
    $incident = makeIncident(IncidentStatus::InProgress);
    $service = app(IncidentService::class);

    $updated = $service->recordStatusChange(
        incident: $incident,
        toStatus: IncidentStatus::Resolved,
        user: $this->admin,
        comment: 'Resolved in test',
    );

    expect($updated->status)->toBe(IncidentStatus::Resolved)
        ->and($updated->resolved_at)->not->toBeNull();
});

test('closing a resolved incident sets closed_at', function () {
    $incident = makeIncident(IncidentStatus::Resolved);
    $incident->forceFill(['resolved_at' => now()->subHour()])->save();
    $service = app(IncidentService::class);

    $updated = $service->recordStatusChange(
        incident: $incident,
        toStatus: IncidentStatus::Closed,
        user: $this->admin,
    );

    expect($updated->status)->toBe(IncidentStatus::Closed)
        ->and($updated->closed_at)->not->toBeNull();
});

test('identical status change is a no-op', function () {
    $incident = makeIncident(IncidentStatus::Received);
    $service = app(IncidentService::class);

    $updated = $service->recordStatusChange(
        incident: $incident,
        toStatus: IncidentStatus::Received,
        user: $this->admin,
    );

    expect($updated->status)->toBe(IncidentStatus::Received);
});

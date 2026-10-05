<?php

use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Assignment;
use App\Models\DocumentRequest;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function documentRequestFixture(): array
{
    $type = IncidentType::factory()->create();
    $agency = Agency::query()->create([
        'name' => 'BFP Pamplona',
        'code' => 'BFP'.uniqid(),
        'is_active' => true,
    ]);
    $user = User::factory()->create([
        'role' => UserRole::Agency,
        'agency_id' => $agency->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-'.strtoupper(substr(uniqid(), -6)),
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => IncidentStatus::Resolved,
        'priority' => 'medium',
        'description' => 'Resolved report for a printable request.',
        'latitude' => 18.47,
        'longitude' => 121.32,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    Assignment::query()->create([
        'incident_id' => $incident->id,
        'agency_id' => $agency->id,
        'assigned_by' => $user->id,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    return [$user, $incident, $agency];
}

test('an approved document request cannot be requested again as a single or bulk request', function () {
    [$user, $incident] = documentRequestFixture();

    DocumentRequest::query()->create([
        'incident_id' => $incident->id,
        'requesting_agency_id' => $user->agency_id,
        'requested_by' => $user->id,
        'request_type' => 'single',
        'status' => 'sent',
    ]);

    $this->actingAs($user)
        ->post(route('agency.incidents.print_requests.store', $incident), [
            'request_type' => 'single',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->actingAs($user)
        ->post(route('agency.document_requests.bulk_store'), [
            'incident_ids' => [$incident->id],
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(DocumentRequest::query()->where('incident_id', $incident->id)->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('agency.document_requests.index'))
        ->assertOk()
        ->assertDontSee($incident->tracking_number.'</option>', false)
        ->assertSee('No resolved reports awaiting a printable request right now.');
});

test('a pending document request can be cancelled and then requested again', function () {
    [$user, $incident] = documentRequestFixture();

    $pending = DocumentRequest::query()->create([
        'incident_id' => $incident->id,
        'requesting_agency_id' => $user->agency_id,
        'requested_by' => $user->id,
        'request_type' => 'single',
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->patch(route('agency.document_requests.cancel', $pending))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($pending->fresh()->status)->toBe('cancelled');

    $this->actingAs($user)
        ->post(route('agency.incidents.print_requests.store', $incident), [
            'request_type' => 'single',
            'request_note' => 'Need a fresh copy.',
        ])
        ->assertRedirect();

    expect(
        DocumentRequest::query()
            ->where('incident_id', $incident->id)
            ->where('status', 'pending')
            ->exists()
    )->toBeTrue();
});

test('an approved document request cannot be cancelled', function () {
    [$user, $incident] = documentRequestFixture();

    $sent = DocumentRequest::query()->create([
        'incident_id' => $incident->id,
        'requesting_agency_id' => $user->agency_id,
        'requested_by' => $user->id,
        'request_type' => 'bulk',
        'status' => 'sent',
    ]);

    $this->actingAs($user)
        ->patch(route('agency.document_requests.cancel', $sent))
        ->assertStatus(422);

    expect($sent->fresh()->status)->toBe('sent');
});

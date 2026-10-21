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

test('resolved incident document actions use the compact responsive action layout', function () {
    [$user, $incident] = documentRequestFixture();

    DocumentRequest::query()->create([
        'incident_id' => $incident->id,
        'requesting_agency_id' => $user->agency_id,
        'requested_by' => $user->id,
        'request_type' => 'single',
        'status' => 'sent',
        'generated_path' => 'incident-documents/approved-copy.pdf',
    ]);

    $this->actingAs($user)
        ->get(route('agency.incidents.show', $incident))
        ->assertOk()
        ->assertSee('class="rg-print-request-actions"', false)
        ->assertSee('class="rg-print-request-form"', false)
        ->assertSee('rg-print-request-button', false)
        ->assertSee('View Printable PDF')
        ->assertSee('Check Gmail for Approved Report')
        ->assertSee('Why do you need another copy?')
        ->assertSee('Request another copy');
});

test('agency case file shows the latest approved follow-up document when requests share a timestamp', function () {
    [$user, $incident] = documentRequestFixture();

    $previous = DocumentRequest::query()->create([
        'incident_id' => $incident->id,
        'requesting_agency_id' => $user->agency_id,
        'requested_by' => $user->id,
        'request_type' => 'single',
        'status' => 'sent',
        'generated_path' => 'document_requests/previous-copy.pdf',
    ]);
    $followUp = DocumentRequest::query()->create([
        'incident_id' => $incident->id,
        'requesting_agency_id' => $user->agency_id,
        'requested_by' => $user->id,
        'request_type' => 'single',
        'request_note' => 'A second copy is required for the records office.',
        'status' => 'sent',
        'generated_path' => 'document_requests/follow-up-copy.pdf',
    ]);

    $sameTimestamp = now()->startOfSecond();
    $previous->forceFill(['created_at' => $sameTimestamp])->saveQuietly();
    $followUp->forceFill(['created_at' => $sameTimestamp])->saveQuietly();

    $this->actingAs($user)
        ->get(route('agency.incidents.show', $incident))
        ->assertOk()
        ->assertSee('document_requests/follow-up-copy.pdf', false)
        ->assertDontSee('document_requests/previous-copy.pdf', false)
        ->assertSee('Check Gmail for Approved Report');
});

test('an approved copy can be requested again when the agency explains why', function () {
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
        ->get(route('agency.document_requests.index'))
        ->assertOk()
        ->assertSee($incident->tracking_number.' — copy already issued', false);

    $this->actingAs($user)
        ->post(route('agency.document_requests.bulk_store'), [
            'incident_ids' => [$incident->id],
            'request_note' => 'Need the endorsement sheet for the municipal file.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(DocumentRequest::query()->where('incident_id', $incident->id)->count())->toBe(2);
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

test('document request status type date search filters remain scoped to the signed-in agency', function () {
    [$user, $incident, $agency] = documentRequestFixture();
    $recent = now()->subDays(2);
    $pending = DocumentRequest::query()->create([
        'incident_id' => $incident->id,
        'requesting_agency_id' => $agency->id,
        'requested_by' => $user->id,
        'request_type' => 'single',
        'status' => 'pending',
    ]);
    $pending->forceFill(['created_at' => $recent, 'updated_at' => $recent])->saveQuietly();

    DocumentRequest::query()->create([
        'incident_id' => $incident->id,
        'requesting_agency_id' => $agency->id,
        'requested_by' => $user->id,
        'request_type' => 'bulk',
        'status' => 'sent',
    ]);

    $otherAgency = Agency::query()->create([
        'name' => 'Other response office',
        'code' => 'OTHER'.uniqid(),
        'is_active' => true,
    ]);
    $otherUser = User::factory()->create([
        'role' => UserRole::Agency,
        'agency_id' => $otherAgency->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    DocumentRequest::query()->create([
        'incident_id' => $incident->id,
        'requesting_agency_id' => $otherAgency->id,
        'requested_by' => $otherUser->id,
        'request_type' => 'single',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)
        ->get(route('agency.document_requests.index', [
            'q' => $incident->tracking_number,
            'status' => 'pending',
            'request_type' => 'single',
            'date_from' => now()->subDays(3)->toDateString(),
            'date_to' => now()->subDay()->toDateString(),
        ]))
        ->assertOk()
        ->assertSee('name="request_type"', false)
        ->assertSee('name="date_from"', false)
        ->assertSee('name="date_to"', false);

    expect($response->viewData('documentRequests')->getCollection()->modelKeys())
        ->toBe([$pending->id]);
});

<?php

use App\Models\Agency;
use App\Models\DocumentRequest;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('a document request notification opens that request instead of the incident', function () {
    $admin = User::factory()->administrator()->create();
    $agency = Agency::query()->create([
        'name' => 'BFP Pamplona',
        'code' => 'BFP'.uniqid(),
        'is_active' => true,
    ]);
    $requester = User::factory()->create([
        'role' => \App\Enums\UserRole::Agency,
        'agency_id' => $agency->id,
        'is_active' => true,
    ]);
    $type = IncidentType::factory()->create();
    $incident = Incident::query()->create([
        'tracking_number' => 'RAN-DOC-NOTE',
        'tracking_pin' => Hash::make('111111'),
        'incident_type_id' => $type->id,
        'status' => 'resolved',
        'priority' => 'medium',
        'description' => 'Needs a printable copy.',
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
    $documentRequest = DocumentRequest::query()->create([
        'incident_id' => $incident->id,
        'requesting_agency_id' => $agency->id,
        'requested_by' => $requester->id,
        'request_type' => 'single',
        'status' => 'pending',
    ]);

    $adminNotice = Notification::query()->create([
        'user_id' => $admin->id,
        'incident_id' => $incident->id,
        'type' => 'document_request.new',
        'title' => 'New document request',
        'message' => 'A document was requested.',
        'data' => ['document_request_id' => $documentRequest->id],
        'channel' => 'database',
    ]);
    $adminNotice->setRelation('user', $admin);

    expect($adminNotice->url())->toBe(route('admin.document_requests.index', [
        'request' => $documentRequest->id,
    ], false));

    $documentRequest->update([
        'status' => 'sent',
        'generated_path' => 'reports/ran-doc-note.pdf',
    ]);
    $agencyNotice = Notification::query()->create([
        'user_id' => $requester->id,
        'incident_id' => $incident->id,
        'type' => 'document_request.ready',
        'title' => 'Document request approved',
        'message' => 'The document is ready.',
        'data' => ['document_request_id' => $documentRequest->id],
        'channel' => 'database',
    ]);
    $agencyNotice->setRelation('user', $requester);

    expect($agencyNotice->url())->toBe(route('agency.document_requests.index', [
        'request' => $documentRequest->id,
    ], false));

    $this->actingAs($requester)
        ->get($agencyNotice->url())
        ->assertOk()
        ->assertSee('doc-request-'.$documentRequest->id, false)
        ->assertSee('table-primary', false)
        ->assertSee('RAN-DOC-NOTE', false)
        ->assertSee('View Printable PDF', false);

    $caseNotice = Notification::query()->create([
        'user_id' => $admin->id,
        'incident_id' => $incident->id,
        'type' => 'incident.assigned',
        'title' => 'Assigned',
        'message' => 'A case was assigned.',
        'channel' => 'database',
    ]);
    $caseNotice->setRelation('user', $admin);

    expect($caseNotice->url())->toBe(route('admin.incidents.show', $incident->id, false));
});

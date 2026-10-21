<?php

use App\Enums\IncidentDocumentType;
use App\Models\Incident;
use App\Models\IncidentDocument;
use App\Models\IncidentType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('case document status completion date and search filters narrow the register together', function () {
    $administrator = User::factory()->administrator()->create();
    $type = IncidentType::factory()->create();

    $makeIncident = function (string $tracking, string $status, int $daysAgo) use ($type): Incident {
        $createdAt = now()->subDays($daysAgo);

        $incident = Incident::query()->create([
            'tracking_number' => $tracking,
            'tracking_pin' => Hash::make('123456'),
            'incident_type_id' => $type->id,
            'status' => $status,
            'priority' => 'medium',
            'description' => 'Case document filtering test',
            'is_anonymous' => true,
            'reported_at' => $createdAt,
        ]);

        $incident->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $incident;
    };

    $complete = $makeIncident('RAN-DOC-COMPLETE', 'resolved', 4);
    $incomplete = $makeIncident('RAN-DOC-INCOMPLETE', 'closed', 2);
    $makeIncident('RAN-DOC-ACTIVE', 'assigned', 3);

    foreach (IncidentDocumentType::cases() as $documentType) {
        IncidentDocument::query()->create([
            'incident_id' => $complete->id,
            'document_type' => $documentType->value,
            'file_path' => 'test/'.$documentType->value.'.pdf',
            'original_filename' => $documentType->value.'.pdf',
        ]);
    }

    expect($complete->fresh()->incidentDocuments()->count())->toBe(count(IncidentDocumentType::cases()))
        ->and($complete->fresh()->created_at->toDateString())->toBe(now()->subDays(4)->toDateString());

    $resolvedResponse = $this->actingAs($administrator)
        ->get(route('admin.incident_documents.index', [
            'q' => 'RAN-DOC',
            'status' => 'resolved',
            'completion' => 'complete',
            'date_from' => now()->subDays(5)->toDateString(),
            'date_to' => now()->subDays(3)->toDateString(),
        ]))
        ->assertOk();

    expect($resolvedResponse->viewData('incidents')->pluck('tracking_number')->all())
        ->toBe(['RAN-DOC-COMPLETE']);

    $closedResponse = $this->get(route('admin.incident_documents.index', [
        'status' => 'closed',
        'completion' => 'missing',
        'date_from' => now()->subDays(3)->toDateString(),
        'date_to' => now()->subDays(1)->toDateString(),
    ]))
        ->assertOk();

    expect($closedResponse->viewData('incidents')->pluck('tracking_number')->all())
        ->toBe(['RAN-DOC-INCOMPLETE']);
});

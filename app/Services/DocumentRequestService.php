<?php

namespace App\Services;

use App\Models\DocumentRequest;
use App\Models\Incident;
use App\Models\User;

class DocumentRequestService
{
    public function createPendingRequest(
        Incident $incident,
        ?int $requestingAgencyId,
        User $requestedBy,
        string $requestType,
        ?string $requestNote = null,
        ?array $requestedSections = null,
    ): DocumentRequest {
        $dr = DocumentRequest::create([
            'incident_id' => $incident->id,
            'requesting_agency_id' => $requestingAgencyId,
            'requested_by' => $requestedBy->id,
            'request_type' => $requestType,
            'request_note' => $requestNote,
            'requested_sections' => $requestedSections,
            'status' => 'pending',
        ]);

        return $dr;
    }
}

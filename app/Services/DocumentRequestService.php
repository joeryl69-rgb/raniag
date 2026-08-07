<?php

namespace App\Services;

use App\Enums\IncidentDocumentType;
use App\Models\DocumentRequest;
use App\Models\Incident;
use App\Models\User;

class DocumentRequestService
{
    /**
     * Blocks the request when the agency explicitly asked for a form document
     * that isn't on file yet. Leaving the picker empty ("everything") is left
     * alone — that's a best-effort request and stays covered by the existing
     * incomplete-report warning instead of a hard block.
     */
    public function assertSectionsAvailable(Incident $incident, ?array $requestedSections): ?string
    {
        if (empty($requestedSections)) {
            return null;
        }

        $availability = $incident->documentAvailability();

        $unavailable = collect($requestedSections)
            ->filter(fn (string $section) => array_key_exists($section, $availability) && ! $availability[$section])
            ->map(fn (string $section) => IncidentDocumentType::from($section)->label());

        if ($unavailable->isEmpty()) {
            return null;
        }

        return "{$incident->tracking_number} is not yet available: {$unavailable->join(', ')}.";
    }

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

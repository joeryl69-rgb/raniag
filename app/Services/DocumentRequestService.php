<?php

namespace App\Services;

use App\Enums\IncidentDocumentType;
use App\Models\DocumentRequest;
use App\Models\Incident;
use App\Models\User;

class DocumentRequestService
{
    /**
     * Statuses that already reserve an incident for this agency.
     * Rejected and cancelled requests do not, so the agency can ask again.
     */
    public const BLOCKING_STATUSES = ['pending', 'approved', 'sent', 'failed'];

    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * An approved (or still pending) request locks that incident for this
     * agency, whether the next attempt is a single request or part of a bulk one.
     */
    public function requestBlockReason(Incident $incident, int $agencyId): ?string
    {
        $existing = DocumentRequest::query()
            ->where('incident_id', $incident->id)
            ->where('requesting_agency_id', $agencyId)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->first();

        if (! $existing) {
            return null;
        }

        $tracking = $incident->tracking_number;

        return $existing->status === 'pending'
            ? "{$tracking} already has a pending document request. Cancel it first if you need to submit a new one."
            : "{$tracking} already has an approved document request and cannot be requested again.";
    }

    public function cancelPending(DocumentRequest $documentRequest): void
    {
        abort_unless(
            $documentRequest->status === 'pending',
            422,
            'Only a pending document request can be cancelled.'
        );

        $documentRequest->update(['status' => 'cancelled']);

        $this->notifications->notifyAdminsDocumentRequestCancelled($documentRequest->fresh());
    }

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

        $this->notifications->notifyAdminsNewDocumentRequest($dr);

        return $dr;
    }
}

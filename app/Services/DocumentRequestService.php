<?php

namespace App\Services;

use App\Enums\IncidentDocumentType;
use App\Models\DocumentRequest;
use App\Models\Incident;
use App\Models\User;

class DocumentRequestService
{
    /**
     * Only a request still waiting on the administrator holds the incident.
     * An approved copy does not. The agency can ask for another copy later.
     */
    public const BLOCKING_STATUSES = ['pending'];

    /** A copy was already produced. A later request must say why. */
    public const ISSUED_STATUSES = ['approved', 'sent', 'failed'];

    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * A pending request is the only hard stop, for both single and bulk.
     * Cancel it, or wait for the decision, before sending another.
     */
    public function requestBlockReason(Incident $incident, int $agencyId): ?string
    {
        $pending = DocumentRequest::query()
            ->where('incident_id', $incident->id)
            ->where('requesting_agency_id', $agencyId)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->exists();

        if (! $pending) {
            return null;
        }

        return "{$incident->tracking_number} already has a pending document request. Cancel it first if you need to submit a new one.";
    }

    /**
     * After a copy has been issued, another request is allowed, but the
     * agency has to say what the new copy is for.
     */
    public function followUpNoteReason(Incident $incident, int $agencyId, ?string $note): ?string
    {
        $issued = DocumentRequest::query()
            ->where('incident_id', $incident->id)
            ->where('requesting_agency_id', $agencyId)
            ->whereIn('status', self::ISSUED_STATUSES)
            ->exists();

        if (! $issued || trim((string) $note) !== '') {
            return null;
        }

        return "{$incident->tracking_number} already has an approved copy. Add a note explaining why another copy is needed.";
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

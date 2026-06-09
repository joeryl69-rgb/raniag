<?php

namespace App\Services;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\User;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class IncidentService
{
    public function __construct(
        private readonly IncidentRepositoryInterface $incidents,
        private readonly TrackingNumberService $trackingNumbers,
        private readonly ActivityLogService $activityLogs,
        private readonly EvidenceService $evidenceService,
    ) {}

    /**
     * @param  list<UploadedFile>  $evidenceFiles
     */
    public function submitAnonymousReport(array $data, array $evidenceFiles = []): Incident
    {
        return DB::transaction(function () use ($data, $evidenceFiles) {
            $payload = Arr::except($data, ['evidence']);
            $meta = $payload['meta'] ?? [];

            if (isset($meta['gps_captures']) && is_string($meta['gps_captures'])) {
                $decoded = json_decode($meta['gps_captures'], true);
                $meta['gps_captures'] = is_array($decoded) ? $decoded : [];
            }

            $incident = $this->incidents->create([
                ...Arr::except($payload, ['meta']),
                'meta' => $meta ?: null,
                'tracking_number' => $this->trackingNumbers->generate(),
                'status' => IncidentStatus::Submitted,
                'priority' => $payload['priority'] ?? IncidentPriority::Medium->value,
                'reported_at' => $payload['reported_at'] ?? now(),
                'is_anonymous' => (bool) ($payload['is_anonymous'] ?? true),
            ]);

            $incident->statusUpdates()->create([
                'user_id' => null,
                'from_status' => null,
                'to_status' => IncidentStatus::Submitted,
                'comment' => 'Incident report received from the public portal.',
                'is_public' => true,
            ]);

            if ($evidenceFiles !== []) {
                $this->evidenceService->attachToIncident($incident, $evidenceFiles);
            }

            $this->activityLogs->log(
                description: 'Public incident report submitted.',
                subject: $incident,
                event: 'incident.submitted',
                logName: 'incident',
                properties: [
                    'tracking_number' => $incident->tracking_number,
                    'is_anonymous' => $incident->is_anonymous,
                ],
            );

            return $incident->load(['incidentType', 'evidence']);
        });
    }

    public function findByTrackingNumber(string $trackingNumber): ?Incident
    {
        return $this->incidents->findByTrackingNumber($trackingNumber);
    }

    public function recordStatusChange(
        Incident $incident,
        IncidentStatus $toStatus,
        ?User $user = null,
        ?string $comment = null,
        bool $isPublic = true,
    ): Incident {
        return DB::transaction(function () use ($incident, $toStatus, $user, $comment, $isPublic) {
            $fromStatus = $incident->status;

            $incident->statusUpdates()->create([
                'user_id' => $user?->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'comment' => $comment,
                'is_public' => $isPublic,
            ]);

            $incident = $this->incidents->update($incident, [
                'status' => $toStatus,
            ]);

            $this->activityLogs->log(
                description: sprintf('Incident status changed from %s to %s.', $fromStatus->value, $toStatus->value),
                user: $user,
                subject: $incident,
                event: 'incident.status_changed',
                logName: 'incident',
                properties: [
                    'from' => $fromStatus->value,
                    'to' => $toStatus->value,
                ],
            );

            return $incident;
        });
    }

    public function canTransitionTo(Incident $incident, IncidentStatus $targetStatus): bool
    {
        return in_array($targetStatus, $incident->status->availableTransitions());
    }

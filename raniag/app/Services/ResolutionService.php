<?php

namespace App\Services;

use App\Enums\IncidentStatus;
use App\Models\Assignment;
use App\Models\Incident;
use App\Models\Resolution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ResolutionService
{
    public function __construct(
        private readonly IncidentService $incidents,
        private readonly ActivityLogService $activityLogs,
        private readonly NotificationService $notifications,
    ) {}

    public function submitResolution(
        Incident $incident,
        User $resolvedBy,
        array $data
    ): Resolution {
        return DB::transaction(function () use ($incident, $resolvedBy, $data) {
            $resolution = Resolution::create([
                'incident_id' => $incident->id,
                'resolved_by' => $resolvedBy->id,
                'summary' => $data['summary'],
                'actions_taken' => $data['actions_taken'],
                'resolved_at' => now(),
            ]);

            // Completion rule: incident becomes globally resolved ONLY when ALL
            // active assignments for this incident are completed.
            $stillHasActiveAssignments = Assignment::query()
                ->where('incident_id', $incident->id)
                ->where('is_active', true)
                ->exists();

            $toStatus = $stillHasActiveAssignments
                ? ($incident->status->value === IncidentStatus::Submitted->value
                    ? IncidentStatus::Assigned
                    : IncidentStatus::InProgress)
                : IncidentStatus::Resolved;

            $commentPrefix = $stillHasActiveAssignments
                ? 'Resolution submitted; awaiting other agencies to complete.'
                : 'Resolution submitted: ';

            $this->incidents->recordStatusChange(
                incident: $incident,
                toStatus: $toStatus,
                user: $resolvedBy,
                comment: $commentPrefix.($stillHasActiveAssignments ? '' : $data['summary']),
                isPublic: true,
            );

            // IMPORTANT: Do not globally mark the incident resolved if this agency is submitting
            // its own resolution but other agencies still have active assignments.
            // The $toStatus computed above enforces this at the status/state-machine level.

            $this->activityLogs->log(

                description: 'Resolution submitted by '.$resolvedBy->agency?->name ?? 'Agency',
                user: $resolvedBy,
                subject: $incident,
                event: 'resolution.submitted',
                logName: 'resolution',
                properties: [
                    'resolution_id' => $resolution->id,
                    'summary' => substr($data['summary'], 0, 100),
                ],
            );

            $this->notifications->notifyAdminResolutionSubmitted($resolution);
            $this->notifications->notifyPublicStatusUpdate(
                $incident,
                'Your report has been resolved.'
            );

            return $resolution->fresh();
        });
    }

    public function closeResolution(Incident $incident, User $closedBy): Incident
    {
        return DB::transaction(function () use ($incident, $closedBy) {
            $this->incidents->recordStatusChange(
                incident: $incident,
                toStatus: IncidentStatus::Closed,
                user: $closedBy,
                comment: 'Case closed',
                isPublic: true,
            );

            $this->activityLogs->log(
                description: 'Incident case closed',
                user: $closedBy,
                subject: $incident,
                event: 'incident.closed',
                logName: 'incident',
                properties: [
                    'closed_at' => now()->toIso8601String(),
                ],
            );

            $this->notifications->notifyPublicStatusUpdate(
                $incident,
                'Your report case has been closed.'
            );

            return $incident->fresh();
        });
    }
}

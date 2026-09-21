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
        private readonly PrintableReportService $printableReports,
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

            if ($stillHasActiveAssignments) {
                // Other agencies still working — advance Assigned → InProgress if needed;
                // never force illegal transitions (e.g. Submitted → Assigned).
                if ($this->incidents->canTransitionTo($incident, IncidentStatus::InProgress)) {
                    $this->incidents->recordStatusChange(
                        incident: $incident,
                        toStatus: IncidentStatus::InProgress,
                        user: $resolvedBy,
                        comment: 'Resolution submitted; awaiting other agencies to complete.',
                        isPublic: true,
                    );
                }
            } else {
                // Assigned cannot jump straight to Resolved in the state machine.
                if ($this->incidents->canTransitionTo($incident, IncidentStatus::InProgress)) {
                    $incident = $this->incidents->recordStatusChange(
                        incident: $incident,
                        toStatus: IncidentStatus::InProgress,
                        user: $resolvedBy,
                        comment: 'Resolution submitted; finalizing case.',
                        isPublic: false,
                    );
                }

                if ($this->incidents->canTransitionTo($incident, IncidentStatus::Resolved)) {
                    $this->incidents->recordStatusChange(
                        incident: $incident,
                        toStatus: IncidentStatus::Resolved,
                        user: $resolvedBy,
                        comment: 'Resolution submitted: '.$data['summary'],
                        isPublic: true,
                    );
                }
            }

            $this->activityLogs->log(
                description: 'Resolution submitted by '.($resolvedBy->agency?->name ?? 'Agency'),
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

            $fresh = $resolution->fresh();
            $incident = $incident->fresh();
            if ($incident && $incident->status === IncidentStatus::Resolved) {
                try {
                    $this->printableReports->generateAfterActionPdf($incident);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('After-action PDF failed: '.$e->getMessage());
                }
            }

            return $fresh;
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

            return $incident->fresh();
        });
    }
}

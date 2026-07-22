<?php

namespace App\Services;

use App\Enums\IncidentStatus;
use App\Models\Agency;
use App\Models\Assignment;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignmentService
{
    public function __construct(
        private readonly IncidentService $incidents,
        private readonly ActivityLogService $activityLogs,
        private readonly NotificationService $notifications,
    ) {}

    public function assignToAgency(
        Incident $incident,
        Agency $agency,
        User $assignedBy,
        array $data = []
    ): Assignment {
        return DB::transaction(function () use ($incident, $agency, $assignedBy, $data) {
            // Idempotency + operational integrity:
            // - Prevent duplicate active assignments for the same (incident, agency)
            // - If the incident is already resolved/closed, block assignment creation
            //   (because it would break dashboard consistency and "already completed" behavior)
            $incidentStatus = $incident->status;
            if (in_array($incidentStatus->value, [
                IncidentStatus::Resolved->value,
                IncidentStatus::Closed->value,
            ], true)) {
                abort(422, 'Cannot assign an agency to an incident that is already resolved/closed.');
            }

            $existingActive = Assignment::query()
                ->where('incident_id', $incident->id)
                ->where('agency_id', $agency->id)
                ->where('is_active', true)
                ->latest('assigned_at')
                ->first();

            if ($existingActive) {
                return $existingActive->fresh();
            }

            $assignment = Assignment::create([
                'incident_id' => $incident->id,
                'agency_id' => $agency->id,
                'assigned_by' => $assignedBy->id,
                'assigned_to' => null,
                'is_active' => true,
                'assigned_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->incidents->recordStatusChange(
                incident: $incident,
                toStatus: IncidentStatus::Assigned,
                user: $assignedBy,
                comment: "Assigned to {$agency->name}",
                isPublic: true,
            );

            $this->activityLogs->log(
                description: "Incident assigned to {$agency->name}",
                user: $assignedBy,
                subject: $incident,
                event: 'assignment.created',
                logName: 'assignment',
                properties: [
                    'agency_id' => $agency->id,
                    'agency_name' => $agency->name,
                ],
            );

            $this->notifications->notifyAgencyAssigned($assignment);

            return $assignment->fresh();
        });
    }

    public function assignToPersonnel(
        Incident $incident,
        User $personnel,
        User $assignedBy,
        array $data = []
    ): Assignment {
        return DB::transaction(function () use ($incident, $personnel, $assignedBy, $data) {
            $incidentStatus = $incident->status;
            if (in_array($incidentStatus->value, [
                IncidentStatus::Resolved->value,
                IncidentStatus::Closed->value,
            ], true)) {
                abort(422, 'Cannot assign personnel to an incident that is already resolved/closed.');
            }

            $existingActive = Assignment::query()
                ->where('incident_id', $incident->id)
                ->whereNull('agency_id')
                ->where('assigned_to', $personnel->id)
                ->where('is_active', true)
                ->latest('assigned_at')
                ->first();

            if ($existingActive) {
                return $existingActive->fresh();
            }

            $assignment = Assignment::create([
                'incident_id' => $incident->id,
                'agency_id' => null,
                'assigned_by' => $assignedBy->id,
                'assigned_to' => $personnel->id,
                'is_active' => true,
                'assigned_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->incidents->recordStatusChange(
                incident: $incident,
                toStatus: IncidentStatus::Assigned,
                user: $assignedBy,
                comment: "Assigned to {$personnel->display_title}",
                isPublic: true,
            );

            $this->activityLogs->log(
                description: "Incident assigned to {$personnel->display_title}",
                user: $assignedBy,
                subject: $incident,
                event: 'assignment.created',
                logName: 'assignment',
                properties: [
                    'assigned_to' => $personnel->id,
                    'assigned_to_name' => $personnel->display_title,
                ],
            );

            $this->notifications->notifyPersonnelAssigned($assignment);

            return $assignment->fresh();
        });
    }

    public function completeAssignment(Assignment $assignment): Assignment
    {
        return DB::transaction(function () use ($assignment) {
            $assignment->update([
                'is_active' => false,
                'completed_at' => now(),
            ]);

            $description = $assignment->agency
                ? "Assignment completed for {$assignment->agency->name}"
                : "Assignment completed for {$assignment->assignee->name}";

            $this->activityLogs->log(
                description: $description,
                subject: $assignment->incident,
                event: 'assignment.completed',
                logName: 'assignment',
                properties: [
                    'agency_id' => $assignment->agency_id,
                    'assigned_to' => $assignment->assigned_to,
                    'assignment_id' => $assignment->id,
                ],
            );

            return $assignment->fresh();
        });
    }
}

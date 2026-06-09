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

    public function completeAssignment(Assignment $assignment): Assignment
    {
        return DB::transaction(function () use ($assignment) {
            $assignment->update([
                'is_active' => false,
                'completed_at' => now(),
            ]);

            $this->activityLogs->log(
                description: "Assignment completed for {$assignment->agency->name}",
                subject: $assignment->incident,
                event: 'assignment.completed',
                logName: 'assignment',
                properties: [
                    'agency_id' => $assignment->agency_id,
                    'assignment_id' => $assignment->id,
                ],
            );

            return $assignment->fresh();
        });
    }
}

<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\UpdateStatusRequest;
use App\Models\Assignment;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use App\Services\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function __construct(
        private readonly IncidentRepositoryInterface $incidents,
        private readonly IncidentService $incidentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()?->agency_id;

        abort_if(! $agencyId, 403, 'No agency is associated with this account.');

        $incidents = $this->incidents->paginateForAgency(
            $agencyId,
            (int) $request->integer('per_page', 15)
        );

        return response()->json($incidents);
    }

    public function show(Request $request, int $incident): JsonResponse
    {
        $record = $this->incidents->findById($incident);

        abort_if(! $record, 404);
        abort_if($record->agency_id !== $request->user()?->agency_id, 403);

        return response()->json($record);
    }

    public function updateStatus(UpdateStatusRequest $request, int $incident): JsonResponse
    {
        $record = $this->incidents->findById($incident);

        abort_if(! $record, 404);
        abort_if($record->agency_id !== $request->user()?->agency_id, 403);

        $data = $request->validated();
        $newStatus = \App\Enums\IncidentStatus::from($data['status']);

        if (! $this->incidentService->canTransitionTo($record, $newStatus)) {
            return response()->json([
                'message' => 'Invalid status transition.',
                'current_status' => $record->status->value,
            ], 422);
        }

        $comment = $data['comment'] ?? null;
        if ($newStatus->value === 'pending_info') {
            $comment = 'Awaiting information: ' . ($data['needs_info'] ?? $comment);
        }

        $updated = $this->incidentService->recordStatusChange(
            incident: $record,
            toStatus: $newStatus,
            user: $request->user(),
            comment: $comment,
            isPublic: true,
        );

        return response()->json([
            'message' => 'Status updated successfully.',
            'incident' => $updated->fresh(),
        ]);
    }

    public function acceptAssignment(Request $request, int $incident): JsonResponse
    {
        $record = $this->incidents->findById($incident);

        abort_if(! $record, 404);
        abort_if($record->agency_id !== $request->user()?->agency_id, 403);

        $assignment = Assignment::where('incident_id', $record->id)
            ->where('agency_id', $request->user()->agency_id)
            ->where('is_active', true)
            ->first();

        abort_if(! $assignment, 404, 'No active assignment found for this incident.');

        $updated = $this->incidentService->recordStatusChange(
            incident: $record,
            toStatus: \App\Enums\IncidentStatus::InProgress,
            user: $request->user(),
            comment: 'Assignment accepted and incident under investigation',
            isPublic: true,
        );

        return response()->json([
            'message' => 'Assignment accepted. Incident is now in progress.',
            'incident' => $updated->fresh(),
            'assignment' => $assignment->fresh(),
        ]);
    }
}

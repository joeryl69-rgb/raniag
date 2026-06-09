<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ValidateIncidentRequest;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use App\Services\AssignmentService;
use App\Services\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function __construct(
        private readonly IncidentRepositoryInterface $incidents,
        private readonly IncidentService $incidentService,
        private readonly AssignmentService $assignmentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $incidents = $this->incidents->paginateAll(
            (int) $request->integer('per_page', 15)
        );

        return response()->json($incidents);
    }

    public function show(int $incident): JsonResponse
    {
        $record = $this->incidents->findById($incident);

        abort_if(! $record, 404);

        return response()->json($record);
    }

    public function validate(ValidateIncidentRequest $request, int $incident): JsonResponse
    {
        $record = $this->incidents->findById($incident);
        abort_if(! $record, 404);

        $data = $request->validated();

        if ($data['action'] === 'reject') {
            $this->incidentService->recordStatusChange(
                incident: $record,
                toStatus: IncidentStatus::Rejected,
                user: $request->user(),
                comment: $data['notes'] ?? 'Report rejected',
                isPublic: true,
            );

            return response()->json([
                'message' => 'Incident report rejected.',
                'incident' => $record->fresh(),
            ]);
        }

        $this->incidentService->recordStatusChange(
            incident: $record,
            toStatus: IncidentStatus::Received,
            user: $request->user(),
            comment: 'Report validated and received',
            isPublic: true,
        );

        $agency = $this->incidents->findById($data['assigned_agency_id']);
        abort_if(! $agency, 404);

        $assignment = $this->assignmentService->assignToAgency(
            incident: $record,
            agency: \App\Models\Agency::findOrFail($data['assigned_agency_id']),
            assignedBy: $request->user(),
            data: ['notes' => $data['notes'] ?? null],
        );

        return response()->json([
            'message' => 'Incident approved and assigned.',
            'incident' => $record->fresh(),
            'assignment' => $assignment,
        ], 201);
    }

    public function assignments(int $incident, Request $request): JsonResponse
    {
        $record = $this->incidents->findById($incident);
        abort_if(! $record, 404);

        $assignments = $record->assignments()
            ->with(['agency', 'assigner', 'assignee'])
            ->paginate((int) $request->integer('per_page', 15));

        return response()->json($assignments);
    }
}

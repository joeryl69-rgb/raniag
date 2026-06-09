<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateAssignmentRequest;
use App\Models\Agency;
use App\Models\Assignment;
use App\Models\Incident;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentService $assignmentService,
    ) {}

    public function store(CreateAssignmentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $incident = Incident::findOrFail($data['incident_id']);
        $agency = Agency::findOrFail($data['agency_id']);

        $assignment = $this->assignmentService->assignToAgency(
            incident: $incident,
            agency: $agency,
            assignedBy: $request->user(),
            data: ['notes' => $data['notes'] ?? null],
        );

        return response()->json([
            'message' => 'Assignment created successfully.',
            'assignment' => $assignment->load(['incident', 'agency', 'assigner']),
        ], 201);
    }

    public function update(Request $request, Assignment $assignment): JsonResponse
    {
        $request->validate([
            'notes' => ['sometimes', 'string', 'max:1000'],
        ]);

        $assignment->update($request->only('notes'));

        return response()->json([
            'message' => 'Assignment updated.',
            'assignment' => $assignment->fresh()->load(['incident', 'agency']),
        ]);
    }

    public function complete(Request $request, Assignment $assignment): JsonResponse
    {
        abort_if(! $assignment->is_active, 422, 'Assignment is already completed.');

        $completed = $this->assignmentService->completeAssignment($assignment);

        return response()->json([
            'message' => 'Assignment completed.',
            'assignment' => $completed->load(['incident', 'agency']),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateAssignmentRequest;
use App\Models\Agency;
use App\Models\Assignment;
use App\Models\Incident;
use App\Models\User;
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

        if (! empty($data['assigned_to'])) {
            $personnel = User::where('role', UserRole::Personnel)
                ->where('is_active', true)
                ->findOrFail($data['assigned_to']);

            $assignment = $this->assignmentService->assignToPersonnel(
                incident: $incident,
                personnel: $personnel,
                assignedBy: $request->user(),
                data: ['notes' => $data['notes'] ?? null],
            );
        } else {
            $agency = Agency::findOrFail($data['agency_id']);
            $assignment = $this->assignmentService->assignToAgency(
                incident: $incident,
                agency: $agency,
                assignedBy: $request->user(),
                data: ['notes' => $data['notes'] ?? null],
            );
        }

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

    public function complete(Request $request, Assignment $assignment)
    {
        abort_if(! $assignment->is_active, 422, 'Assignment is already completed.');

        $completed = $this->assignmentService->completeAssignment($assignment);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Assignment completed.',
                'assignment' => $completed->load(['incident', 'agency']),
            ]);
        }

        return redirect()
            ->route('admin.incidents.show', $assignment->incident_id)
            ->with('success', 'Assignment has been marked as completed. Please review incident status and close if resolved.');
    }
}

<?php

namespace App\Http\Controllers\Personnel;

use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\UpdateStatusRequest;
use App\Models\Assignment;
use App\Models\Incident;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use App\Services\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncidentController extends Controller
{
    public function __construct(
        private readonly IncidentRepositoryInterface $incidents,
        private readonly IncidentService $incidentService,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $personnelId = $request->user()?->id;
        abort_if(! $personnelId, 403, 'No personnel account is associated with this login.');

        $incidents = Incident::query()
            ->with(['incidentType', 'agency'])
            ->whereHas('assignments', function ($q) use ($personnelId) {
                $q->where('assigned_to', $personnelId);
            })
            ->latest('reported_at')
            ->paginate((int) $request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json($incidents);
        }

        return view('personnel.incidents.index', compact('incidents'));
    }

    public function show(Request $request, int $incident): View|JsonResponse
    {
        $record = $this->incidents->findById($incident);
        abort_if(! $record, 404);

        $personnelId = $request->user()?->id;
        abort_if(! $personnelId, 403);

        $hasAnyAssignmentForPersonnel = $record->currentAssignments()
            ->where('assigned_to', $personnelId)
            ->exists();

        abort_if(
            ! $hasAnyAssignmentForPersonnel,
            403,
            'This case is not assigned to your personnel account.'
        );

        if ($request->wantsJson()) {
            return response()->json($record);
        }

        return view('personnel.incidents.show', ['incident' => $record]);
    }

    public function updateStatus(UpdateStatusRequest $request, int $incident): RedirectResponse|JsonResponse
    {
        $record = $this->incidents->findById($incident);
        abort_if(! $record, 404);

        $personnelId = $request->user()?->id;
        abort_if(! $personnelId, 403);

        $hasActiveAssignment = $record->currentAssignments()
            ->where('assigned_to', $personnelId)
            ->where('is_active', true)
            ->exists();

        abort_if(
            ! $hasActiveAssignment,
            403,
            'Your personnel account does not have an active assignment on this incident.'
        );

        $data = $request->validated();
        $newStatus = IncidentStatus::from($data['status']);

        if ($newStatus !== $record->status && ! $this->incidentService->canTransitionTo($record, $newStatus)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Invalid status transition.',
                    'current_status' => $record->status->value,
                ], 422);
            }

            return redirect()
                ->route('personnel.incidents.show', $record->id)
                ->with('error', 'Invalid status transition from '.$record->status->value.'.');
        }

        $comment = $data['comment'] ?? null;
        if ($newStatus->value === 'pending_info') {
            $comment = 'Awaiting information: '.($data['needs_info'] ?? $comment);
        }

        $updated = $this->incidentService->recordStatusChange(
            incident: $record,
            toStatus: $newStatus,
            user: $request->user(),
            comment: $comment,
            isPublic: true,
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Status updated successfully.',
                'incident' => $updated->fresh(),
            ]);
        }

        return redirect()
            ->route('personnel.incidents.show', $record->id)
            ->with('success', 'Case investigation status successfully logged.');
    }

    public function acceptAssignment(Request $request, int $incident): RedirectResponse|JsonResponse
    {
        $record = $this->incidents->findById($incident);
        abort_if(! $record, 404);

        $personnelId = $request->user()?->id;
        abort_if(! $personnelId, 403);

        $assignment = Assignment::where('incident_id', $record->id)
            ->where('assigned_to', $personnelId)
            ->where('is_active', true)
            ->where('created_at', '>=', $record->created_at)
            ->first();

        abort_if(! $assignment, 403, 'No active assignment found for this incident for your account.');

        $updated = $this->incidentService->recordStatusChange(
            incident: $record,
            toStatus: IncidentStatus::InProgress,
            user: $request->user(),
            comment: 'Assignment accepted and incident under active investigation',
            isPublic: true,
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Assignment accepted. Incident is now in progress.',
                'incident' => $updated->fresh(),
                'assignment' => $assignment->fresh(),
            ]);
        }

        return redirect()
            ->route('personnel.incidents.show', $record->id)
            ->with('success', 'Emergency dispatch assignment acknowledged. Case is now under active investigation.');
    }
}

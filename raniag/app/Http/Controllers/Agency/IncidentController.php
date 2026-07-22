<?php

namespace App\Http\Controllers\Agency;

use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\UpdateStatusRequest;
use App\Models\Assignment;
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
        $agencyId = $request->user()?->agency_id;

        abort_if(! $agencyId, 403, 'No agency is associated with this account.');

        $incidents = $this->incidents->paginateForAgency(
            $agencyId,
            (int) $request->integer('per_page', 15)
        );

        if ($request->wantsJson()) {
            return response()->json($incidents);
        }

        return view('agency.incidents.index', compact('incidents'));
    }

    public function show(Request $request, int $incident): View|JsonResponse
    {
        $record = $this->incidents->findById($incident);

        abort_if(! $record, 404);

        $agencyId = $request->user()?->agency_id;
        abort_if(! $agencyId, 403);

        // Access rule (revised):
        // Agency users must be able to view the incident case file
        // even after resolving, as long as this agency was involved.
        // If this agency has ANY assignment (past or active), allow access.

        $hasAnyAssignmentForAgency = $record->currentAssignments()
            ->where('agency_id', $request->user()?->agency_id)
            ->exists();

        abort_if(
            ! $hasAnyAssignmentForAgency,
            403,
            'This case is not associated with your agency.'
        );

        // (Compatibility note)
        // If your IDE/static analyzer complains about abort_if signature mismatch,
        // this is still valid in Laravel. Runtime behavior is what matters.

        if ($request->wantsJson()) {
            return response()->json($record);
        }

        return view('agency.incidents.show', ['incident' => $record]);

    }

    public function updateStatus(UpdateStatusRequest $request, int $incident): RedirectResponse|JsonResponse
    {
        $record = $this->incidents->findById($incident);

        abort_if(! $record, 404);

        // Agency must have an active assignment on this incident to update status
        $agencyId = $request->user()?->agency_id;
        abort_if(! $agencyId, 403);

        $hasActiveAssignment = $record->currentAssignments()
            ->where('agency_id', $agencyId)
            ->where('is_active', true)
            ->exists();

        abort_if(
            ! $hasActiveAssignment,
            403,
            'Your agency does not have an active assignment on this incident.'
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
                ->route('agency.incidents.show', $record->id)
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
            ->route('agency.incidents.show', $record->id)
            ->with('success', 'Case investigation status successfully logged.');
    }

    public function acceptAssignment(Request $request, int $incident): RedirectResponse|JsonResponse
    {
        $record = $this->incidents->findById($incident);

        abort_if(! $record, 404);

        $agencyId = $request->user()?->agency_id;
        abort_if(! $agencyId, 403);

        $assignment = Assignment::where('incident_id', $record->id)
            ->where('agency_id', $agencyId)
            ->where('is_active', true)
            ->where('created_at', '>=', $record->created_at)
            ->first();

        abort_if(! $assignment, 403, 'No active assignment found for this incident for your agency.');

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
            ->route('agency.incidents.show', $record->id)
            ->with('success', 'Emergency dispatch assignment acknowledged. Case is now under active investigation.');
    }
}

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

        $filters = $request->only(['status', 'priority', 'barangay', 'q', 'sort', 'direction', 'date_from', 'date_to']);
        $filters['viewer_agency_id'] = $request->user()?->agency_id;

        $incidents = $this->incidents->paginateForPersonnel(
            $personnelId,
            (int) $request->integer('per_page', 15),
            $filters
        );

        if ($request->wantsJson()) {
            return response()->json($incidents);
        }

        $agencyId = $request->user()?->agency_id;
        $barangays = Incident::query()
            ->whereHas('assignments', function ($q) use ($personnelId, $agencyId) {
                $q->where('assignments.assigned_to', $personnelId);
                if ($agencyId) {
                    $q->orWhere('assignments.agency_id', $agencyId);
                }
            })
            ->whereNotNull('barangay')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay');

        return view('personnel.incidents.index', compact('incidents', 'filters', 'barangays'));
    }

    public function show(Request $request, int $incident): View|JsonResponse
    {
        $record = $this->incidents->findById($incident);
        abort_if(! $record, 404);

        $personnelId = $request->user()?->id;
        abort_if(! $personnelId, 403);

        $agencyId = $request->user()?->agency_id;
        $hasAnyAssignmentForPersonnel = \Illuminate\Support\Facades\DB::table('assignments')
            ->where('incident_id', $record->id)
            ->where(function ($q) use ($personnelId, $agencyId) {
                $q->where('assigned_to', $personnelId);
                if ($agencyId) {
                    $q->orWhere('agency_id', $agencyId);
                }
            })
            ->exists();

        abort_if(
            ! $hasAnyAssignmentForPersonnel,
            403,
            'This case is not assigned to your personnel account.'
        );

        if ($request->wantsJson()) {
            return response()->json($record);
        }

        return view('personnel.incidents.show', [
            'incident' => $record,
            'gpsConfig' => [
                'max_captures' => config('raniag.gps_camera.max_captures'),
                'jpeg_quality' => config('raniag.gps_camera.jpeg_quality'),
                'geolocation' => [
                    'enableHighAccuracy' => config('raniag.geolocation.enable_high_accuracy'),
                    'timeout' => config('raniag.geolocation.timeout_ms'),
                    'maximumAge' => config('raniag.geolocation.maximum_age_ms'),
                ],
            ],
        ]);
    }

    public function updateStatus(UpdateStatusRequest $request, int $incident): RedirectResponse|JsonResponse
    {
        $record = $this->incidents->findById($incident);
        abort_if(! $record, 404);

        $personnelId = $request->user()?->id;
        abort_if(! $personnelId, 403);

        $agencyId = $request->user()?->agency_id;
        $hasActiveAssignment = $record->currentAssignments()
            ->where('is_active', true)
            ->where(function ($q) use ($personnelId, $agencyId) {
                $q->where('assigned_to', $personnelId);
                if ($agencyId) {
                    $q->orWhere('agency_id', $agencyId);
                }
            })
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
        $isPublicUpdate = true;
        if ($newStatus->value === 'pending_info') {
            $comment = 'Awaiting information: '.($data['needs_info'] ?? $comment);
            $isPublicUpdate = false;
        }

        $updated = $this->incidentService->recordStatusChange(
            incident: $record,
            toStatus: $newStatus,
            user: $request->user(),
            comment: $comment,
            isPublic: $isPublicUpdate,
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

        $agencyId = $request->user()?->agency_id;
        $assignment = Assignment::where('incident_id', $record->id)
            ->where('is_active', true)
            ->where(function ($q) use ($personnelId, $agencyId) {
                $q->where('assigned_to', $personnelId);
                if ($agencyId) {
                    $q->orWhere('agency_id', $agencyId);
                }
            })
            ->latest('created_at')
            ->first();

        abort_if(! $assignment, 403, 'No active assignment found for this incident for your account.');
        abort_if($assignment->isAcknowledged(), 409, 'This assignment has already been accepted.');

        $updated = $this->incidentService->logAssignmentAcknowledged($assignment, $record, $request->user());

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

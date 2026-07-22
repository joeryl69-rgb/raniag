<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\SubmitResolutionRequest;
use App\Models\Assignment;
use App\Models\Incident;
use App\Models\Resolution;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use App\Services\AssignmentService;
use App\Services\EvidenceService;
use App\Services\ResolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ResolutionController extends Controller
{
    public function __construct(
        private readonly ResolutionService $resolutionService,
        private readonly EvidenceService $evidenceService,
        private readonly IncidentRepositoryInterface $incidents,
        private readonly AssignmentService $assignmentService,
    ) {}

    public function store(SubmitResolutionRequest $request, int $incident): RedirectResponse|JsonResponse
    {
        $record = $this->incidents->findById($incident);

        abort_if(! $record, 404);

        $data = $request->validated();

        // Ensure this agency actually has an active assignment for this incident.
        $activeAssignment = Assignment::where('incident_id', $record->id)
            ->where('agency_id', $request->user()?->agency_id)
            ->where('is_active', true)
            ->latest('assigned_at')
            ->first();

        abort_if(! $activeAssignment, 403, 'No active assignment found for this incident for your agency.');

        // Important: mark assignment(s) inactive FIRST so ResolutionService computes
        // the correct global incident status based on remaining active assignments.
        //
        // Because the agency might have more than one active assignment row for the
        // same incident (data inconsistency or previous dispatch), we complete ALL
        // active assignments for THIS incident + THIS agency.
        //
        // The incident becomes globally terminal only when *all* agencies have
        // completed their assignments (handled inside ResolutionService).
        $activeAssignments = Assignment::query()
            ->where('incident_id', $record->id)
            ->where('agency_id', $request->user()?->agency_id)
            ->where('is_active', true)
            ->orderByDesc('assigned_at')
            ->get();

        foreach ($activeAssignments as $assignment) {
            $this->assignmentService->completeAssignment($assignment);
        }

        $resolution = $this->resolutionService->submitResolution(
            incident: $record,
            resolvedBy: $request->user(),
            data: [
                'summary' => $data['summary'],
                'actions_taken' => $data['actions_taken'],
            ],
        );

        if (! empty($data['evidence'])) {
            $this->evidenceService->attachToIncident(
                $record,
                $data['evidence'],
                [],
                $request->user()->id
            );
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Resolution submitted successfully.',
                'resolution' => $resolution->load('incident'),
                'incident' => $record->fresh()->load('evidence'),
            ], 201);
        }

        $stillActive = Assignment::query()
            ->where('incident_id', $record->id)
            ->where('is_active', true)
            ->exists();

        $successMessage = $stillActive
            ? 'Case resolution submitted. Waiting for other agencies to complete this case.'
            : 'Case resolution submitted. This case has been resolved.';

        return redirect()
            ->route('agency.incidents.show', $record->id)
            ->with('success', $successMessage);

    }

    public function update(SubmitResolutionRequest $request, int $incident, int $resolutionId): RedirectResponse|JsonResponse
    {
        $record = $this->incidents->findById($incident);
        abort_if(! $record, 404);

        $resolution = Resolution::with('resolver')->where('incident_id', $record->id)
            ->where('id', $resolutionId)
            ->firstOrFail();

        // Ensure this resolution belongs to the agency
        $agencyId = $request->user()?->agency_id;
        $resolverAgencyId = $resolution->resolver?->agency_id;
        abort_if($agencyId !== $resolverAgencyId, 403, 'You can only edit resolutions submitted by your agency.');

        abort_if(in_array($record->status->value, ['resolved', 'closed']), 403, 'You can no longer edit this resolution because the case has been globally resolved.');

        $data = $request->validated();

        $resolution->update([
            'summary' => $data['summary'],
            'actions_taken' => $data['actions_taken'],
        ]);

        if (! empty($data['evidence'])) {
            $this->evidenceService->attachToIncident(
                $record,
                $data['evidence'],
                [],
                $request->user()->id
            );
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Resolution updated successfully.',
                'resolution' => $resolution->fresh(),
                'incident' => $record->fresh()->load('evidence'),
            ]);
        }

        return redirect()
            ->route('agency.incidents.show', $record->id)
            ->with('success', 'Resolution details updated successfully.');
    }
}

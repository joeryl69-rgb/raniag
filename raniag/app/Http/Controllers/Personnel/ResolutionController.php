<?php

namespace App\Http\Controllers\Personnel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\SubmitResolutionRequest;
use App\Models\Assignment;
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

        $activeAssignment = Assignment::where('incident_id', $record->id)
            ->where('assigned_to', $request->user()?->id)
            ->where('is_active', true)
            ->latest('assigned_at')
            ->first();

        abort_if(! $activeAssignment, 403, 'No active assignment found for this incident for your account.');

        $activeAssignments = Assignment::query()
            ->where('incident_id', $record->id)
            ->where('assigned_to', $request->user()?->id)
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
            ? 'Case resolution submitted. Waiting for other responders to complete this case.'
            : 'Case resolution submitted. This case has been resolved.';

        return redirect()
            ->route('personnel.incidents.show', $record->id)
            ->with('success', $successMessage);
    }

    public function update(SubmitResolutionRequest $request, int $incident, int $resolutionId): RedirectResponse|JsonResponse
    {
        $record = $this->incidents->findById($incident);
        abort_if(! $record, 404);

        $resolution = Resolution::with('resolver')->where('incident_id', $record->id)
            ->where('id', $resolutionId)
            ->firstOrFail();

        $resolverId = $resolution->resolver?->id;
        abort_if($request->user()->id !== $resolverId, 403, 'You can only edit resolutions submitted by your account.');

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
            ->route('personnel.incidents.show', $record->id)
            ->with('success', 'Resolution details updated successfully.');
    }
}

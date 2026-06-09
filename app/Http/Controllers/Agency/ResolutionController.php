<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\SubmitResolutionRequest;
use App\Models\Incident;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use App\Services\EvidenceService;
use App\Services\ResolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResolutionController extends Controller
{
    public function __construct(
        private readonly ResolutionService $resolutionService,
        private readonly EvidenceService $evidenceService,
        private readonly IncidentRepositoryInterface $incidents,
    ) {}

    public function store(SubmitResolutionRequest $request, int $incident): JsonResponse
    {
        $record = $this->incidents->findById($incident);

        abort_if(! $record, 404);
        abort_if($record->agency_id !== $request->user()?->agency_id, 403);

        $data = $request->validated();

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
                isGpsCapture: false,
            );
        }

        return response()->json([
            'message' => 'Resolution submitted successfully.',
            'resolution' => $resolution->load('incident'),
            'incident' => $record->fresh()->load('evidence'),
        ], 201);
    }
}

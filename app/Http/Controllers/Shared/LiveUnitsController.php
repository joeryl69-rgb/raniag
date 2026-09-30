<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Incident;
use App\Services\SituationalMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveUnitsController extends Controller
{
    public function __construct(
        private readonly SituationalMapService $situational,
    ) {}

    public function __invoke(Request $request, Incident $incident): JsonResponse
    {
        $this->authorize('view', $incident);

        try {
            $units = $this->situational->liveUnitsForIncident($incident, forPublic: false);
        } catch (\Throwable $e) {
            report($e);
            $units = [];
        }

        $assigned = Assignment::query()
            ->with('agency:id,name')
            ->where('incident_id', $incident->id)
            ->where('is_active', true)
            ->get()
            ->map(fn (Assignment $assignment) => [
                'label' => $assignment->agency?->name ?: 'Assigned agency',
                'field_phase' => $assignment->field_phase ?: 'assigned',
            ])
            ->unique('label')
            ->values();

        return response()->json([
            'units' => $units,
            'assigned' => $assigned,
            'scene' => [
                'latitude' => $incident->latitude !== null ? (float) $incident->latitude : null,
                'longitude' => $incident->longitude !== null ? (float) $incident->longitude : null,
            ],
            'updated_at' => now()->toIso8601String(),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE)->header('Cache-Control', 'no-store, private');
    }
}

<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Incident;
use App\Services\SituationalMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LiveUnitsController extends Controller
{
    public function __construct(
        private readonly SituationalMapService $situational,
    ) {}

    public function __invoke(Request $request, Incident $incident): JsonResponse
    {
        Gate::authorize('view', $incident);

        $units = $this->situational->liveUnitsForIncident($incident, forPublic: false);
        $assigned = Assignment::query()
            ->with(['agency', 'assignee'])
            ->where('incident_id', $incident->id)
            ->where('is_active', true)
            ->get()
            ->map(fn (Assignment $assignment) => [
                'label' => $assignment->agency?->name ?: $assignment->assignee?->name ?: 'Assigned responder',
                'field_phase' => $assignment->field_phase ?: 'assigned',
            ])
            ->unique('label')
            ->values()
            ->all();

        return response()->json([
            'units' => $units,
            'assigned' => $assigned,
            'scene' => [
                'latitude' => $incident->latitude !== null ? (float) $incident->latitude : null,
                'longitude' => $incident->longitude !== null ? (float) $incident->longitude : null,
            ],
            'updated_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store, private');
    }
}

<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
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

        return response()->json([
            'units' => $this->situational->liveUnitsForIncident($incident, forPublic: false),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}

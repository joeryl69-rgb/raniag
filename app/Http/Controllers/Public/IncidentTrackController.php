<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\TrackIncidentRequest;
use App\Services\IncidentService;
use App\Services\SituationalMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncidentTrackController extends Controller
{
    public function __construct(
        private readonly IncidentService $incidentService,
        private readonly SituationalMapService $situational,
    ) {}

    public function index(Request $request): View
    {
        return view('public.track.index', [
            'prefillTrackingNumber' => $request->query('tracking_number'),
        ]);
    }

    public function show(TrackIncidentRequest $request): View|JsonResponse|RedirectResponse
    {
        return $this->resolveTrackingView(
            $request->validated('tracking_number'),
            $request->wantsJson(),
        );
    }

    public function liveUnits(string $trackingNumber): JsonResponse
    {
        $incident = $this->incidentService->findByTrackingNumber(strtoupper(trim($trackingNumber)));
        abort_if(! $incident, 404);
        abort_unless(session()->get('track_verified.'.$incident->id) === true, 403);

        return response()->json([
            'units' => $this->situational->liveUnitsForIncident($incident, forPublic: true),
            'scene' => [
                'latitude' => $incident->latitude !== null ? (float) $incident->latitude : null,
                'longitude' => $incident->longitude !== null ? (float) $incident->longitude : null,
            ],
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    private function resolveTrackingView(string $trackingNumber, bool $asJson = false): View|JsonResponse|RedirectResponse
    {
        $incident = $this->incidentService->findByTrackingNumber(
            strtoupper(trim($trackingNumber))
        );

        if (! $incident) {
            if ($asJson) {
                abort(404, 'No incident found for the provided tracking number.');
            }

            return redirect()
                ->route('public.track')
                ->withInput([
                    'tracking_number' => $trackingNumber,
                ])
                ->withErrors([
                    'tracking_number' => 'No matching report for that tracking number. Check it and try again.',
                ]);
        }

        if ($asJson) {
            return response()->json([
                'tracking_number' => $incident->tracking_number,
                'status' => $incident->status,
                'priority' => $incident->priority,
                'incident_type' => $incident->incidentType,
                'reported_at' => $incident->reported_at,
                'status_updates' => $incident->statusUpdates,
            ]);
        }

        session()->put('track_verified.'.$incident->id, true);

        $incident->loadMissing(['evidence', 'incidentType', 'assignments.agency']);

        return view('public.track.show', [
            'incident' => $incident,
            'canReply' => $incident->status === \App\Enums\IncidentStatus::PendingInfo
                && session()->get('track_verified.'.$incident->id) === true,
            'nearestCenter' => $this->nearestOpenCenter($incident),
            'map' => config('raniag.map'),
            'unitsUrl' => route('public.track.units', $incident->tracking_number),
            'liveUnits' => $this->situational->liveUnitsForIncident($incident, forPublic: true),
        ]);
    }

    public function reply(Request $request, string $trackingNumber): RedirectResponse
    {
        $incident = $this->incidentService->findByTrackingNumber(strtoupper(trim($trackingNumber)));
        abort_if(! $incident, 404);
        abort_unless(session()->get('track_verified.'.$incident->id) === true, 403);
        abort_unless($incident->status === \App\Enums\IncidentStatus::PendingInfo, 422);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $this->incidentService->recordStatusChange(
            incident: $incident,
            toStatus: \App\Enums\IncidentStatus::InProgress,
            user: null,
            comment: 'Reporter reply: '.$data['message'],
            isPublic: true,
        );

        return redirect()
            ->route('public.track')
            ->with('success', 'Your reply was sent. Responders will continue from your update.');
    }

    private function nearestOpenCenter(\App\Models\Incident $incident): ?array
    {
        if ($incident->latitude === null || $incident->longitude === null) {
            return null;
        }
        if (! \Illuminate\Support\Facades\Schema::hasTable('evacuation_centers')) {
            return null;
        }

        $lat = (float) $incident->latitude;
        $lng = (float) $incident->longitude;
        $best = null;
        $bestDist = PHP_FLOAT_MAX;
        foreach (\App\Models\EvacuationCenter::query()->where('is_open', true)->get() as $center) {
            $dLat = deg2rad((float) $center->latitude - $lat);
            $dLng = deg2rad((float) $center->longitude - $lng);
            $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat)) * cos(deg2rad((float) $center->latitude)) * sin($dLng / 2) ** 2;
            $d = 2 * 6371000 * asin(min(1, sqrt($a)));
            if ($d < $bestDist) {
                $bestDist = $d;
                $best = $center;
            }
        }

        if (! $best) {
            return null;
        }

        return [
            'name' => $best->name,
            'distance_m' => (int) round($bestDist),
            'latitude' => $best->latitude,
            'longitude' => $best->longitude,
        ];
    }
}

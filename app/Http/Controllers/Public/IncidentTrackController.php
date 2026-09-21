<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\TrackIncidentRequest;
use App\Services\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncidentTrackController extends Controller
{
    public function __construct(
        private readonly IncidentService $incidentService,
    ) {}

    public function index(Request $request): View
    {
        // Prefill only — never auto-open a case from the query string (PIN required).
        return view('public.track.index', [
            'prefillTrackingNumber' => $request->query('tracking_number'),
        ]);
    }

    public function show(TrackIncidentRequest $request): View|JsonResponse|RedirectResponse
    {
        return $this->resolveTrackingView(
            $request->validated('tracking_number'),
            $request->validated('access_code'),
            $request->wantsJson(),
        );
    }

    private function resolveTrackingView(string $trackingNumber, string $accessCode, bool $asJson = false): View|JsonResponse|RedirectResponse
    {
        $incident = $this->incidentService->findByTrackingNumber(
            strtoupper(trim($trackingNumber))
        );

        if (! $incident || ! $incident->matchesTrackingAccessCode($accessCode)) {
            if ($asJson) {
                abort(404, 'No incident found for the provided tracking number and access code.');
            }

            return redirect()
                ->route('public.track')
                ->withInput([
                    'tracking_number' => $trackingNumber,
                    'access_code' => $accessCode,
                ])
                ->withErrors([
                    'tracking_number' => 'No matching report for that tracking number and access code. Check both and try again.',
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

        $incident->loadMissing('evidence');

        return view('public.track.show', [
            'incident' => $incident,
        ]);
    }
}

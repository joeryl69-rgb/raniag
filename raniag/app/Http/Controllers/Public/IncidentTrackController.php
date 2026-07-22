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

    public function index(Request $request): View|RedirectResponse
    {
        $trackingNumber = $request->query('tracking_number');

        if ($trackingNumber) {
            return $this->resolveTrackingView($trackingNumber);
        }

        return view('public.track.index');
    }

    public function show(TrackIncidentRequest $request): View|JsonResponse|RedirectResponse
    {
        return $this->resolveTrackingView(
            $request->validated('tracking_number'),
            $request->wantsJson(),
        );
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
                ->withInput(['tracking_number' => $trackingNumber])
                ->withErrors(['tracking_number' => 'No incident found for that tracking number. Please check and try again.']);
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

        return view('public.track.show', [
            'incident' => $incident,
        ]);
    }
}

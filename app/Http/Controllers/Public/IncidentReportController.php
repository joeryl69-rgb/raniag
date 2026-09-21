<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreIncidentReportRequest;
use App\Models\IncidentType;
use App\Services\GeofenceService;
use App\Services\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class IncidentReportController extends Controller
{
    public function __construct(
        private readonly IncidentService $incidentService,
        private readonly GeofenceService $geofence,
    ) {}

    public function create(): View
    {
        return view('public.report.create', [
            'incidentTypes' => IncidentType::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'barangays' => config('raniag.barangays', []),
            'mapConfig' => config('raniag.map'),
            'addressConfig' => config('raniag.address'),
            'boundaryGeometry' => $this->geofence->boundaryGeometry(),
            'barangayBoundaries' => $this->geofence->barangayBoundaries(),
            'evidenceConfig' => config('raniag.evidence'),
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

    public function store(StoreIncidentReportRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $idempotencyKey = $validated['idempotency_key'] ?? null;
        unset($validated['idempotency_key']);

        if (is_string($idempotencyKey) && $idempotencyKey !== '') {
            $cached = Cache::get('report:idem:'.$idempotencyKey);
            if (is_array($cached) && ! empty($cached['tracking_number'])) {
                return $this->duplicateResponse($request, $cached);
            }
        }

        $incident = $this->incidentService->submitAnonymousReport(
            $validated,
            $request->file('evidence', []) ?? [],
        );

        if (is_string($idempotencyKey) && $idempotencyKey !== '') {
            Cache::put('report:idem:'.$idempotencyKey, [
                'tracking_number' => $incident->tracking_number,
                'access_code' => $incident->plainTrackingPin,
            ], now()->addDay());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Incident report submitted successfully.',
                'tracking_number' => $incident->tracking_number,
                'access_code' => $incident->plainTrackingPin,
                'incident' => $incident,
            ], 201);
        }

        return redirect()
            ->route('public.report.success', $incident->tracking_number)
            ->with('success', 'Your incident report has been submitted successfully.')
            ->with('tracking_pin', $incident->plainTrackingPin);
    }

    public function success(string $trackingNumber): View
    {
        return view('public.report.success', [
            'trackingNumber' => strtoupper($trackingNumber),
            'trackingPin' => session('tracking_pin'),
        ]);
    }

    private function duplicateResponse(StoreIncidentReportRequest $request, array $cached): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Incident report already submitted.',
                'tracking_number' => $cached['tracking_number'],
                'access_code' => $cached['access_code'] ?? null,
                'duplicate' => true,
            ], 200);
        }

        return redirect()
            ->route('public.report.success', $cached['tracking_number'])
            ->with('success', 'Your incident report was already received.')
            ->with('tracking_pin', $cached['access_code'] ?? null);
    }
}

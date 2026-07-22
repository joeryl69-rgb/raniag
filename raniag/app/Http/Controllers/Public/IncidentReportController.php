<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreIncidentReportRequest;
use App\Models\IncidentType;
use App\Services\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IncidentReportController extends Controller
{
    public function __construct(
        private readonly IncidentService $incidentService,
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
        $incident = $this->incidentService->submitAnonymousReport(
            $request->validated(),
            $request->file('evidence', []),
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Incident report submitted successfully.',
                'tracking_number' => $incident->tracking_number,
                'incident' => $incident,
            ], 201);
        }

        return redirect()
            ->route('public.report.success', $incident->tracking_number)
            ->with('success', 'Your incident report has been submitted successfully.');
    }

    public function success(string $trackingNumber): View
    {
        return view('public.report.success', [
            'trackingNumber' => strtoupper($trackingNumber),
        ]);
    }
}

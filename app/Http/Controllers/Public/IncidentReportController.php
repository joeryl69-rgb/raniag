<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreIncidentReportRequest;
use App\Models\IncidentType;
use App\Models\Incident;
use App\Services\GeofenceService;
use App\Services\IncidentService;
use App\Services\WebPushService;
use Illuminate\Database\UniqueConstraintViolationException;
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
        $barangays = config('raniag.barangays', []);
        $prefillBarangay = request()->query('barangay');
        if (! is_string($prefillBarangay) || ! in_array($prefillBarangay, $barangays, true)) {
            $prefillBarangay = null;
        }

        return view('public.report.create', [
            'incidentTypes' => IncidentType::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'barangays' => $barangays,
            'prefillBarangay' => $prefillBarangay,
            'mapConfig' => config('raniag.map'),
            'addressConfig' => config('raniag.address'),
            'boundaryGeometry' => $this->geofence->boundaryGeometry(),
            'barangayBoundaries' => $this->geofence->barangayBoundaries(),
            'evidenceConfig' => config('raniag.evidence'),
            'gpsConfig' => [
                'max_captures' => config('raniag.gps_camera.max_captures'),
                'jpeg_quality' => config('raniag.gps_camera.jpeg_quality'),
                'mapbox_token' => config('raniag.map.mapbox_token'),
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
        if (! is_string($idempotencyKey) || $idempotencyKey === '') {
            $idempotencyKey = null;
            unset($validated['idempotency_key']);
        }

        $rememberIdempotency = function (Incident $incident) use ($idempotencyKey): void {
            if (! $idempotencyKey) {
                return;
            }
            Cache::put('report:idem:'.$idempotencyKey, [
                'tracking_number' => $incident->tracking_number,
            ], now()->addDay());
        };

        $existing = $this->existingIdempotentReport($idempotencyKey);
        if ($existing) {
            return $this->duplicateResponse($request, $existing);
        }

        $lock = $idempotencyKey
            ? Cache::lock('report:idem:lock:'.$idempotencyKey, 20)
            : null;

        try {
            try {
                $lock?->block(15);
            } catch (\Throwable) {
                $lock = null;
            }

            $existing = $this->existingIdempotentReport($idempotencyKey);
            if ($existing) {
                return $this->duplicateResponse($request, $existing);
            }

            $incident = $this->incidentService->submitAnonymousReport(
                $validated,
                $request->file('evidence', []) ?? [],
            );
            $rememberIdempotency($incident);
        } catch (UniqueConstraintViolationException $exception) {
            $existing = $this->existingIdempotentReport($idempotencyKey);
            if ($existing) {
                return $this->duplicateResponse($request, $existing);
            }

            throw $exception;
        } finally {
            $lock?->release();
        }

        $this->notifyOfflineDelivery($request, $incident, $idempotencyKey);

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
            'organization' => config('raniag.organization'),
        ]);
    }

    /**
     * @return array{tracking_number: string}|null
     */
    private function existingIdempotentReport(?string $idempotencyKey): ?array
    {
        if (! $idempotencyKey) {
            return null;
        }

        $cached = Cache::get('report:idem:'.$idempotencyKey);
        if (is_array($cached) && ! empty($cached['tracking_number'])) {
            return $cached;
        }

        $incident = Incident::query()
            ->withTrashed()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if (! $incident) {
            return null;
        }

        return ['tracking_number' => $incident->tracking_number];
    }

    private function notifyOfflineDelivery(
        StoreIncidentReportRequest $request,
        Incident $incident,
        ?string $idempotencyKey = null,
    ): void
    {
        if (! $request->boolean('offline_delivery')) {
            return;
        }

        $notificationKey = $idempotencyKey ? 'report:idem:push-notified:'.$idempotencyKey : null;
        $token = trim((string) $request->input('push_client_token', ''));
        if ($token === '') {
            return;
        }

        if ($notificationKey && ! Cache::add($notificationKey, true, now()->addDay())) {
            return;
        }

        app(WebPushService::class)->sendToReporter(
            $token,
            'Report submitted',
            'RANIAG received '.$incident->tracking_number.'. Open this notice to see the tracking page.',
            route('public.report.success', $incident->tracking_number),
        );
    }

    private function duplicateResponse(StoreIncidentReportRequest $request, array $cached): JsonResponse|RedirectResponse
    {
        if ($request->boolean('offline_delivery')) {
            $incident = Incident::query()
                ->where('tracking_number', $cached['tracking_number'])
                ->first();
            if ($incident) {
                $this->notifyOfflineDelivery(
                    $request,
                    $incident,
                    $request->input('idempotency_key'),
                );
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Incident report already submitted.',
                'tracking_number' => $cached['tracking_number'],
                'duplicate' => true,
            ], 200);
        }

        return redirect()
            ->route('public.report.success', $cached['tracking_number'])
            ->with('success', 'Your incident report was already received.');
    }
}

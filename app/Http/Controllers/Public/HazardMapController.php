<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EvacuationCenter;
use App\Models\HazardZone;
use App\Services\GeofenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HazardMapController extends Controller
{
    public function __construct(private readonly GeofenceService $geofence) {}

    public function index(): View
    {
        return view('public.hazard.map', [
            'map' => config('raniag.map'),
            'zones' => HazardZone::query()->with('type')->where('is_active', true)->get(),
            'centers' => EvacuationCenter::query()->where('is_open', true)->orderBy('name')->get(),
        ]);
    }

    public function nearestCenter(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ]);

        $lat = (float) $data['lat'];
        $lng = (float) $data['lng'];
        $best = null;
        $bestDist = PHP_FLOAT_MAX;

        foreach (EvacuationCenter::query()->where('is_open', true)->get() as $center) {
            $d = $this->haversine($lat, $lng, (float) $center->latitude, (float) $center->longitude);
            if ($d < $bestDist) {
                $bestDist = $d;
                $best = $center;
            }
        }

        $containing = [];
        foreach (HazardZone::query()->with('type')->where('is_active', true)->get() as $zone) {
            if ($this->geofence->pointInGeometry($lng, $lat, $zone->geometry ?? [])) {
                $containing[] = [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'type' => $zone->type?->name,
                    'advisory_note' => $zone->advisory_note,
                ];
            }
        }

        return response()->json([
            'nearest_center' => $best ? [
                'id' => $best->id,
                'name' => $best->name,
                'latitude' => $best->latitude,
                'longitude' => $best->longitude,
                'distance_m' => round($bestDist),
            ] : null,
            'hazard_zones' => $containing,
        ]);
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }
}

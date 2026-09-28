<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EvacuationCenter;
use App\Models\HazardZone;
use App\Services\GeofenceService;
use App\Services\SituationalMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class HazardMapController extends Controller
{
    public function __construct(
        private readonly GeofenceService $geofence,
        private readonly SituationalMapService $situational,
    ) {}

    public function index(): View
    {
        $snapshot = $this->situational->publicSnapshot();

        return view('public.hazard.map', [
            'map' => config('raniag.map'),
            'zones' => $snapshot['zones'],
            'centers' => $snapshot['centers'],
            'risk' => $snapshot['risk'],
            'snapshotUrl' => route('public.hazard.snapshot'),
            'nearestUrl' => route('public.hazard.nearest'),
        ]);
    }

    public function snapshot(): JsonResponse
    {
        return response()->json($this->situational->publicSnapshot());
    }

    public function barangay(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $lat = (float) $data['lat'];
        $lng = (float) $data['lng'];
        $name = $this->geofence->resolveBarangay($lat, $lng);
        $address = config('raniag.address');

        if ($name !== null) {
            return response()->json([
                'barangay' => $name,
                'municipality' => $address['municipality'] ?? 'Pamplona',
                'province' => $address['province'] ?? 'Cagayan',
                'country' => $address['country'] ?? 'Philippines',
                'inside' => true,
            ])->header('Cache-Control', 'no-store');
        }

        $outside = $this->reverseOutsidePamplona($lat, $lng);

        return response()->json([
            'barangay' => $outside['barangay'],
            'municipality' => $outside['municipality'],
            'province' => $outside['province'],
            'country' => $outside['country'],
            'inside' => false,
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Same-origin map tile so the GPS camera can draw it into a video.
     * A direct OpenStreetMap image cannot be painted onto a recording.
     */
    public function mapTile(int $z, int $x, int $y): Response
    {
        abort_unless($z === 16 && $x >= 0 && $x < 65536 && $y >= 0 && $y < 65536, 404);

        $bytes = Cache::get("osm-tile-{$z}-{$x}-{$y}");
        if (! is_string($bytes) || $bytes === '') {
            try {
                $remote = Http::timeout(4)
                    ->withHeaders(['User-Agent' => 'RANIAG-MDRRMO-Pamplona/1.0 (gps camera map)'])
                    ->get("https://tile.openstreetmap.org/{$z}/{$x}/{$y}.png");
            } catch (\Throwable) {
                abort(404);
            }
            abort_unless($remote->ok(), 404);
            $bytes = $remote->body();
            Cache::put("osm-tile-{$z}-{$x}-{$y}", $bytes, now()->addDay());
        }

        return response($bytes, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * A fix outside the Pamplona barangay polygons must not be labeled
     * Pamplona. Ask the public geocoder once; if it is unreachable, return
     * empty names so the camera keeps the raw coordinates.
     *
     * @return array{barangay: ?string, municipality: ?string, province: ?string, country: ?string}
     */
    private function reverseOutsidePamplona(float $lat, float $lng): array
    {
        $empty = ['barangay' => null, 'municipality' => null, 'province' => null, 'country' => null];

        try {
            $response = Http::timeout(3)
                ->withHeaders([
                    'User-Agent' => 'RANIAG-MDRRMO-Pamplona/1.0 (field camera)',
                    'Accept' => 'application/json',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $lat,
                    'lon' => $lng,
                    'zoom' => 16,
                    'addressdetails' => 1,
                ]);
        } catch (\Throwable) {
            return $empty;
        }

        if (! $response->ok()) {
            return $empty;
        }

        $addr = $response->json('address') ?? [];
        $barangay = $addr['village'] ?? $addr['suburb'] ?? $addr['hamlet'] ?? $addr['neighbourhood'] ?? null;
        $municipality = $addr['city'] ?? $addr['town'] ?? $addr['municipality'] ?? null;

        return [
            'barangay' => is_string($barangay) ? $barangay : null,
            'municipality' => is_string($municipality) ? $municipality : null,
            'province' => is_string($addr['state'] ?? null) ? $addr['state'] : (is_string($addr['province'] ?? null) ? $addr['province'] : null),
            'country' => is_string($addr['country'] ?? null) ? $addr['country'] : null,
        ];
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

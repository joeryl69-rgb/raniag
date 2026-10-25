<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EvacuationCenter;
use App\Models\Evidence;
use App\Models\HazardZone;
use App\Services\GeofenceService;
use App\Services\SituationalMapService;
use App\Support\PrivateIncidentFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'boundary' => $this->geofence->boundaryGeometry(),
            'zones' => $snapshot['zones'],
            'centers' => $snapshot['centers'],
            'risk' => $snapshot['risk'],
            'openReports' => $snapshot['open_reports'],
            'snapshotUrl' => route('public.hazard.snapshot'),
            'nearestUrl' => route('public.hazard.nearest'),
        ]);
    }

    public function snapshot(): JsonResponse
    {
        return response()->json($this->situational->publicSnapshot());
    }

    /**
     * GPS photo or video the reporter attached to an open report inside Pamplona.
     * Staff uploads and closed or outside reports are not served.
     */
    public function reportMedia(Evidence $evidence): StreamedResponse|BinaryFileResponse
    {
        if (! $this->situational->publicGpsEvidenceIsVisible($evidence)) {
            abort(404);
        }

        $path = $evidence->file_path;
        if (! $path || ! PrivateIncidentFiles::exists($path)) {
            abort(404);
        }

        $absolute = PrivateIncidentFiles::absolutePath($path);
        $mime = (string) $evidence->mime_type;
        $isVideo = str_starts_with($mime, 'video/');
        $downloadName = $isVideo ? 'gps-video.mp4' : 'gps-photo.jpg';

        if ($isVideo) {
            return response()->file($absolute, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return response()->stream(function () use ($absolute) {
            $stream = fopen($absolute, 'rb');
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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
     * Same-origin map tile so the GPS camera can safely draw it into a video.
     */
    public function mapTile(int $z, int $x, int $y): Response
    {
        abort_unless($z === 17 && $x >= 0 && $x < 131072 && $y >= 0 && $y < 131072, 404);

        $token = trim((string) config('raniag.map.mapbox_token', ''));
        $mapStyle = $token !== '' ? 'satellite-streets-v12' : 'osm';
        $extension = $mapStyle === 'satellite-streets-v12' ? 'jpg' : 'png';
        $path = storage_path("app/map-tiles/{$mapStyle}/{$z}/{$x}/{$y}.{$extension}");
        if (is_readable($path)) {
            $cached = file_get_contents($path);
            if (is_string($cached) && $this->isSupportedMapTile($cached)) {
                return response($cached, 200, [
                    'Content-Type' => $this->mapTileContentType($cached),
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
        }

        try {
            $remote = $token !== ''
                ? Http::timeout(8)->get("https://api.mapbox.com/styles/v1/mapbox/satellite-streets-v12/tiles/256/{$z}/{$x}/{$y}@2x", [
                    'access_token' => $token,
                ])
                : Http::timeout(6)
                    ->withHeaders(['User-Agent' => 'RANIAG-MDRRMO-Pamplona/1.0 (gps camera map)'])
                    ->get("https://tile.openstreetmap.org/{$z}/{$x}/{$y}.png");
        } catch (\Throwable) {
            $remote = null;
        }

        if ((! $remote || ! $remote->ok() || ! $this->isSupportedMapTile($remote->body())) && $token !== '') {
            $mapStyle = 'osm';
            $path = storage_path("app/map-tiles/{$mapStyle}/{$z}/{$x}/{$y}.png");
            try {
                $remote = Http::timeout(6)
                    ->withHeaders(['User-Agent' => 'RANIAG-MDRRMO-Pamplona/1.0 (gps camera map)'])
                    ->get("https://tile.openstreetmap.org/{$z}/{$x}/{$y}.png");
            } catch (\Throwable) {
                $remote = null;
            }
        }

        abort_unless($remote && $remote->ok(), 404);
        $bytes = $remote->body();
        abort_unless($this->isSupportedMapTile($bytes), 404);

        try {
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            file_put_contents($path, $bytes);
        } catch (\Throwable) {
            // A failed disk write must still return the tile to the camera.
        }

        return response($bytes, 200, [
            'Content-Type' => $this->mapTileContentType($bytes),
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function isSupportedMapTile(string $bytes): bool
    {
        return str_starts_with($bytes, "\x89PNG") || str_starts_with($bytes, "\xFF\xD8\xFF");
    }

    private function mapTileContentType(string $bytes): string
    {
        return str_starts_with($bytes, "\xFF\xD8\xFF") ? 'image/jpeg' : 'image/png';
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
        $barangay = $addr['village'] ?? $addr['suburb'] ?? $addr['hamlet'] ?? $addr['neighbourhood'] ?? $addr['city_district'] ?? $addr['quarter'] ?? $addr['locality'] ?? null;
        $municipality = $addr['municipality'] ?? $addr['town'] ?? $addr['city'] ?? $addr['county'] ?? null;

        return [
            'barangay' => is_string($barangay) ? $barangay : null,
            'municipality' => is_string($municipality) ? $municipality : null,
            'province' => is_string($addr['state'] ?? null) ? $addr['state'] : (is_string($addr['province'] ?? null) ? $addr['province'] : (is_string($addr['state_district'] ?? null) ? $addr['state_district'] : null)),
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
        $openCenters = EvacuationCenter::query()
            ->where('is_open', true)
            ->get()
            ->map(function (EvacuationCenter $center) use ($lat, $lng) {
                return [
                    'id' => $center->id,
                    'name' => $center->name,
                    'latitude' => $center->latitude,
                    'longitude' => $center->longitude,
                    'barangay' => $center->barangay,
                    'distance_m' => round($this->haversine($lat, $lng, (float) $center->latitude, (float) $center->longitude)),
                ];
            })
            ->sortBy('distance_m')
            ->values();
        $best = $openCenters->first();

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
            'nearest_center' => $best,
            'open_centers' => $openCenters,
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

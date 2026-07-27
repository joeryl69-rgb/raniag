<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GeofenceService
{
    /**
     * Ray-casting point-in-polygon test for a single ring.
     *
     * $ring is a list of [lng, lat] pairs (GeoJSON coordinate order),
     * describing one closed boundary ring. Works for any simple polygon;
     * municipality boundaries are simple (non-self-intersecting) so this
     * is sufficient without needing PostGIS or a spatial DB extension.
     */
    public function pointInRing(float $lng, float $lat, array $ring): bool
    {
        $inside = false;
        $count = count($ring);

        if ($count < 3) {
            return false;
        }

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $lngI = $ring[$i][0];
            $latI = $ring[$i][1];
            $lngJ = $ring[$j][0];
            $latJ = $ring[$j][1];

            $intersects = ($latI > $lat) !== ($latJ > $lat)
                && $lng < ($lngJ - $lngI) * ($lat - $latI) / ($latJ - $latI) + $lngI;

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Test a point against a full GeoJSON geometry (Polygon or
     * MultiPolygon). For a Polygon, coordinates[0] is the outer ring and
     * any further rings are holes to subtract. A point counts as inside
     * only if it's inside the outer ring and not inside any hole.
     */
    public function pointInGeometry(float $lng, float $lat, array $geometry): bool
    {
        $type = $geometry['type'] ?? null;
        $coordinates = $geometry['coordinates'] ?? [];

        $polygons = $type === 'MultiPolygon' ? $coordinates : [$coordinates];

        foreach ($polygons as $rings) {
            if (empty($rings)) {
                continue;
            }

            $outer = $rings[0];
            if (! $this->pointInRing($lng, $lat, $outer)) {
                continue;
            }

            $inHole = false;
            foreach (array_slice($rings, 1) as $hole) {
                if ($this->pointInRing($lng, $lat, $hole)) {
                    $inHole = true;
                    break;
                }
            }

            if (! $inHole) {
                return true;
            }
        }

        return false;
    }

    /**
     * Public accessor for the loaded boundary geometry, e.g. so it can be
     * serialized to the frontend for a visual overlay. Returns null under
     * the same conditions as loadBoundary() (not configured / file missing).
     */
    public function boundaryGeometry(): ?array
    {
        return $this->loadBoundary();
    }

    /**
     * Load the configured Pamplona boundary GeoJSON file (cached after
     * first read since the file never changes at runtime).
     */
    protected function loadBoundary(): ?array
    {
        return Cache::rememberForever('raniag.pamplona_boundary_geojson', function () {
            $path = config('raniag.pamplona_boundary_path');

            if (! $path || ! Storage::disk('local')->exists($path)) {
                return null;
            }

            $decoded = json_decode(Storage::disk('local')->get($path), true);

            if (! is_array($decoded)) {
                return null;
            }

            // Accept either a bare geometry or a Feature/FeatureCollection wrapper.
            if (($decoded['type'] ?? null) === 'FeatureCollection') {
                return $decoded['features'][0]['geometry'] ?? null;
            }

            if (($decoded['type'] ?? null) === 'Feature') {
                return $decoded['geometry'] ?? null;
            }

            return $decoded;
        });
    }

    /**
     * Whether the given coordinates fall inside the configured Pamplona
     * municipality boundary. Returns null (unknown) if no coordinates
     * were given or no boundary file is configured/found yet, so callers
     * can distinguish "unknown" from "outside" instead of flagging every
     * report as out-of-jurisdiction before the boundary file is in place.
     */
    public function isWithinPamplona(?float $lat, ?float $lng): ?bool
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        $geometry = $this->loadBoundary();

        if (! $geometry) {
            return null;
        }

        return $this->pointInGeometry($lng, $lat, $geometry);
    }

    /**
     * Public accessor for the loaded barangay boundaries GeoJSON
     * FeatureCollection, so it can be serialized to the frontend and
     * tested client-side (map pin dragging, live GPS updates) without a
     * round trip for every move. Returns null under the same conditions
     * as loadBarangayBoundaries().
     */
    public function barangayBoundaries(): ?array
    {
        return $this->loadBarangayBoundaries();
    }

    /**
     * Load the configured Pamplona barangay boundaries GeoJSON
     * FeatureCollection (cached after first read since the file never
     * changes at runtime).
     */
    protected function loadBarangayBoundaries(): ?array
    {
        return Cache::rememberForever('raniag.pamplona_barangays_geojson', function () {
            $path = config('raniag.pamplona_barangays_path');

            if (! $path || ! Storage::disk('local')->exists($path)) {
                return null;
            }

            $decoded = json_decode(Storage::disk('local')->get($path), true);

            if (! is_array($decoded) || ($decoded['type'] ?? null) !== 'FeatureCollection') {
                return null;
            }

            return $decoded;
        });
    }

    /**
     * Resolve which Pamplona barangay contains the given coordinates
     * using real point-in-polygon geofencing (rather than guessing from
     * reverse-geocoded place-name text, which is unreliable for small
     * rural barangays that OpenStreetMap rarely tags). Returns the
     * barangay name (matching config('raniag.barangays')) or null if the
     * point falls outside every mapped barangay, or if no coordinates /
     * boundary file are available.
     */
    public function resolveBarangay(?float $lat, ?float $lng): ?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        $collection = $this->loadBarangayBoundaries();

        if (! $collection) {
            return null;
        }

        foreach ($collection['features'] ?? [] as $feature) {
            $geometry = $feature['geometry'] ?? null;
            $name = $feature['properties']['adm4_en'] ?? null;

            if (! $geometry || ! $name) {
                continue;
            }

            if ($this->pointInGeometry($lng, $lat, $geometry)) {
                return $name;
            }
        }

        return null;
    }
}

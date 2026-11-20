<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Services\GeofenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;

class GeofenceController extends Controller
{
    public function __construct(private readonly GeofenceService $geofence) {}

    public function index(): View
    {
        $geometry = $this->geofence->boundaryGeometry();
        abort_if($geometry === null, 503, 'Pamplona boundary data is unavailable.');

        $settings = SystemSetting::query()
            ->with('municipalityBoundaryUpdatedBy')
            ->find(1);
        $ring = $geometry['coordinates'][0] ?? [];

        return view('admin.geofence.index', [
            'boundaryGeometry' => $geometry,
            'isCustomBoundary' => $this->geofence->hasCustomBoundary(),
            'boundaryVertexCount' => max(0, count($ring) - 1),
            'boundaryUpdatedAt' => $settings?->municipality_boundary_updated_at,
            'boundaryUpdatedBy' => $settings?->municipalityBoundaryUpdatedBy,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'geometry' => ['required', 'string', 'max:150000'],
        ]);

        try {
            $decoded = json_decode($payload['geometry'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'geometry' => 'The boundary data is not valid GeoJSON. Please adjust it on the map and try again.',
            ]);
        }

        $geometry = Validator::make(['geometry' => $decoded], [
            'geometry' => ['required', 'array'],
            'geometry.type' => ['required', 'in:Polygon'],
            'geometry.coordinates' => ['required', 'array', 'size:1'],
            'geometry.coordinates.0' => ['required', 'array', 'min:4', 'max:2001'],
            'geometry.coordinates.0.*' => ['required', 'array', 'size:2'],
            'geometry.coordinates.0.*.0' => ['required', 'numeric', 'between:-180,180'],
            'geometry.coordinates.0.*.1' => ['required', 'numeric', 'between:-90,90'],
        ])->validate()['geometry'];

        $ring = $geometry['coordinates'][0];
        $first = [(float) $ring[0][0], (float) $ring[0][1]];
        $last = [(float) $ring[array_key_last($ring)][0], (float) $ring[array_key_last($ring)][1]];

        if ($first !== $last) {
            throw ValidationException::withMessages([
                'geometry' => 'The municipal boundary must be a closed polygon. Complete the outline on the map before saving.',
            ]);
        }

        $distinctVertices = [];
        foreach (array_slice($ring, 0, -1) as $position) {
            $distinctVertices[sprintf('%.8f,%.8f', (float) $position[0], (float) $position[1])] = true;
        }

        if (count($distinctVertices) < 3) {
            throw ValidationException::withMessages([
                'geometry' => 'The municipal boundary needs at least three distinct points.',
            ]);
        }

        if ($this->hasSelfIntersection($ring)) {
            throw ValidationException::withMessages([
                'geometry' => 'The boundary outline crosses itself. Adjust the points so the municipal area forms one simple polygon.',
            ]);
        }

        try {
            DB::transaction(function () use ($geometry, $request, $ring): void {
                $settings = SystemSetting::current();
                $settings->forceFill([
                    'municipality_boundary' => $geometry,
                    'municipality_boundary_updated_by' => $request->user()->id,
                    'municipality_boundary_updated_at' => now(),
                ])->save();

                $this->geofence->clearBoundaryCache();
                $this->geofence->refreshIncidentJurisdictions();

                ActivityLog::query()->create([
                    'user_id' => $request->user()->id,
                    'log_name' => 'municipality_boundary',
                    'description' => 'Updated the Pamplona municipal boundary.',
                    'subject_type' => SystemSetting::class,
                    'subject_id' => $settings->id,
                    'event' => 'municipality_boundary.updated',
                    'properties' => ['vertex_count' => count($ring) - 1],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            });
        } finally {
            $this->geofence->clearBoundaryCache();
        }

        return redirect()
            ->route('admin.geofence.index')
            ->with('success', 'Pamplona’s municipal boundary was updated across the system.');
    }

    public function reset(Request $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request): void {
                $settings = SystemSetting::current();
                $settings->forceFill([
                    'municipality_boundary' => null,
                    'municipality_boundary_updated_by' => $request->user()->id,
                    'municipality_boundary_updated_at' => now(),
                ])->save();

                $this->geofence->clearBoundaryCache();
                $this->geofence->refreshIncidentJurisdictions();

                ActivityLog::query()->create([
                    'user_id' => $request->user()->id,
                    'log_name' => 'municipality_boundary',
                    'description' => 'Restored the default Pamplona municipal boundary.',
                    'subject_type' => SystemSetting::class,
                    'subject_id' => $settings->id,
                    'event' => 'municipality_boundary.reset',
                    'properties' => ['source' => config('raniag.pamplona_boundary_path')],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            });
        } finally {
            $this->geofence->clearBoundaryCache();
        }

        return redirect()
            ->route('admin.geofence.index')
            ->with('success', 'The original Pamplona municipal boundary has been restored.');
    }

    private function hasSelfIntersection(array $ring): bool
    {
        $segmentCount = count($ring) - 1;
        $epsilon = 1.0e-12;

        for ($first = 0; $first < $segmentCount; $first++) {
            for ($second = $first + 1; $second < $segmentCount; $second++) {
                if ($second === $first + 1 || ($first === 0 && $second === $segmentCount - 1)) {
                    continue;
                }

                if ($this->segmentsIntersect(
                    $ring[$first],
                    $ring[$first + 1],
                    $ring[$second],
                    $ring[$second + 1],
                    $epsilon,
                )) {
                    return true;
                }
            }
        }

        return false;
    }

    private function segmentsIntersect(array $a, array $b, array $c, array $d, float $epsilon): bool
    {
        $first = $this->orientation($a, $b, $c);
        $second = $this->orientation($a, $b, $d);
        $third = $this->orientation($c, $d, $a);
        $fourth = $this->orientation($c, $d, $b);

        if ((($first > $epsilon && $second < -$epsilon) || ($first < -$epsilon && $second > $epsilon))
            && (($third > $epsilon && $fourth < -$epsilon) || ($third < -$epsilon && $fourth > $epsilon))) {
            return true;
        }

        return (abs($first) <= $epsilon && $this->pointOnSegment($c, $a, $b, $epsilon))
            || (abs($second) <= $epsilon && $this->pointOnSegment($d, $a, $b, $epsilon))
            || (abs($third) <= $epsilon && $this->pointOnSegment($a, $c, $d, $epsilon))
            || (abs($fourth) <= $epsilon && $this->pointOnSegment($b, $c, $d, $epsilon));
    }

    private function orientation(array $a, array $b, array $c): float
    {
        return ((float) $b[0] - (float) $a[0]) * ((float) $c[1] - (float) $a[1])
            - ((float) $b[1] - (float) $a[1]) * ((float) $c[0] - (float) $a[0]);
    }

    private function pointOnSegment(array $point, array $start, array $end, float $epsilon): bool
    {
        return (float) $point[0] >= min((float) $start[0], (float) $end[0]) - $epsilon
            && (float) $point[0] <= max((float) $start[0], (float) $end[0]) + $epsilon
            && (float) $point[1] >= min((float) $start[1], (float) $end[1]) - $epsilon
            && (float) $point[1] <= max((float) $start[1], (float) $end[1]) + $epsilon;
    }
}

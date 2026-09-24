<?php

namespace App\Services;

use App\Enums\IncidentStatus;
use App\Models\Assignment;
use App\Models\EvacuationCenter;
use App\Models\HazardZone;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds situational / risk / live-unit payloads for public and staff maps.
 *
 * Public callers never receive exact incident coordinates — only aggregated
 * barangay awareness counts. Staff and verified track sessions get richer data.
 */
class SituationalMapService
{
    public const OPEN_STATUSES = [
        IncidentStatus::Submitted,
        IncidentStatus::Received,
        IncidentStatus::Assigned,
        IncidentStatus::InProgress,
        IncidentStatus::PendingInfo,
    ];

    /** Stale GPS pings are hidden from live maps (minutes). */
    public const UNIT_STALE_MINUTES = 10;

    public function __construct(
        private readonly GeofenceService $geofence,
    ) {}

    /**
     * Active hazard zones for map layers.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function activeZonesPayload(): Collection
    {
        return HazardZone::query()->with('type')->where('is_active', true)->get()->map(function (HazardZone $zone) {
            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'geometry' => $zone->geometry,
                'color' => $zone->displayColor(),
                'advisory_note' => $zone->advisory_note,
                'type' => $zone->type ? ['name' => $zone->type->name, 'color' => $zone->type->color] : null,
            ];
        })->values();
    }

    /**
     * Open evacuation centers.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function openCentersPayload(): Collection
    {
        return EvacuationCenter::query()
            ->where('is_open', true)
            ->orderBy('name')
            ->get()
            ->map(fn (EvacuationCenter $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'latitude' => (float) $c->latitude,
                'longitude' => (float) $c->longitude,
                'capacity' => $c->capacity,
                'notes' => $c->notes,
                'is_open' => true,
            ])
            ->values();
    }

    /**
     * Public risk awareness — barangay open-incident counts only (no lat/lng).
     *
     * @return array{barangays: list<array{name: string, open_count: int}>, total_open: int, geometries: array|null}
     */
    public function publicRiskAwareness(): array
    {
        $openValues = array_map(fn (IncidentStatus $s) => $s->value, self::OPEN_STATUSES);

        $counts = Incident::query()
            ->selectRaw('barangay, COUNT(*) as open_count')
            ->whereIn('status', $openValues)
            ->whereNotNull('barangay')
            ->where('barangay', '!=', '')
            ->groupBy('barangay')
            ->pluck('open_count', 'barangay');

        $barangays = [];
        foreach (config('raniag.barangays', []) as $name) {
            $barangays[] = [
                'name' => $name,
                'open_count' => (int) ($counts[$name] ?? 0),
            ];
        }

        // Include any unexpected barangay labels that still appear in data.
        foreach ($counts as $name => $count) {
            if (! in_array($name, config('raniag.barangays', []), true)) {
                $barangays[] = [
                    'name' => (string) $name,
                    'open_count' => (int) $count,
                ];
            }
        }

        $geo = $this->geofence->barangayBoundaries();
        $geometries = null;
        if (is_array($geo) && ($geo['type'] ?? null) === 'FeatureCollection') {
            $countMap = collect($barangays)->mapWithKeys(fn ($b) => [$b['name'] => $b['open_count']]);
            $features = [];
            foreach ($geo['features'] ?? [] as $feature) {
                $name = $feature['properties']['adm4_en'] ?? null;
                if (! $name) {
                    continue;
                }
                $openCount = (int) ($countMap[$name] ?? 0);
                $props = $feature['properties'] ?? [];
                $props['name'] = $name;
                $props['open_count'] = $openCount;
                $features[] = [
                    'type' => 'Feature',
                    'properties' => $props,
                    'geometry' => $feature['geometry'],
                ];
            }
            $geometries = [
                'type' => 'FeatureCollection',
                'features' => $features,
            ];
        }

        return [
            'barangays' => $barangays,
            'total_open' => (int) array_sum(array_column($barangays, 'open_count')),
            'geometries' => $geometries,
        ];
    }

    /**
     * Staff open-incident pins for situational maps.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function openIncidentsPayload(): Collection
    {
        $openValues = array_map(fn (IncidentStatus $s) => $s->value, self::OPEN_STATUSES);

        return Incident::with(['incidentType', 'agency'])
            ->whereIn('status', $openValues)
            ->orderByDesc('reported_at')
            ->get()
            ->map(function (Incident $inc) {
                $hazardZones = $inc->meta['hazard_zones'] ?? [];

                return [
                    'id' => $inc->id,
                    'tracking_number' => $inc->tracking_number,
                    'incidentType' => $inc->incidentType,
                    'incident_type' => $inc->incidentType,
                    'priority' => $inc->priority instanceof \BackedEnum ? $inc->priority->value : $inc->priority,
                    'status' => $inc->status instanceof \BackedEnum ? $inc->status->value : (string) $inc->status,
                    'reported_at' => $inc->reported_at?->toDateTimeString(),
                    'barangay' => $inc->barangay,
                    'latitude' => $inc->latitude,
                    'longitude' => $inc->longitude,
                    'agency' => $inc->agency,
                    'hazard_zones' => is_array($hazardZones) ? $hazardZones : [],
                ];
            })
            ->values();
    }

    /**
     * Live units for an incident (staff or verified track session).
     *
     * @return list<array<string, mixed>>
     */
    public function liveUnitsForIncident(Incident $incident, bool $forPublic = false): array
    {
        $cutoff = now()->subMinutes(self::UNIT_STALE_MINUTES);

        $assignments = Assignment::query()
            ->with(['agency', 'assignee'])
            ->where('incident_id', $incident->id)
            ->where('is_active', true)
            ->get();

        $units = [];

        foreach ($assignments as $assignment) {
            $users = collect();

            if ($assignment->assigned_to) {
                if ($assignment->assignee) {
                    $users->push($assignment->assignee);
                }
            } elseif ($assignment->agency_id) {
                $users = User::query()
                    ->where('agency_id', $assignment->agency_id)
                    ->where('is_active', true)
                    ->whereNotNull('last_lat')
                    ->whereNotNull('last_lng')
                    ->where('last_location_at', '>=', $cutoff)
                    ->get();
            }

            foreach ($users as $user) {
                if ($user->last_lat === null || $user->last_lng === null) {
                    continue;
                }
                if (! $user->last_location_at instanceof Carbon || $user->last_location_at->lt($cutoff)) {
                    continue;
                }

                if ($forPublic) {
                    $label = $assignment->agency?->name ?: 'Responder';
                } else {
                    $label = $assignment->agency?->name
                        ?? $user->name
                        ?? 'Responder';
                    if ($assignment->assignee && $assignment->agency) {
                        $label = $assignment->agency->name.' — '.$assignment->assignee->name;
                    }
                }

                $units[] = [
                    'assignment_id' => $assignment->id,
                    'label' => $label,
                    'field_phase' => $assignment->field_phase,
                    'latitude' => (float) $user->last_lat,
                    'longitude' => (float) $user->last_lng,
                    'updated_at' => $user->last_location_at->toIso8601String(),
                ];
            }
        }

        // Deduplicate by approximate position + label
        $seen = [];
        $unique = [];
        foreach ($units as $unit) {
            $key = $unit['label'].'|'.round($unit['latitude'], 4).'|'.round($unit['longitude'], 4);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $unit;
        }

        return $unique;
    }

    /**
     * Full public snapshot (zones + centers + risk awareness).
     *
     * @return array<string, mixed>
     */
    public function publicSnapshot(): array
    {
        return [
            'zones' => $this->activeZonesPayload(),
            'centers' => $this->openCentersPayload(),
            'risk' => $this->publicRiskAwareness(),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Staff situational layers to merge into dashboard JSON.
     *
     * @return array<string, mixed>
     */
    public function staffSituationalLayers(): array
    {
        return [
            'hazard_zones' => $this->activeZonesPayload(),
            'evac_centers' => $this->openCentersPayload(),
            'map' => config('raniag.map'),
        ];
    }
}

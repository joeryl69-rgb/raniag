<?php

namespace App\Services;

use App\Enums\IncidentStatus;
use App\Models\Assignment;
use App\Models\EvacuationCenter;
use App\Models\Evidence;
use App\Models\HazardZone;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds situational / risk / live-unit payloads for public and staff maps.
 *
 * The public live map receives open-report pins inside Pamplona (location,
 * tracking number, type, status, and reporter GPS photo or video only).
 * Reporter names, contact details, and staff files stay off that payload.
 * Staff and verified track sessions get richer data.
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
                'barangay' => $zone->barangay,
                'advisory_note' => $zone->advisory_note,
                'advisory_url' => $zone->advisory_url,
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
                'barangay' => $c->barangay,
                'address' => $c->address,
                'capacity' => $c->capacity,
                'notes' => $c->notes,
                'color' => $c->color ?: '#0f766e',
                'is_open' => true,
            ])
            ->values();
    }

    /**
     * Barangay open-incident counts for the live-map summary. No coordinates.
     *
     * @return array{barangays: list<array{name: string, open_count: int}>, total_open: int}
     */
    public function publicRiskAwareness(): array
    {
        $openValues = array_map(fn (IncidentStatus $s) => $s->value, self::OPEN_STATUSES);

        $counts = $this->insidePamplona(Incident::query())
            ->selectRaw('barangay, COUNT(*) as open_count')
            ->whereIn('status', $openValues)
            ->groupBy('barangay')
            ->pluck('open_count', 'barangay');

        $barangays = [];
        foreach (config('raniag.barangays', []) as $name) {
            $barangays[] = [
                'name' => $name,
                'open_count' => (int) ($counts[$name] ?? 0),
            ];
        }

        return [
            'barangays' => $barangays,
            'total_open' => (int) array_sum(array_column($barangays, 'open_count')),
        ];
    }

    /**
     * Public pins for open reports inside Pamplona that included a location.
     * Limited to the tracking number, type, status, barangay, time, and
     * reporter-submitted GPS photo or video. No names or contact details.
     *
     * @return list<array<string, mixed>>
     */
    public function publicOpenReports(): array
    {
        $openValues = array_map(fn (IncidentStatus $s) => $s->value, self::OPEN_STATUSES);

        return $this->insidePamplona(Incident::query())
            ->with(['incidentType', 'evidence'])
            ->whereIn('status', $openValues)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByDesc('reported_at')
            ->get()
            ->map(function (Incident $incident) {
                $media = $incident->evidence
                    ->filter(fn (Evidence $item) => $this->publicGpsEvidenceIsVisible($item))
                    ->map(fn (Evidence $item) => [
                        'kind' => str_starts_with((string) $item->mime_type, 'video/') ? 'video' : 'image',
                        'url' => route('public.hazard.report-media', $item),
                    ])
                    ->values()
                    ->all();

                return [
                    'tracking_number' => $incident->tracking_number,
                    'type' => $incident->incidentType?->name,
                    'icon' => $incident->incidentType?->icon,
                    'color' => $incident->incidentType?->color,
                    'status' => $incident->status?->label() ?? '',
                    'barangay' => $incident->barangay,
                    'reported_at' => $incident->reported_at?->toIso8601String(),
                    'latitude' => (float) $incident->latitude,
                    'longitude' => (float) $incident->longitude,
                    'media' => $media,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * A public map may show a file only when the reporter captured it with
     * GPS on an open report inside Pamplona. Staff uploads stay private.
     */
    public function publicGpsEvidenceIsVisible(Evidence $evidence): bool
    {
        if (! $this->isReporterGpsCapture($evidence)) {
            return false;
        }

        $evidence->loadMissing('incident');
        $incident = $evidence->incident;
        if (! $incident || $incident->latitude === null || $incident->longitude === null) {
            return false;
        }

        $mime = (string) $evidence->mime_type;
        if (! str_starts_with($mime, 'image/') && ! str_starts_with($mime, 'video/')) {
            return false;
        }

        if (! in_array($incident->status, self::OPEN_STATUSES, true)) {
            return false;
        }

        return $this->insidePamplona(Incident::query()->whereKey($incident->id))->exists();
    }

    private function isReporterGpsCapture(Evidence $evidence): bool
    {
        return $evidence->uploaded_by === null && $evidence->is_gps_capture;
    }

    /**
     * Staff open-incident pins for situational maps.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function openIncidentsPayload(?int $agencyId = null, ?int $personnelId = null): Collection
    {
        $openValues = array_map(fn (IncidentStatus $s) => $s->value, self::OPEN_STATUSES);

        $query = Incident::with(['incidentType', 'agency'])
            ->whereIn('status', $openValues);

        if ($agencyId !== null || $personnelId !== null) {
            $query->whereHas('assignments', function ($assignment) use ($agencyId, $personnelId) {
                $assignment->where('is_active', true)
                    ->where(function ($owners) use ($agencyId, $personnelId) {
                        if ($agencyId !== null) {
                            $owners->where('agency_id', $agencyId);
                        }
                        if ($personnelId !== null) {
                            $method = $agencyId !== null ? 'orWhere' : 'where';
                            $owners->{$method}('assigned_to', $personnelId);
                        }
                    });
            });
        }

        return $query
            ->orderByDesc('reported_at')
            ->get()
            ->map(function (Incident $inc) {
                $hazardZones = $inc->meta['hazard_zones'] ?? [];
                $status = $inc->status instanceof \BackedEnum ? $inc->status->value : (string) $inc->status;
                $inside = $status !== IncidentStatus::OutsideAor->value
                    && ($inc->meta['within_jurisdiction'] ?? null) !== false
                    && \App\Support\PlaceScope::isOfficial($inc->barangay);

                return [
                    'id' => $inc->id,
                    'tracking_number' => $inc->tracking_number,
                    'incidentType' => $inc->incidentType,
                    'incident_type' => $inc->incidentType,
                    'priority' => $inc->priority instanceof \BackedEnum ? $inc->priority->value : $inc->priority,
                    'status' => $status,
                    'reported_at' => $inc->reported_at?->toDateTimeString(),
                    'barangay' => $inc->barangay,
                    'within_jurisdiction' => $inside,
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
        // Staff keep a responder on the dispatch map for the length of a
        // response. The public track page only shows a fresh ping.
        $cutoff = now()->subMinutes($forPublic ? self::UNIT_STALE_MINUTES : 360);

        $assignments = Assignment::query()
            ->with(['agency', 'assignee'])
            ->where('incident_id', $incident->id)
            ->where('is_active', true)
            ->get();

        $units = [];

        foreach ($assignments as $assignment) {
            // GPS lives on the user, not the case. Drawing every agency
            // member who has a last fix would show them en route on cases
            // they have not accepted or marked en route.
            $phase = (string) ($assignment->field_phase ?? '');
            if (! in_array($phase, ['en_route', 'on_scene'], true)) {
                continue;
            }

            $users = collect();

            // Agency dispatch used to look up GPS only when assigned_to was
            // empty. A case assigned to the agency account (or to one
            // personnel) still has to show whoever in that agency is
            // actually sharing location — usually the person who tapped
            // En route on their phone.
            if ($assignment->assignee) {
                $users->push($assignment->assignee);
            }
            if ($assignment->agency_id) {
                $users = $users->merge(
                    User::query()
                        ->where('agency_id', $assignment->agency_id)
                        ->where('is_active', true)
                        ->whereNotNull('last_lat')
                        ->whereNotNull('last_lng')
                        ->get()
                )->unique('id');
            }

            foreach ($users as $user) {
                if ($user->last_lat === null || $user->last_lng === null) {
                    continue;
                }
                $seenAt = $user->last_location_at;
                if (! $seenAt instanceof Carbon || $seenAt->lt($cutoff)) {
                    continue;
                }

                $agencyName = $assignment->agency?->name;
                $personName = $user->name ?: null;
                if ($forPublic) {
                    $label = $agencyName ?: 'Responder';
                } elseif ($agencyName && $personName) {
                    $label = $agencyName.' — '.$personName;
                } else {
                    $label = $agencyName ?: $personName ?: 'Responder';
                }

                $units[] = [
                    'id' => $assignment->id.'-'.$user->id,
                    'assignment_id' => $assignment->id,
                    'label' => $label,
                    'field_phase' => $assignment->field_phase ?: 'assigned',
                    'latitude' => (float) $user->last_lat,
                    'longitude' => (float) $user->last_lng,
                    'updated_at' => $seenAt->toIso8601String(),
                    'stale' => $seenAt->lt(now()->subMinutes(self::UNIT_STALE_MINUTES)),
                ];
            }
        }

        $seen = [];
        $unique = [];
        foreach ($units as $unit) {
            $key = $unit['id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $unit;
        }

        return $unique;
    }

    /**
     * Reports whose barangay is inside Pamplona and were not marked outside the AOR.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Incident>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Incident>
     */
    private function insidePamplona($query)
    {
        return $query
            ->whereIn('barangay', config('raniag.barangays', []))
            ->where(function ($inner) {
                $inner->whereNull('meta->within_jurisdiction')
                    ->orWhere('meta->within_jurisdiction', true);
            });
    }

    public function publicSnapshot(): array
    {
        return [
            'zones' => $this->activeZonesPayload(),
            'centers' => $this->openCentersPayload(),
            'risk' => $this->publicRiskAwareness(),
            'open_reports' => $this->publicOpenReports(),
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

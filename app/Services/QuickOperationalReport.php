<?php

namespace App\Services;

use App\Enums\IncidentStatus;
use App\Models\Assignment;
use App\Models\Evacuee;
use App\Models\Incident;
use App\Support\PeriodRange;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class QuickOperationalReport
{
    public function __construct(private readonly GeofenceService $geofence) {}

    /**
     * @return array<string, mixed>
     */
    public function build(string $period): array
    {
        $window = PeriodRange::resolve($period);
        $barangays = config('raniag.barangays', []);
        $query = Incident::query()
            ->with(['incidentType:id,name', 'assignments'])
            ->whereBetween('reported_at', [$window['from'], $window['to']])
            ->whereIn('barangay', $barangays)
            ->where('status', '!=', IncidentStatus::OutsideAor->value);
        $incidents = $query->orderByDesc('reported_at')->get();

        $openStatuses = array_map(fn (IncidentStatus $status) => $status->value, [
            IncidentStatus::Submitted,
            IncidentStatus::Received,
            IncidentStatus::Assigned,
            IncidentStatus::InProgress,
            IncidentStatus::PendingInfo,
        ]);
        $resolvedStatuses = [IncidentStatus::Resolved->value, IncidentStatus::Closed->value];
        $open = $incidents->filter(fn (Incident $incident) => in_array($this->enumValue($incident->status), $openStatuses, true))->count();
        $resolved = $incidents->filter(fn (Incident $incident) => in_array($this->enumValue($incident->status), $resolvedStatuses, true))->count();

        $typeCounts = $this->countRows($incidents, fn (Incident $incident) => $incident->incidentType?->name ?? 'Uncategorized');
        $barangayCounts = $this->countRows($incidents, fn (Incident $incident) => $incident->barangay ?: 'No barangay');
        $repeats = $incidents
            ->filter(fn (Incident $incident) => filled($incident->barangay))
            ->groupBy(fn (Incident $incident) => $incident->barangay.'|'.($incident->incidentType?->name ?? 'Uncategorized'))
            ->map(function (Collection $group, string $key) use ($window) {
                [$barangay, $type] = explode('|', $key, 2);
                $count = $group->count();
                $openCount = $group->filter(fn (Incident $incident) => in_array($this->enumValue($incident->status), [
                    IncidentStatus::Submitted->value,
                    IncidentStatus::Received->value,
                    IncidentStatus::Assigned->value,
                    IncidentStatus::InProgress->value,
                    IncidentStatus::PendingInfo->value,
                ], true))->count();
                $latest = $group->first();

                return [
                    'barangay' => $barangay,
                    'type' => $type,
                    'count' => $count,
                    'open' => $openCount,
                    'band' => PeriodRange::band($count),
                    'latest_at' => $latest?->reported_at?->format('M j, Y'),
                    'description' => $count.' '.$type.' report'.($count === 1 ? '' : 's').' were recorded in '.$barangay.' during '.$window['label'].'. '.($openCount > 0 ? $openCount.' remain open and may need follow-up.' : 'No cases in this group remain open.'),
                    'cases' => $group->take(4)->map(fn (Incident $incident) => [
                        'tracking' => (string) $incident->tracking_number,
                        'status' => $incident->status instanceof IncidentStatus ? $incident->status->label() : ucfirst(str_replace('_', ' ', (string) $incident->status)),
                        'reported_at' => $incident->reported_at?->format('M j, Y g:i a') ?? '—',
                        'description' => $this->clip((string) ($incident->description ?: $incident->location_address), 150),
                    ])->values()->all(),
                ];
            })
            ->filter(fn (array $repeat) => $repeat['count'] >= 2)
            ->sortByDesc('count')
            ->take(6)
            ->values();

        $assignmentMinutes = [];
        foreach ($incidents as $incident) {
            $firstAssignment = $incident->assignments
                ->filter(fn (Assignment $assignment) => $assignment->assigned_at)
                ->min('assigned_at');
            if ($incident->reported_at && $firstAssignment) {
                $assignmentMinutes[] = max(0, (int) $incident->reported_at->diffInMinutes(Carbon::parse($firstAssignment)));
            }
        }
        sort($assignmentMinutes);
        $medianMinutes = $this->median($assignmentMinutes);

        $stillSheltered = Evacuee::query()->whereNull('checked_out_at')->get();
        $outsideShelter = $stillSheltered->filter(fn (Evacuee $person) => $person->isOutsideMunicipality())->count();
        $insideShelter = $stillSheltered->count() - $outsideShelter;
        $map = $this->mapFeatures($barangayCounts);
        $hotspotCount = $repeats->pluck('barangay')->unique()->count();
        $topRepeat = $repeats->first();
        $reading = $incidents->count().' report'.($incidents->count() === 1 ? '' : 's').' recorded in '.$window['label'].'. ';
        $reading .= $topRepeat
            ? $topRepeat['barangay'].' has the strongest repeat signal: '.$topRepeat['count'].' '.$topRepeat['type'].' reports.'
            : 'No repeated barangay and incident-type combinations were recorded.';

        return [
            'period' => $window['key'],
            'period_label' => $window['label'],
            'period_caption' => $window['caption'],
            'from' => $window['from']->toDateString(),
            'to' => $window['to']->toDateString(),
            'generated_at' => now()->format('M j, Y g:i a'),
            'reading' => $reading,
            'metrics' => [
                'reports' => $incidents->count(),
                'open' => $open,
                'resolved' => $resolved,
                'hotspots' => $hotspotCount,
                'first_assignment' => $medianMinutes === null ? 'Not enough assigned cases' : $this->duration($medianMinutes),
                'shelter_total' => $stillSheltered->count(),
                'shelter_inside' => $insideShelter,
                'shelter_outside' => $outsideShelter,
            ],
            'type_counts' => $typeCounts->take(6)->values()->all(),
            'barangay_counts' => $barangayCounts->take(8)->values()->all(),
            'hotspots' => $repeats->all(),
            'map' => $map,
            'map_available' => $map !== [],
            'data_note' => 'Hotspots are repeated combinations of a Pamplona barangay and incident type (at least two reports) within this period. Map shading shows all in-period incidents by barangay.',
        ];
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum ? (string) $value->value : (string) $value;
    }

    private function clip(string $value, int $length): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return $value === '' ? 'No description recorded.' : mb_strimwidth($value, 0, $length, '…');
    }

    private function median(array $values): ?int
    {
        if ($values === []) {
            return null;
        }
        $middle = (int) floor((count($values) - 1) / 2);

        return count($values) % 2
            ? $values[$middle]
            : (int) round(($values[$middle] + $values[$middle + 1]) / 2);
    }

    private function duration(int $minutes): string
    {
        return $minutes < 60 ? $minutes.' min' : round($minutes / 60, 1).' hr';
    }

    /**
     * @param  Collection<int, Incident>  $incidents
     * @return Collection<int, array{label: string, count: int}>
     */
    private function countRows(Collection $incidents, callable $label): Collection
    {
        return $incidents->groupBy($label)
            ->map(fn (Collection $group, string $name) => ['label' => $name, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();
    }

    /**
     * @param  Collection<int, array{label: string, count: int}>  $counts
     * @return list<array{name: string, count: int, band: string, path: string, label_x: float, label_y: float}>
     */
    private function mapFeatures(Collection $counts): array
    {
        $geo = $this->geofence->barangayBoundaries();
        if (! is_array($geo) || ! is_array($geo['features'] ?? null)) {
            return [];
        }

        $countMap = $counts->mapWithKeys(fn (array $row) => [$row['label'] => $row['count']]);
        $features = [];
        foreach ($geo['features'] as $feature) {
            $name = $feature['properties']['adm4_en'] ?? null;
            $geometry = $feature['geometry'] ?? null;
            if (! is_string($name) || ! is_array($geometry)) {
                continue;
            }
            $features[] = [
                'name' => $name,
                'count' => (int) ($countMap[$name] ?? 0),
                'geometry' => $geometry,
            ];
        }
        if ($features === []) {
            return [];
        }

        $coordinates = [];
        foreach ($features as $feature) {
            foreach ($this->rings($feature['geometry']) as $ring) {
                foreach ($ring as $point) {
                    if (isset($point[0], $point[1]) && is_numeric($point[0]) && is_numeric($point[1])) {
                        $coordinates[] = [(float) $point[0], (float) $point[1]];
                    }
                }
            }
        }
        if ($coordinates === []) {
            return [];
        }
        $longitudes = array_column($coordinates, 0);
        $latitudes = array_column($coordinates, 1);
        $minLng = min($longitudes);
        $maxLng = max($longitudes);
        $minLat = min($latitudes);
        $maxLat = max($latitudes);
        $width = 760;
        $height = 520;
        $padding = 18;
        $lngRange = max(.00001, $maxLng - $minLng);
        $latRange = max(.00001, $maxLat - $minLat);
        $scale = min(($width - 2 * $padding) / $lngRange, ($height - 2 * $padding) / $latRange);
        $mapWidth = $lngRange * $scale;
        $mapHeight = $latRange * $scale;
        $offsetX = ($width - $mapWidth) / 2;
        $offsetY = ($height - $mapHeight) / 2;
        $result = [];

        foreach ($features as $feature) {
            $path = '';
            $labelPoints = [];
            foreach ($this->rings($feature['geometry']) as $ring) {
                foreach ($ring as $index => $point) {
                    if (! isset($point[0], $point[1]) || ! is_numeric($point[0]) || ! is_numeric($point[1])) {
                        continue;
                    }
                    $x = $offsetX + (((float) $point[0] - $minLng) * $scale);
                    $y = $height - ($offsetY + (((float) $point[1] - $minLat) * $scale));
                    $path .= ($index === 0 ? 'M' : 'L').round($x, 2).' '.round($y, 2).' ';
                    if ($index < count($ring) - 1) {
                        $labelPoints[] = [$x, $y];
                    }
                }
                $path .= 'Z ';
            }
            if ($path === '') {
                continue;
            }
            $labelX = array_sum(array_column($labelPoints, 0)) / max(1, count($labelPoints));
            $labelY = array_sum(array_column($labelPoints, 1)) / max(1, count($labelPoints));
            $result[] = [
                'name' => $feature['name'],
                'count' => $feature['count'],
                'band' => PeriodRange::band($feature['count']),
                'path' => trim($path),
                'label_x' => round($labelX, 2),
                'label_y' => round($labelY, 2),
            ];
        }

        return $result;
    }

    private function rings(array $geometry): array
    {
        $type = $geometry['type'] ?? null;
        $coordinates = $geometry['coordinates'] ?? [];

        return match ($type) {
            'Polygon' => $coordinates,
            'MultiPolygon' => array_merge(...array_map(fn ($polygon) => $polygon, $coordinates)),
            default => [],
        };
    }
}

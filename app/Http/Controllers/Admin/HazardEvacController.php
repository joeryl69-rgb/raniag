<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvacuationCenter;
use App\Models\Evacuee;
use App\Models\HazardZone;
use App\Models\HazardZoneType;
use App\Models\IncidentType;
use App\Support\IconLibrary;
use App\Support\PeriodRange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HazardEvacController extends Controller
{
    public function index(Request $request): View
    {
        $registryFilters = $request->validate([
            'stay' => ['nullable', Rule::in(['inside', 'left', 'all'])],
            'origin' => ['nullable', Rule::in(['inside', 'outside', 'all'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $registryFilters = [
            'stay' => $registryFilters['stay'] ?? 'inside',
            'origin' => $registryFilters['origin'] ?? 'all',
            'q' => trim($registryFilters['q'] ?? ''),
        ];

        $zones = Schema::hasTable('hazard_zones')
            ? HazardZone::query()->with('type')->latest()->get()
            : collect();

        $centers = Schema::hasTable('evacuation_centers')
            ? EvacuationCenter::query()
                ->withCount(['evacuees as checked_in_count' => function ($query) {
                    $query->whereNull('checked_out_at');
                }])
                ->orderBy('name')
                ->get()
            : collect();

        $incidentTypes = Schema::hasTable('incident_types')
            ? IncidentType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        $selectedCenterId = $request->integer('evacuation_center_id') ?: null;
        if ($selectedCenterId && ! $centers->contains('id', $selectedCenterId)) {
            $selectedCenterId = null;
        }

        $evacueeQuery = Schema::hasTable('evacuees')
            ? Evacuee::query()->with('center')->latest()
            : null;
        if ($evacueeQuery && $selectedCenterId) {
            $evacueeQuery->where('evacuation_center_id', $selectedCenterId);
        }
        $evacueeStatsQuery = $evacueeQuery ? clone $evacueeQuery : null;
        if ($evacueeQuery && $registryFilters['stay'] === 'inside') {
            $evacueeQuery->whereNull('checked_out_at');
        } elseif ($evacueeQuery && $registryFilters['stay'] === 'left') {
            $evacueeQuery->whereNotNull('checked_out_at');
        }
        if ($evacueeQuery && $registryFilters['origin'] === 'outside') {
            $evacueeQuery->where('origin_scope', 'outside');
        } elseif ($evacueeQuery && $registryFilters['origin'] === 'inside') {
            $evacueeQuery->where(function ($query) {
                $query->where('origin_scope', '!=', 'outside')->orWhereNull('origin_scope');
            });
        }
        if ($evacueeQuery && $registryFilters['q'] !== '') {
            $term = $registryFilters['q'];
            $evacueeQuery->where(function ($query) use ($term) {
                $query->where('full_name', 'like', '%'.$term.'%')
                    ->orWhere('barangay', 'like', '%'.$term.'%')
                    ->orWhere('origin_place', 'like', '%'.$term.'%')
                    ->orWhereHas('center', fn ($center) => $center->where('name', 'like', '%'.$term.'%'));
            });
        }
        $checkedInCount = $evacueeStatsQuery
            ? (clone $evacueeStatsQuery)->whereNull('checked_out_at')->count()
            : 0;
        $outsideCount = $evacueeStatsQuery
            ? (clone $evacueeStatsQuery)->whereNull('checked_out_at')->where('origin_scope', 'outside')->count()
            : 0;
        $checkedSince = function ($from, $until = null) use ($evacueeStatsQuery) {
            if (! $evacueeStatsQuery) {
                return 0;
            }

            $query = (clone $evacueeStatsQuery)->where('checked_in_at', '>=', $from);
            if ($until !== null) {
                $query->where('checked_in_at', '<', $until);
            }

            return $query->count();
        };
        $registryCheckedInCount = $evacueeQuery
            ? (clone $evacueeQuery)->whereNull('checked_out_at')->count()
            : 0;
        $evacuees = $evacueeQuery
            ? $evacueeQuery->paginate(15)->withQueryString()
            : new LengthAwarePaginator(collect(), 0, 15, 1, [
                'path' => route('admin.hazard.index'),
                'query' => $request->query(),
            ]);
        $checkInCenters = $centers->filter(fn (EvacuationCenter $center) => $center->is_open
            && ($center->capacity === null || $center->checked_in_count < $center->capacity));

        $monthStart = now()->startOfMonth();
        $previousStart = now()->copy()->subMonthNoOverflow()->startOfMonth();
        $countSince = function ($rows, $from, $until = null) {
            return $rows->filter(function ($row) use ($from, $until) {
                $at = $row->created_at;
                if (! $at || $at->lt($from)) {
                    return false;
                }

                return $until === null || $at->lt($until);
            })->count();
        };
        return view('admin.hazard.index', [
            'incidentTypes' => $incidentTypes,
            'zones' => $zones,
            'centers' => $centers,
            'evacuees' => $evacuees,
            'registryFilters' => $registryFilters,
            'checkedInCount' => $checkedInCount,
            'registryCheckedInCount' => $registryCheckedInCount,
            'checkInCenters' => $checkInCenters,
            'selectedCenterId' => $selectedCenterId,
            'map' => config('raniag.map'),
            'barangays' => config('raniag.barangays', []),
            'mapZonesJson' => $zones->map(function (HazardZone $z) {
                return [
                    'id' => $z->id,
                    'name' => $z->name,
                    'geometry' => $z->geometry,
                    'color' => $z->displayColor(),
                    'type' => $z->type?->name,
                    'active' => (bool) $z->is_active,
                ];
            })->values(),
            'mapCentersJson' => $centers->map(function (EvacuationCenter $c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'lat' => (float) $c->latitude,
                    'lng' => (float) $c->longitude,
                    'barangay' => $c->barangay,
                    'address' => $c->address,
                    'capacity' => $c->capacity,
                    'notes' => $c->notes,
                    'open' => (bool) $c->is_open,
                    'color' => $c->color ?: '#0f766e',
                ];
            })->values(),
            'publicHazardMapUrl' => route('public.hazard.map'),
            'cardTrends' => [
                'areas' => PeriodRange::change($countSince($zones, $monthStart), $countSince($zones, $previousStart, $monthStart)),
                'public' => [
                    'percent' => $zones->count() > 0 ? (int) round($zones->where('is_active', true)->count() / $zones->count() * 100) : 0,
                    'direction' => 'flat',
                ],
                'shelters' => [
                    'percent' => $centers->count() > 0 ? (int) round($centers->where('is_open', true)->count() / $centers->count() * 100) : 0,
                    'direction' => 'flat',
                ],
                'checked_in' => PeriodRange::change($checkedSince($monthStart), $checkedSince($previousStart, $monthStart)),
                'outside' => $outsideCount,
            ],
        ]);
    }

    public function storeZone(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'incident_type_id' => ['required', 'exists:incident_types,id'],
            'name' => ['required', 'string', 'max:120'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'geometry_json' => ['required', 'string', 'json', 'max:100000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $geometry = $this->closeHazardPolygon(json_decode($data['geometry_json'], true));
        if (! $this->isValidHazardPolygon($geometry)) {
            return back()->withErrors(['geometry_json' => 'Draw a valid closed hazard boundary on the map before saving.'])->withInput();
        }

        $incidentType = IncidentType::query()->findOrFail($data['incident_type_id']);
        $type = $this->zoneTypeForIncident($incidentType);
        $payload = [
            'hazard_zone_type_id' => $type->id,
            'name' => $data['name'],
            'barangay' => $data['barangay'] ?? null,
            'geometry' => $geometry,
            'is_active' => $request->boolean('is_active', true),
        ];

        if (Schema::hasColumn('hazard_zones', 'color')) {
            $payload['color'] = $type->color;
        }

        HazardZone::create($payload);

        return back()->with('success', 'Hazard area saved. It appears on the public Live Map.');
    }

    private function closeHazardPolygon(mixed $geometry): mixed
    {
        if (! is_array($geometry) || ($geometry['type'] ?? null) !== 'Polygon' || ! is_array($geometry['coordinates'] ?? null)) {
            return $geometry;
        }

        foreach ($geometry['coordinates'] as $index => $ring) {
            if (! is_array($ring) || $ring === []) {
                continue;
            }

            $first = $ring[0] ?? null;
            $last = $ring[array_key_last($ring)] ?? null;
            if (is_array($first) && $first !== $last) {
                $ring[] = $first;
                $geometry['coordinates'][$index] = $ring;
            }
        }

        return $geometry;
    }

    private function isValidHazardPolygon(mixed $geometry): bool
    {
        if (! is_array($geometry) || ($geometry['type'] ?? null) !== 'Polygon') {
            return false;
        }

        $rings = $geometry['coordinates'] ?? null;
        if (! is_array($rings) || ! array_is_list($rings) || $rings === []) {
            return false;
        }

        foreach ($rings as $ring) {
            if (! is_array($ring) || ! array_is_list($ring) || count($ring) < 4 || count($ring) > 1000) {
                return false;
            }

            $normalized = [];
            foreach ($ring as $coordinate) {
                if (! is_array($coordinate) || count($coordinate) !== 2) {
                    return false;
                }

                [$longitude, $latitude] = $coordinate;
                if ((! is_int($longitude) && ! is_float($longitude))
                    || (! is_int($latitude) && ! is_float($latitude))
                    || ! is_finite((float) $longitude)
                    || ! is_finite((float) $latitude)
                    || $longitude < -180 || $longitude > 180
                    || $latitude < -90 || $latitude > 90) {
                    return false;
                }

                $normalized[] = [(float) $longitude, (float) $latitude];
            }

            if ($normalized[0] !== $normalized[count($normalized) - 1]) {
                return false;
            }

            $area = 0.0;
            for ($index = 0, $last = count($normalized) - 1; $index < $last; $index++) {
                $area += ($normalized[$index][0] * $normalized[$index + 1][1])
                    - ($normalized[$index + 1][0] * $normalized[$index][1]);
            }
            if (abs($area) < 1.0e-12) {
                return false;
            }
        }

        return true;
    }

    public function updateZone(Request $request, HazardZone $zone): RedirectResponse
    {
        $request->merge([
            'advisory_url' => $request->filled('advisory_url') ? $request->input('advisory_url') : null,
            'advisory_note' => $request->filled('advisory_note') ? $request->input('advisory_note') : null,
        ]);

        $data = $request->validate([
            'advisory_note' => ['nullable', 'string', 'max:5000'],
            'advisory_url' => ['nullable', 'url', 'max:500'],
        ]);

        $zone->update($data);

        return back()->with('success', 'Advisory updated. Residents see it when they open this area on the Live Map.');
    }

    public function updateZoneDetails(Request $request, HazardZone $zone): RedirectResponse
    {
        $data = $request->validate([
            'incident_type_id' => ['required', 'exists:incident_types,id'],
            'name' => ['required', 'string', 'max:120'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'geometry_json' => ['nullable', 'string', 'json', 'max:100000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $incidentType = IncidentType::query()->findOrFail($data['incident_type_id']);
        $type = $this->zoneTypeForIncident($incidentType);
        $payload = [
            'hazard_zone_type_id' => $type->id,
            'name' => $data['name'],
            'barangay' => ($data['barangay'] ?? null) ?: null,
            'is_active' => $request->boolean('is_active'),
        ];

        if (Schema::hasColumn('hazard_zones', 'color')) {
            $payload['color'] = $type->color;
        }

        if ($request->filled('geometry_json')) {
            $geometry = $this->closeHazardPolygon(json_decode($data['geometry_json'], true));
            if (! $this->isValidHazardPolygon($geometry)) {
                return back()->withErrors([
                    'geometry_json' => 'Draw a valid closed hazard boundary on the map before replacing this area.',
                ])->withInput();
            }
            $payload['geometry'] = $geometry;
        }

        $zone->update($payload);

        return back()->with('success', 'Hazard area updated.');
    }

    public function destroyZone(HazardZone $zone): RedirectResponse
    {
        $zone->delete();

        return back()->with('success', 'Hazard zone removed.');
    }

    public function storeCenter(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_open' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        EvacuationCenter::create([
            ...$data,
            'is_open' => $request->boolean('is_open', true),
        ]);

        return back()->with('success', 'Evacuation center saved.');
    }

    public function updateCenter(Request $request, EvacuationCenter $center): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required_if:workspace,center', 'string', 'max:120'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required_if:workspace,center', 'numeric', 'between:-90,90'],
            'longitude' => ['required_if:workspace,center', 'numeric', 'between:-180,180'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        if ($request->input('workspace') === 'center') {
            $center->update([
                ...$data,
                'barangay' => ($data['barangay'] ?? null) ?: null,
                'address' => ($data['address'] ?? null) ?: null,
                'capacity' => ($data['capacity'] ?? null) ?: null,
                'notes' => ($data['notes'] ?? null) ?: null,
                'is_open' => $request->boolean('is_open'),
            ]);
        } else {
            $center->update(['color' => $data['color']]);
        }

        return back()->with('success', $request->input('workspace') === 'center'
            ? 'Shelter updated. Residents see the changes on the Live Map.'
            : 'Shelter pin color updated. Residents see it on the Live Map.');
    }

    public function destroyCenter(EvacuationCenter $center): RedirectResponse
    {
        $center->delete();

        return back()->with('success', 'Evacuation center removed.');
    }

    public function storeEvacuee(Request $request): RedirectResponse
    {
        $outside = $request->input('origin_scope') === 'outside';
        $data = $request->validate([
            'evacuation_center_id' => ['required', 'exists:evacuation_centers,id'],
            'full_name' => ['required', 'string', 'max:120'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'sex' => ['nullable', 'string', 'max:16'],
            'origin_scope' => ['required', 'in:inside,outside'],
            'origin_place' => [$outside ? 'required' : 'nullable', 'string', 'max:120'],
            'barangay' => array_values(array_filter([
                'nullable',
                'string',
                'max:100',
                $outside ? null : Rule::in(config('raniag.barangays', [])),
            ])),
            'is_vulnerable' => ['sometimes', 'boolean'],
            'vulnerability_notes' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $outside, $request) {
            $center = EvacuationCenter::query()
                ->lockForUpdate()
                ->findOrFail($data['evacuation_center_id']);

            if (! $center->is_open) {
                throw ValidationException::withMessages([
                    'evacuation_center_id' => 'This shelter is closed and cannot accept check-ins.',
                ]);
            }

            $checkedInCount = $center->evacuees()->whereNull('checked_out_at')->count();
            if ($center->capacity !== null && $checkedInCount >= $center->capacity) {
                throw ValidationException::withMessages([
                    'evacuation_center_id' => 'This shelter has reached its capacity. Select another open shelter with space.',
                ]);
            }

            Evacuee::create([
                ...$data,
                'origin_scope' => $outside ? 'outside' : 'inside',
                'origin_place' => $outside ? $data['origin_place'] : null,
                'is_vulnerable' => $request->boolean('is_vulnerable'),
                'checked_in_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.hazard.index', ['tab' => 'registry'])
            ->with('success', 'Evacuee checked in.');
    }

    public function checkOutEvacuee(Evacuee $evacuee): RedirectResponse
    {
        if (! $evacuee->checked_out_at) {
            $evacuee->update(['checked_out_at' => now()]);
        }

        return redirect()
            ->route('admin.hazard.index', ['tab' => 'registry'])
            ->with('success', 'Evacuee checked out.');
    }

    private function zoneTypeForIncident(IncidentType $incidentType): HazardZoneType
    {
        $slug = $incidentType->slug ?: Str::slug($incidentType->name);
        $color = IconLibrary::resolveColor($incidentType->color ?: $incidentType->default_color);

        $type = HazardZoneType::query()->firstOrNew(['slug' => $slug]);
        $type->name = $incidentType->name;
        $type->color = $color;
        $type->save();

        return $type;
    }
}

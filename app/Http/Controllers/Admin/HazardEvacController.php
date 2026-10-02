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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HazardEvacController extends Controller
{
    public function index(): View
    {
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

        $evacuees = Schema::hasTable('evacuees')
            ? Evacuee::query()->with('center')->latest()->limit(200)->get()
            : collect();

        $checkedIn = $evacuees->whereNull('checked_out_at');
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
        $checkedSince = function ($from, $until = null) use ($evacuees) {
            return $evacuees->filter(function ($row) use ($from, $until) {
                $at = $row->checked_in_at;
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
                'outside' => $checkedIn->where('origin_scope', 'outside')->count(),
            ],
        ]);
    }

    public function storeZone(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'incident_type_id' => ['required', 'exists:incident_types,id'],
            'name' => ['required', 'string', 'max:120'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'geometry_json' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $geometry = json_decode($data['geometry_json'], true);
        if (! is_array($geometry) || empty($geometry['type'])) {
            return back()->withErrors(['geometry_json' => 'Draw the hazard boundary on the map before saving.'])->withInput();
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
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $center->update(['color' => $data['color']]);

        return back()->with('success', 'Shelter pin color updated. Residents see it on the Live Map.');
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

        Evacuee::create([
            ...$data,
            'origin_scope' => $outside ? 'outside' : 'inside',
            'origin_place' => $outside ? $data['origin_place'] : null,
            'is_vulnerable' => $request->boolean('is_vulnerable'),
            'checked_in_at' => now(),
        ]);

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

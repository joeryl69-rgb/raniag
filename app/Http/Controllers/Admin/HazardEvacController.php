<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvacuationCenter;
use App\Models\Evacuee;
use App\Models\HazardZone;
use App\Models\HazardZoneType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HazardEvacController extends Controller
{
    public function index(): View
    {
        $zones = Schema::hasTable('hazard_zones')
            ? HazardZone::query()->with('type')->latest()->get()
            : collect();

        $centers = Schema::hasTable('evacuation_centers')
            ? EvacuationCenter::query()->orderBy('name')->get()
            : collect();

        $zoneTypes = Schema::hasTable('hazard_zone_types')
            ? HazardZoneType::query()->orderBy('name')->get()
            : collect();

        $evacuees = Schema::hasTable('evacuees')
            ? Evacuee::query()->with('center')->latest()->limit(100)->get()
            : collect();

        return view('admin.hazard.index', [
            'zoneTypes' => $zoneTypes,
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
                ];
            })->values(),
            'mapCentersJson' => $centers->map(function (EvacuationCenter $c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'lat' => (float) $c->latitude,
                    'lng' => (float) $c->longitude,
                ];
            })->values(),
            'publicHazardMapUrl' => route('public.hazard.map'),
        ]);
    }

    public function storeZone(Request $request): RedirectResponse
    {
        $rules = [
            'hazard_zone_type_id' => ['required', 'exists:hazard_zone_types,id'],
            'name' => ['required', 'string', 'max:120'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'geometry_json' => ['required', 'string'],
            'advisory_note' => ['nullable', 'string', 'max:5000'],
            'advisory_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if (Schema::hasColumn('hazard_zones', 'color')) {
            $rules['color'] = ['nullable', 'string', 'max:16', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        }

        $data = $request->validate($rules);

        $geometry = json_decode($data['geometry_json'], true);
        if (! is_array($geometry) || empty($geometry['type'])) {
            return back()->withErrors(['geometry_json' => 'Draw a polygon on the map before saving.'])->withInput();
        }

        $type = HazardZoneType::query()->find($data['hazard_zone_type_id']);
        $payload = [
            'hazard_zone_type_id' => $data['hazard_zone_type_id'],
            'name' => $data['name'],
            'barangay' => $data['barangay'] ?? null,
            'geometry' => $geometry,
            'advisory_note' => $data['advisory_note'] ?? null,
            'advisory_url' => $data['advisory_url'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        if (Schema::hasColumn('hazard_zones', 'color')) {
            $payload['color'] = $data['color'] ?? $type?->color;
        }

        HazardZone::create($payload);

        return back()->with('success', 'Hazard zone saved. It appears on the public Hazard Map.');
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
        ]);

        EvacuationCenter::create([
            ...$data,
            'is_open' => $request->boolean('is_open', true),
        ]);

        return back()->with('success', 'Evacuation center saved.');
    }

    public function destroyCenter(EvacuationCenter $center): RedirectResponse
    {
        $center->delete();

        return back()->with('success', 'Evacuation center removed.');
    }

    public function storeEvacuee(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'evacuation_center_id' => ['required', 'exists:evacuation_centers,id'],
            'full_name' => ['required', 'string', 'max:120'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'sex' => ['nullable', 'string', 'max:16'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'is_vulnerable' => ['sometimes', 'boolean'],
            'vulnerability_notes' => ['nullable', 'string', 'max:255'],
        ]);

        Evacuee::create([
            ...$data,
            'is_vulnerable' => $request->boolean('is_vulnerable'),
            'checked_in_at' => now(),
        ]);

        return back()->with('success', 'Evacuee registered.');
    }
}

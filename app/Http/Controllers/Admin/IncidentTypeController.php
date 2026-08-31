<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IncidentType;
use App\Support\IconLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IncidentTypeController extends Controller
{
    /**
     * Curated "quick pick" subset shown by default (matches App\Support\IconLibrary::DEFAULT_SET).
     * The picker also lets the admin search the full Bootstrap Icons catalog
     * (self-hosted in public/vendor/bootstrap-icons — no CDN, no external
     * site to browse), so the admin is never limited to the quick-pick list
     * without ever having to leave the app.
     */
    private const COLOR_PRESETS = [
        '#dc3545' => 'Red',
        '#b91c1c' => 'Dark Red',
        '#f59e0b' => 'Amber',
        '#0d6efd' => 'Blue',
        '#16a34a' => 'Green',
        '#198754' => 'Emerald',
        '#6f42c1' => 'Purple',
        '#fd7e14' => 'Orange',
        '#0e4a6b' => 'Navy',
        '#212529' => 'Charcoal',
        '#64748b' => 'Slate',
        '#adb5bd' => 'Gray',
    ];

    /**
     * Original icon + color for the incident types seeded out of the box
     * (see database/seeders/IncidentTypeSeeder.php), keyed by slug. Powers
     * the "Reset to Default" button in the edit modal so an admin who has
     * customized a built-in type's icon/color can revert it in one click.
     * Only applies to these known slugs — a custom, admin-created type has
     * no "original" to reset to, so the button is hidden for those.
     */
    public const DEFAULT_PRESETS = [
        'fire' => ['icon' => 'bi-fire', 'color' => '#dc3545'],
        'flood' => ['icon' => 'bi-water', 'color' => '#0d6efd'],
        'crime' => ['icon' => 'bi-shield-fill-exclamation', 'color' => '#212529'],
        'medical' => ['icon' => 'bi-heart-pulse-fill', 'color' => '#198754'],
        'traffic' => ['icon' => 'bi-car-front-fill', 'color' => '#fd7e14'],
        'disaster' => ['icon' => 'bi-exclamation-triangle-fill', 'color' => '#6f42c1'],
        'infrastructure' => ['icon' => 'bi-building-fill-exclamation', 'color' => '#64748b'],
        'other' => ['icon' => 'bi-question-circle-fill', 'color' => '#adb5bd'],
    ];

    public function index(): View
    {
        $types = IncidentType::withCount('incidents')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.incident_types.index', [
            'types' => $types,
            'iconChoices' => IconLibrary::DEFAULT_SET,
            'iconCatalog' => IconLibrary::CATALOG,
            'colorChoices' => self::COLOR_PRESETS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        // Capture this type's own icon/color as its "default" the moment
        // it's created, so the Reset to Default button always has a real
        // target — not just for the 8 hardcoded seeded types.
        $data['default_icon'] = $data['icon'];
        $data['default_color'] = $data['color'];

        IncidentType::create($data);

        return back()->with('success', "Incident type \"{$data['name']}\" created.");
    }

    public function update(Request $request, IncidentType $incidentType): RedirectResponse
    {
        $data = $this->validated($request, $incidentType->id);

        if ($data['name'] !== $incidentType->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $incidentType->id);
        }

        $incidentType->update($data);

        return back()->with('success', "Incident type \"{$incidentType->name}\" updated.");
    }

    public function toggle(IncidentType $incidentType): RedirectResponse
    {
        $incidentType->update(['is_active' => ! $incidentType->is_active]);

        $state = $incidentType->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "\"{$incidentType->name}\" {$state}.");
    }

    public function destroy(IncidentType $incidentType): RedirectResponse
    {
        if ($incidentType->incidents()->exists()) {
            return back()->with('error', "Can't delete \"{$incidentType->name}\" — it's already used by existing incident reports. Deactivate it instead to hide it from new reports.");
        }

        $incidentType->delete();

        return back()->with('success', "Incident type \"{$incidentType->name}\" deleted.");
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        // Icon accepts anything from the searchable Bootstrap Icons catalog, OR a
        // hand-typed "bi-*" class (the admin can search the full icon set — not
        // just the curated quick-pick grid). Color accepts any preset swatch OR a
        // custom hex value from the color-wheel input, so the admin isn't limited
        // to a fixed palette either.
        return $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:incident_types,name,'.($ignoreId ?? 'NULL').',id'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['required', 'string', 'max:64', 'regex:/^bi-[a-z0-9\-]+$/'],
            'color' => ['required', 'string', 'max:16', 'regex:/^#[0-9a-fA-F]{3,8}$/'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (
            IncidentType::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-".++$i;
        }

        return $slug;
    }
}

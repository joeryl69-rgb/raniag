<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PersonnelRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin-managed CRUD for personnel role titles (mirrors
 * IncidentTypeController's pattern). Lets an administrator decide
 * which role titles are offered when creating/editing a Personnel
 * account, instead of the list being hardcoded in
 * AgencyController/PersonnelController.
 */
class PersonnelRoleController extends Controller
{
    public function index(): View
    {
        $roles = PersonnelRole::orderBy('sort_order')->orderBy('title')->get();

        // role_title on users is a plain string (not a foreign key — see
        // migration note), so "how many personnel currently use this role"
        // is counted by matching title strings rather than a relation.
        $counts = \App\Models\User::where('role', \App\Enums\UserRole::Personnel)
            ->whereNotNull('role_title')
            ->selectRaw('role_title, count(*) as aggregate')
            ->groupBy('role_title')
            ->pluck('aggregate', 'role_title');

        $roles->each(function (PersonnelRole $role) use ($counts) {
            $role->personnel_count = $counts->get($role->title, 0);
        });

        return view('admin.personnel_roles.index', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        PersonnelRole::create($data);

        return back()->with('success', "Personnel role \"{$data['title']}\" added.");
    }

    public function update(Request $request, PersonnelRole $personnelRole): RedirectResponse
    {
        $data = $this->validated($request, $personnelRole->id);

        $oldTitle = $personnelRole->title;
        $personnelRole->update($data);

        // Keep existing personnel accounts pointed at the renamed title so
        // "Team Leader" -> "Lead Responder" doesn't orphan users who already
        // carry the old string in users.role_title.
        if ($oldTitle !== $data['title']) {
            \App\Models\User::where('role_title', $oldTitle)->update(['role_title' => $data['title']]);
        }

        return back()->with('success', "Personnel role \"{$personnelRole->title}\" updated.");
    }

    public function toggle(PersonnelRole $personnelRole): RedirectResponse
    {
        $personnelRole->update(['is_active' => ! $personnelRole->is_active]);

        $state = $personnelRole->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "\"{$personnelRole->title}\" {$state}. Deactivated roles stop appearing in the account-creation dropdown but existing accounts keep their title.");
    }

    public function destroy(PersonnelRole $personnelRole): RedirectResponse
    {
        $inUse = \App\Models\User::where('role_title', $personnelRole->title)->exists();

        if ($inUse) {
            return back()->with('error', "Can't delete \"{$personnelRole->title}\" — it's already assigned to existing personnel accounts. Deactivate it instead.");
        }

        $personnelRole->delete();

        return back()->with('success', "Personnel role \"{$personnelRole->title}\" deleted.");
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150', Rule::unique('personnel_roles', 'title')->ignore($ignoreId)],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }
}

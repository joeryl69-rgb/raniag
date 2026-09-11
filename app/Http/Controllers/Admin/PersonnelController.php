<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\PersonnelRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PersonnelController extends Controller
{
    public function edit(User $personnel)
    {
        if ($personnel->role !== UserRole::Personnel) {
            abort(404);
        }

        $roleTitles = PersonnelRole::activeTitles();

        // Keep the personnel's current title selectable even if an admin
        // has since deactivated or renamed that role — otherwise editing
        // this account would silently drop their existing title.
        if ($personnel->role_title && ! in_array($personnel->role_title, $roleTitles, true)) {
            array_unshift($roleTitles, $personnel->role_title);
        }

        return view('admin.personnel.edit', compact('personnel', 'roleTitles'));
    }

    public function update(Request $request, User $personnel)
    {
        if ($personnel->role !== UserRole::Personnel) {
            abort(404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($personnel->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'role_title' => ['required', Rule::in(array_unique(array_merge(PersonnelRole::activeTitles(), [$personnel->role_title])))],
            'team_assignment' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $personnel->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'role_title' => $data['role_title'],
            'team_assignment' => $data['team_assignment'],
            'is_active' => $request->has('is_active'),
            'password' => $data['password'] ? Hash::make($data['password']) : $personnel->password,
        ]);

        return redirect()
            ->route('admin.agencies.index')
            ->with('success', 'Personnel account updated successfully.');
    }

    public function destroy(User $personnel)
    {
        if ($personnel->role !== UserRole::Personnel) {
            abort(404);
        }

        $personnel->delete();

        return redirect()
            ->route('admin.agencies.index')
            ->with('success', 'Personnel account deleted successfully.');
    }
}

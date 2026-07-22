<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
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

        $roleTitles = [
            'Research and Planning Chief',
            'Operations and Warning Chief',
            'Admin and Training Chief',
            'PQRT Chief',
            'PQRT Deputy Chief',
            'Team Leader',
            'Responder',
        ];

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
            'role_title' => ['required', Rule::in([
                'Research and Planning Chief',
                'Operations and Warning Chief',
                'Admin and Training Chief',
                'PQRT Chief',
                'PQRT Deputy Chief',
                'Team Leader',
                'Responder',
            ])],
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
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AgencyController extends Controller
{
    public function index()
    {
        $agencies = Agency::with('users')->paginate(15);
        $personnelAccounts = User::where('role', UserRole::Personnel)->paginate(15);

        return view('admin.agencies.index', compact('agencies', 'personnelAccounts'));
    }

    public function create()
    {
        $roleTitles = [
            'Research and Planning Chief',
            'Operations and Warning Chief',
            'Admin and Training Chief',
            'PQRT Chief',
            'PQRT Deputy Chief',
            'Team Leader',
            'Responder',
        ];

        return view('admin.agencies.create', compact('roleTitles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_type' => ['required', Rule::in(['agency', 'personnel'])],
            'name' => ['required_if:account_type,agency', 'string', 'max:255'],
            'code' => ['required_if:account_type,agency', 'string', 'max:32', 'unique:agencies,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'role_title' => ['required_if:account_type,personnel', Rule::in([
                'Research and Planning Chief',
                'Operations and Warning Chief',
                'Admin and Training Chief',
                'PQRT Chief',
                'PQRT Deputy Chief',
                'Team Leader',
                'Responder',
            ])],
            'team_assignment' => ['required_if:account_type,personnel', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'officer_name' => ['required', 'string', 'max:255'],
            'officer_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'officer_password' => ['required', 'string', 'min:8'],
        ]);

        DB::transaction(function () use ($data) {
            if ($data['account_type'] === 'agency') {
                $agency = Agency::create([
                    'name' => $data['name'],
                    'code' => strtoupper($data['code']),
                    'description' => $data['description'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'address' => $data['address'],
                    'is_active' => true,
                ]);

                User::create([
                    'name' => $data['officer_name'],
                    'email' => $data['officer_email'],
                    'password' => Hash::make($data['officer_password']),
                    'role' => UserRole::Agency,
                    'agency_id' => $agency->id,
                    'phone' => $data['phone'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            } else {
                User::create([
                    'name' => $data['officer_name'],
                    'email' => $data['officer_email'],
                    'password' => Hash::make($data['officer_password']),
                    'role' => UserRole::Personnel,
                    'role_title' => $data['role_title'],
                    'team_assignment' => $data['team_assignment'],
                    'phone' => $data['phone'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            }
        });

        return redirect()
            ->route('admin.agencies.index')
            ->with('success', $data['account_type'] === 'agency'
                ? 'Agency and primary officer login created successfully.'
                : 'Personnel account created successfully.');
    }

    public function edit(Agency $agency)
    {
        $user = $agency->users()->where('role', UserRole::Agency)->first();

        return view('admin.agencies.edit', compact('agency', 'user'));
    }

    public function update(Request $request, Agency $agency)
    {
        $user = $agency->users()->where('role', UserRole::Agency)->first();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', Rule::unique('agencies', 'code')->ignore($agency->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'officer_name' => ['required', 'string', 'max:255'],
            'officer_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'officer_password' => ['nullable', 'string', 'min:8'],
        ]);

        $isActive = $request->has('is_active');

        DB::transaction(function () use ($agency, $user, $data, $isActive) {
            $agency->update([
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'description' => $data['description'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'],
                'is_active' => $isActive,
            ]);

            if ($user) {
                $userData = [
                    'name' => $data['officer_name'],
                    'email' => $data['officer_email'],
                    'phone' => $data['phone'],
                    'is_active' => $isActive,
                ];

                if (! empty($data['officer_password'])) {
                    $userData['password'] = Hash::make($data['officer_password']);
                }

                $user->update($userData);
            }
        });

        return redirect()
            ->route('admin.agencies.index')
            ->with('success', 'Agency and officer account updated successfully.');
    }
}

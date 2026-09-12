<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AgencySeeder extends Seeder
{
    /**
     * Optional sample agency account for local development.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'development', 'testing'])) {
            return;
        }

        $defaultAgencies = [
            ['name' => 'Philippine National Police', 'code' => 'PNP'],
            ['name' => 'Bureau of Fire Protection', 'code' => 'BFP'],
            ['name' => 'Barangay Health Workers', 'code' => 'BHW'],
        ];

        foreach ($defaultAgencies as $agencyData) {
            Agency::query()->firstOrCreate(
                ['code' => $agencyData['code']],
                [
                    'name' => $agencyData['name'],
                    'description' => 'Baseline agency record for local/test development.',
                    'email' => null,
                    'phone' => null,
                    'address' => null,
                    'is_active' => true,
                ]
            );
        }

        // Test users for existing agencies.
        $testAgencyCodes = ['PNP', 'BFP', 'BHW'];

        foreach ($testAgencyCodes as $code) {
            $agency = Agency::query()->where('code', $code)->first();
            if (! $agency) {
                continue;
            }

            User::query()->updateOrCreate(
                ['email' => 'agent_'.strtolower($code).'@raniag.local'],
                [
                    'name' => $agency->name.' Officer',
                    'password' => Hash::make('password'),
                    'role' => UserRole::Agency,
                    'agency_id' => $agency->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}

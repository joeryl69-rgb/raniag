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
        if (! app()->environment(['local', 'development'])) {
            return;
        }

        // Test users for existing agencies.
        // IMPORTANT: This seeder must NOT create new rows in `agencies`.
        // It only creates/updates users for agencies that already exist.
        // Skip MDRRMO entirely to avoid duplication.
        $testAgencyCodes = ['PNP', 'BFP', 'BHW'];

        foreach ($testAgencyCodes as $code) {
            $agency = Agency::query()->where('code', $code)->first();
            if (! $agency) {
                // If the agency row doesn't exist in `agencies`, skip it.
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

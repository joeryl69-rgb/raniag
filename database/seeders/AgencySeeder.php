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

        $agency = Agency::query()->updateOrCreate(
            ['code' => 'MDRRMO'],
            [
                'name' => 'Municipal Disaster Risk Reduction and Management Office',
                'description' => 'Sample agency for development testing.',
                'email' => 'mdrrmo@pamplona.gov.ph',
                'phone' => null,
                'is_active' => true,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => env('RANIAG_AGENCY_EMAIL', 'agency@raniag.pamplona.gov.ph')],
            [
                'name' => env('RANIAG_AGENCY_NAME', 'MDRRMO Officer'),
                'password' => Hash::make(env('RANIAG_AGENCY_PASSWORD', 'password')),
                'role' => UserRole::Agency,
                'agency_id' => $agency->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}

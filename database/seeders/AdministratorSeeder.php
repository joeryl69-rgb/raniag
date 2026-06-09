<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdministratorSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('RANIAG_ADMIN_EMAIL', 'admin@raniag.pamplona.gov.ph')],
            [
                'name' => env('RANIAG_ADMIN_NAME', 'RANIAG Administrator'),
                'password' => Hash::make(env('RANIAG_ADMIN_PASSWORD', 'password')),
                'role' => UserRole::Administrator,
                'agency_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}

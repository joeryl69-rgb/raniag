<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IncidentTypeSeeder::class,
            PersonnelRoleSeeder::class,
            AdministratorSeeder::class,
            // AgencySeeder intentionally not run — the only account that needs
            // to exist at install time is the Administrator. Agencies are
            // created dynamically by the admin from inside the app, so seeding
            // fixed/sample agency rows isn't needed and risked colliding with
            // whatever agencies were already created for real on the server.
        ]);
    }
}

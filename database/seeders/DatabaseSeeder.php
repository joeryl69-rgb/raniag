<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IncidentTypeSeeder::class,
            AdministratorSeeder::class,
            AgencySeeder::class,
        ]);
    }
}

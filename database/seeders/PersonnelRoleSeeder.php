<?php

namespace Database\Seeders;

use App\Models\PersonnelRole;
use Illuminate\Database\Seeder;

class PersonnelRoleSeeder extends Seeder
{
    /**
     * Seeds the roles that used to be hardcoded in
     * AgencyController/PersonnelController, so existing personnel
     * accounts keep matching a real row after the migration to a
     * dynamic, admin-managed list.
     */
    public function run(): void
    {
        $titles = [
            'Research and Planning Chief',
            'Operations and Warning Chief',
            'Admin and Training Chief',
            'PQRT Chief',
            'PQRT Deputy Chief',
            'Team Leader',
            'Responder',
        ];

        foreach ($titles as $i => $title) {
            PersonnelRole::firstOrCreate(
                ['title' => $title],
                ['is_active' => true, 'sort_order' => $i]
            );
        }
    }
}

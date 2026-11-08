<?php

namespace Database\Seeders;

use App\Models\EvacuationCenter;
use App\Models\Evacuee;
use Illuminate\Database\Seeder;

class DemoEvacueeSeeder extends Seeder
{
    private const MAX_PER_CENTER = 30;

    private const NAMES = [
        ['Ana Mae Santos', 24, 'Female'],
        ['Juan Miguel Dela Cruz', 31, 'Male'],
        ['Maria Teresa Reyes', 52, 'Female'],
        ['Mark Joseph Garcia', 18, 'Male'],
        ['Rosa Elena Mendoza', 67, 'Female'],
        ['Carlo Antonio Ramos', 42, 'Male'],
        ['Liza Mae Bautista', 29, 'Female'],
        ['Jose Manuel Flores', 74, 'Male'],
        ['Leah Marie Castro', 35, 'Female'],
        ['Ramon Luis Aquino', 46, 'Male'],
        ['Grace Ann Villanueva', 21, 'Female'],
        ['Noel Patrick Domingo', 58, 'Male'],
        ['Cecilia Mae Navarro', 63, 'Female'],
        ['Paolo Andre Soriano', 27, 'Male'],
        ['Maribel Cruz', 39, 'Female'],
        ['Renato Lim', 55, 'Male'],
        ['Aira Nicole Pascual', 16, 'Female'],
        ['Danilo Mercado', 70, 'Male'],
        ['Joan Marie Santiago', 33, 'Female'],
        ['Emmanuel Rivera', 48, 'Male'],
        ['Nerissa Valdez', 44, 'Female'],
        ['Arnel Cabacungan', 37, 'Male'],
        ['Rhea Ann Castillo', 25, 'Female'],
        ['Eduardo Panganiban', 61, 'Male'],
        ['Sheryl Mae Ocampo', 30, 'Female'],
        ['Benjamin Tolentino', 53, 'Male'],
        ['Katrina Joy Manalo', 19, 'Female'],
        ['Rogelio Santiago', 77, 'Male'],
        ['Melinda Ramos', 41, 'Female'],
        ['Patrick John Bautista', 23, 'Male'],
    ];

    private const OUTSIDE_PLACES = [
        ['Abulug', 'Poblacion'],
        ['Allacapan', 'Centro'],
        ['Ballesteros', 'Centro'],
        ['Claveria', 'Poblacion'],
        ['Sanchez-Mira', 'Poblacion'],
    ];

    public function run(): void
    {
        $barangays = array_values(config('raniag.barangays', []));

        foreach (EvacuationCenter::query()->where('is_open', true)->orderBy('id')->get() as $center) {
            $limit = min(self::MAX_PER_CENTER, $center->capacity ?? self::MAX_PER_CENTER);
            if ($limit < 1) {
                continue;
            }

            $fractions = [0.38, 0.46, 0.54, 0.63, 0.72, 0.81, 0.9];
            $fraction = $fractions[$center->id % count($fractions)];
            $target = min($limit, max(1, (int) floor($limit * $fraction)));

            $existingDemoCount = $center->evacuees()->where('is_demo', true)->count();
            $occupiedCount = $center->evacuees()->whereNull('checked_out_at')->count();
            $availableCapacity = $center->capacity === null
                ? self::MAX_PER_CENTER - $occupiedCount
                : $center->capacity - $occupiedCount;
            $toCreate = min(
                max(0, $target - $existingDemoCount),
                max(0, $availableCapacity),
                max(0, count(self::NAMES) - $existingDemoCount)
            );

            for ($index = $existingDemoCount; $index < $existingDemoCount + $toCreate; $index++) {
                [$name, $age, $sex] = self::NAMES[($center->id * 7 + $index) % count(self::NAMES)];
                $isOutside = $index % 4 === 3;
                $outsidePlace = self::OUTSIDE_PLACES[($center->id + $index) % count(self::OUTSIDE_PLACES)];
                $homeBarangay = $isOutside
                    ? $outsidePlace[1]
                    : ($barangays[($center->id + $index) % max(1, count($barangays))] ?? 'Centro');
                $checkedInAt = now()->subDays(($center->id + $index) % 12)->subHours($index % 8);

                Evacuee::query()->create([
                    'evacuation_center_id' => $center->id,
                    'full_name' => '[DEMO] '.$name,
                    'is_demo' => true,
                    'age' => $age,
                    'sex' => $sex,
                    'barangay' => $homeBarangay,
                    'origin_scope' => $isOutside ? 'outside' : 'inside',
                    'origin_place' => $isOutside ? $outsidePlace[0] : null,
                    'is_vulnerable' => $index % 7 === 0,
                    'vulnerability_notes' => $index % 7 === 0 ? 'Demo record — verify before operational use' : null,
                    'checked_in_at' => $checkedInAt,
                ]);
            }
        }
    }
}

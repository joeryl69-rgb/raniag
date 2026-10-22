<?php

namespace App\Support;

class PlaceScope
{
    public const OUTSIDE = 'outside';

    /**
     * @return list<string>
     */
    public static function officialBarangays(): array
    {
        return array_values(config('raniag.barangays', []));
    }

    public static function isOfficial(?string $barangay): bool
    {
        $barangay = trim((string) $barangay);

        return $barangay !== '' && in_array($barangay, self::officialBarangays(), true);
    }

    /**
     * @param  iterable<int, mixed>  $names
     * @return array{inside: list<string>, outside: list<string>}
     */
    public static function partition(iterable $names): array
    {
        $official = self::officialBarangays();
        $inside = [];
        $outside = [];

        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '' || $name === 'all' || $name === self::OUTSIDE) {
                continue;
            }
            if (in_array($name, $official, true)) {
                $inside[$name] = $name;
            } else {
                $outside[$name] = $name;
            }
        }

        $inside = array_values($inside);
        usort($inside, function (string $a, string $b) use ($official): int {
            return array_search($a, $official, true) <=> array_search($b, $official, true);
        });

        $outside = array_values($outside);
        sort($outside, SORT_NATURAL | SORT_FLAG_CASE);

        return ['inside' => $inside, 'outside' => $outside];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<*>|\Illuminate\Database\Query\Builder  $query
     */
    public static function applyBarangayFilter($query, ?string $barangay, string $column = 'barangay'): void
    {
        $barangay = trim((string) $barangay);
        if ($barangay === '' || $barangay === 'all') {
            return;
        }

        if ($barangay === self::OUTSIDE) {
            $official = self::officialBarangays();
            $query->where(function ($inner) use ($official, $column) {
                $inner->whereNull($column)
                    ->orWhere($column, '')
                    ->orWhereNotIn($column, $official);
            });

            return;
        }

        $query->where($column, $barangay);
    }
}

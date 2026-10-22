<?php

namespace App\Support;

use App\Enums\IncidentStatus;

class PlaceScope
{
    public const OUTSIDE = 'outside';

    public const INSIDE = 'inside';

    /**
     * @return list<string>
     */
    public static function officialBarangays(): array
    {
        return array_values(config('raniag.barangays', []));
    }

    public static function label(?string $scope): string
    {
        return match (trim((string) $scope)) {
            '', 'all' => 'All barangays',
            self::OUTSIDE => 'All outside Pamplona',
            self::INSIDE => 'All inside Pamplona',
            default => trim((string) $scope),
        };
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
            self::whereOutside($query, $column);

            return;
        }

        if ($barangay === self::INSIDE) {
            self::whereInside($query, $column);

            return;
        }

        $query->where($column, $barangay);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<*>|\Illuminate\Database\Query\Builder  $query
     */
    private static function whereOutside($query, string $column): void
    {
        $official = self::officialBarangays();
        $outsideStatus = IncidentStatus::OutsideAor->value;

        $query->where(function ($inner) use ($official, $column, $outsideStatus) {
            $inner->where('status', $outsideStatus)
                ->orWhere('meta->within_jurisdiction', false)
                ->orWhereNull($column)
                ->orWhere($column, '')
                ->orWhereNotIn($column, $official);
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<*>|\Illuminate\Database\Query\Builder  $query
     */
    private static function whereInside($query, string $column): void
    {
        $official = self::officialBarangays();
        $outsideStatus = IncidentStatus::OutsideAor->value;

        $query->whereIn($column, $official)
            ->where('status', '!=', $outsideStatus)
            ->where(function ($inner) {
                $inner->whereNull('meta->within_jurisdiction')
                    ->orWhere('meta->within_jurisdiction', true);
            });
    }
}

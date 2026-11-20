<?php

namespace App\Support;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Services\GeofenceService;

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
        $municipality = self::municipalityLabel();

        return match (trim((string) $scope)) {
            '', 'all' => 'All barangays',
            self::OUTSIDE => 'All outside '.$municipality,
            self::INSIDE => 'All inside '.$municipality,
            default => trim((string) $scope),
        };
    }

    public static function municipalityLabel(): string
    {
        return trim((string) config('raniag.address.municipality'))
            ?: (string) config('system_branding.municipality', 'municipality');
    }

    public static function isOfficial(?string $barangay): bool
    {
        $barangay = trim((string) $barangay);

        return $barangay !== '' && in_array($barangay, self::officialBarangays(), true);
    }

    public static function addressWithoutBarangayPrefix(?string $address, ?string $barangay, ?string $province = null): string
    {
        $province ??= config('raniag.address.province');
        $address = LocationAddressFormatter::normalize($address, $province);
        $parts = array_values(array_filter(array_map('trim', explode(',', trim((string) $address)))));
        $barangay = mb_strtolower(trim((string) $barangay));

        while ($barangay !== '' && $parts !== []) {
            $firstPart = preg_replace('/^(?:barangay|brgy\.?)\s+/i', '', $parts[0]);
            if (mb_strtolower(trim((string) $firstPart)) !== $barangay) {
                break;
            }
            array_shift($parts);
        }

        return implode(', ', $parts);
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
            self::whereJurisdiction($query, false, $column);

            return;
        }

        if ($barangay === self::INSIDE) {
            self::whereJurisdiction($query, true, $column);

            return;
        }

        $query->where($column, $barangay);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<*>|\Illuminate\Database\Query\Builder  $query
     */
    public static function currentJurisdiction(Incident $incident): ?bool
    {
        $stored = $incident->meta['within_jurisdiction'] ?? null;
        if (is_bool($stored)) {
            return $stored;
        }

        if (is_numeric($incident->latitude) && is_numeric($incident->longitude)) {
            $current = app(GeofenceService::class)->isWithinPamplona(
                (float) $incident->latitude,
                (float) $incident->longitude,
            );
            if ($current !== null) {
                return $current;
            }
        }

        if (self::isOfficial($incident->barangay)) {
            return true;
        }

        $status = $incident->status instanceof \BackedEnum
            ? $incident->status->value
            : (string) $incident->status;

        if ($status === IncidentStatus::OutsideAor->value || trim((string) $incident->barangay) !== '') {
            return false;
        }

        return null;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<*>|\Illuminate\Database\Query\Builder  $query
     */
    private static function whereJurisdiction($query, bool $inside, string $column): void
    {
        $model = $query->getModel();
        $latitudeColumn = $model->qualifyColumn('latitude');
        $longitudeColumn = $model->qualifyColumn('longitude');
        $barangayColumn = $model->qualifyColumn($column);

        if (app(GeofenceService::class)->boundaryGeometry() === null) {
            self::whereStoredJurisdiction($query, $inside, $barangayColumn);

            return;
        }

        $query->where(function ($scope) use ($inside, $latitudeColumn, $longitudeColumn, $barangayColumn) {
            $scope->where(function ($located) use ($inside, $latitudeColumn, $longitudeColumn) {
                $located->whereNotNull($latitudeColumn)
                    ->whereNotNull($longitudeColumn)
                    ->where('meta->within_jurisdiction', $inside);
            })->orWhere(function ($withoutCoordinates) use ($inside, $latitudeColumn, $longitudeColumn, $barangayColumn) {
                $withoutCoordinates->where(function ($unclassified) use ($latitudeColumn, $longitudeColumn) {
                    $unclassified->whereNull($latitudeColumn)
                        ->orWhereNull($longitudeColumn)
                        ->orWhereNull('meta->within_jurisdiction');
                });
                self::whereStoredJurisdiction($withoutCoordinates, $inside, $barangayColumn);
            });
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<*>|\Illuminate\Database\Query\Builder  $query
     */
    private static function whereStoredJurisdiction($query, bool $inside, string $column): void
    {
        $official = self::officialBarangays();
        $outsideStatus = IncidentStatus::OutsideAor->value;

        if ($inside) {
            $query->whereIn($column, $official)
                ->where('status', '!=', $outsideStatus)
                ->where(function ($inner) {
                    $inner->whereNull('meta->within_jurisdiction')
                        ->orWhere('meta->within_jurisdiction', true);
                });

            return;
        }

        $query->where(function ($inner) use ($official, $column, $outsideStatus) {
            $inner->where('status', $outsideStatus)
                ->orWhere('meta->within_jurisdiction', false)
                ->orWhere(function ($namedPlace) use ($official, $column) {
                    $namedPlace->whereNotNull($column)
                        ->where($column, '<>', '')
                        ->whereNotIn($column, $official);
                });
        });
    }
}

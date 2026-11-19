<?php

namespace App\Support;

class LocationAddressFormatter
{
    public static function normalize(?string $address, ?string $province = null): ?string
    {
        $parts = array_values(array_filter(
            array_map('trim', explode(',', trim((string) $address))),
            static fn (string $part): bool => $part !== ''
        ));
        $seen = [];
        $parts = array_values(array_filter($parts, static function (string $part) use (&$seen): bool {
            $key = mb_strtolower($part);
            if (isset($seen[$key])) {
                return false;
            }

            $seen[$key] = true;

            return true;
        }));

        $provinceKey = mb_strtolower(trim((string) $province));
        if ($provinceKey !== '') {
            $provinceIsIncludedInRegion = collect($parts)->contains(
                static fn (string $part): bool => mb_strtolower($part) !== $provinceKey
                    && str_contains(mb_strtolower($part), $provinceKey)
            );

            if ($provinceIsIncludedInRegion) {
                $parts = array_values(array_filter(
                    $parts,
                    static fn (string $part): bool => mb_strtolower($part) !== $provinceKey
                ));
            }
        }

        return $parts === [] ? null : implode(', ', $parts);
    }
}

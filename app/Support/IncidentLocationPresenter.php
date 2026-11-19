<?php

namespace App\Support;

use App\Models\Incident;

final class IncidentLocationPresenter
{
    /**
     * @return array{primary: string, secondary: ?string}
     */
    public static function present(Incident $incident): array
    {
        $barangay = trim((string) $incident->barangay);
        $address = trim((string) $incident->location_address);

        if ($barangay !== '') {
            $addressDetail = PlaceScope::addressWithoutBarangayPrefix($address, $barangay);

            return [
                'primary' => $barangay,
                'secondary' => $addressDetail !== '' ? $addressDetail : null,
            ];
        }

        if ($address !== '') {
            return ['primary' => $address, 'secondary' => null];
        }

        if (is_numeric($incident->latitude) && is_numeric($incident->longitude)) {
            return [
                'primary' => 'Pinned location',
                'secondary' => number_format((float) $incident->latitude, 5, '.', '')
                    .', '.number_format((float) $incident->longitude, 5, '.', ''),
            ];
        }

        return ['primary' => 'Location not provided', 'secondary' => null];
    }
}

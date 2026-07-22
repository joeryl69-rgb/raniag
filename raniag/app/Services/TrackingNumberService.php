<?php

namespace App\Services;

use App\Models\Incident;
use Illuminate\Support\Str;

class TrackingNumberService
{
    public function generate(): string
    {
        $prefix = strtoupper(config('raniag.tracking.prefix', 'RAN'));
        do {
            $trackingNumber = $prefix.'-'.strtoupper(Str::random(6));
        } while (Incident::query()->where('tracking_number', $trackingNumber)->exists());

        return $trackingNumber;
    }
}

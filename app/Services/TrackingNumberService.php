<?php

namespace App\Services;

use App\Models\Incident;
use Illuminate\Support\Str;

class TrackingNumberService
{
    public function generate(): string
    {
        $prefix = config('raniag.tracking.prefix', 'RAN');
        $segmentLength = (int) config('raniag.tracking.segment_length', 4);

        do {
            $dateSegment = now()->format('Ymd');
            $randomSegment = strtoupper(Str::random($segmentLength));
            $trackingNumber = sprintf('%s-%s-%s', $prefix, $dateSegment, $randomSegment);
        } while (Incident::query()->where('tracking_number', $trackingNumber)->exists());

        return $trackingNumber;
    }
}

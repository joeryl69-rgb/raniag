<?php

return [

    /*
    |--------------------------------------------------------------------------
    | RANIAG — LGU Pamplona Incident Reporting
    |--------------------------------------------------------------------------
    */

    'name' => env('RANIAG_NAME', 'RANIAG'),

    'organization' => env('RANIAG_ORGANIZATION', 'LGU Pamplona'),

    'tracking' => [
        'prefix' => env('RANIAG_TRACKING_PREFIX', 'RAN'),
        'segment_length' => 4,
    ],

    'roles' => [
        'administrator' => 'administrator',
        'agency' => 'agency',
    ],

    'map' => [
        'default_lat' => (float) env('RANIAG_MAP_LAT', 18.4720),
        'default_lng' => (float) env('RANIAG_MAP_LNG', 121.3250),
        'default_zoom' => (int) env('RANIAG_MAP_ZOOM', 13),
    ],

    'evidence' => [
        'max_files' => (int) env('RANIAG_EVIDENCE_MAX_FILES', 5),
        'max_size_kb' => (int) env('RANIAG_EVIDENCE_MAX_SIZE_KB', 5120),
        'allowed_mimes' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'mp4', 'mov', 'webm'],
    ],

    'geolocation' => [
        'enable_high_accuracy' => true,
        'timeout_ms' => (int) env('RANIAG_GEO_TIMEOUT_MS', 15000),
        'maximum_age_ms' => (int) env('RANIAG_GEO_MAX_AGE_MS', 0),
    ],

    'gps_camera' => [
        'jpeg_quality' => 0.88,
        'max_captures' => (int) env('RANIAG_GPS_MAX_CAPTURES', 5),
    ],

    'barangays' => [
        'Abanqueruan',
        'Allasitan',
        'Bagu',
        'Balingit',
        'Bidduang',
        'Cabaggan',
        'Capalalian',
        'Casitan',
        'Curva',
        'Dodan',
        'Gang-ngo',
        'Santa Cruz',
        'San Juan',
        'San Leonardo',
        'Santa Filomena',
        'Tangatan',
        'Tupang',
    ],

];

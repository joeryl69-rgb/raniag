<?php

return [

    /*
    |--------------------------------------------------------------------------
    | RANIAG — LGU Pamplona Incident Reporting
    |--------------------------------------------------------------------------
    */

    'name' => env('RANIAG_NAME', 'RANIAG'),

    'organization' => env('RANIAG_ORGANIZATION', 'MDRRMO Pamplona'),

    'tagline' => env('RANIAG_TAGLINE', 'Your safety is our priority'),

    'tracking' => [
        'prefix' => env('RANIAG_TRACKING_PREFIX', 'RAN'),
        'segment_length' => 4,
    ],

    'roles' => [
        'administrator' => 'administrator',
        'agency' => 'agency',
        'personnel' => 'personnel',
    ],

    'map' => [
        'default_lat' => (float) env('RANIAG_MAP_LAT', 18.4720),
        'default_lng' => (float) env('RANIAG_MAP_LNG', 121.3250),
        'default_zoom' => (int) env('RANIAG_MAP_ZOOM', 13),
    ],

    // Fallback address components used to complete the full address
    // (barangay, municipality, province, country) shown in the GPS
    // camera overlay and burned into the photo watermark. Reverse
    // geocoding (Nominatim) fills these in dynamically when available;
    // these are only the defaults used if that lookup fails or omits a
    // field, so the address is never left incomplete.
    'address' => [
        'municipality' => env('RANIAG_MUNICIPALITY', 'Pamplona'),
        'province' => env('RANIAG_PROVINCE', 'Cagayan'),
        'country' => env('RANIAG_COUNTRY', 'Philippines'),
    ],

    // Path (relative to storage/app) to a GeoJSON file containing the
    // official Pamplona municipality boundary (a Polygon/MultiPolygon,
    // or a Feature/FeatureCollection wrapping one). Used by
    // App\Services\GeofenceService to flag reports submitted outside
    // municipality limits. Get the authoritative boundary from your
    // LGU's GIS/planning office, PhilGIS, or OpenStreetMap/Nominatim
    // ("Pamplona, Cagayan, Philippines" -> export as GeoJSON) — do not
    // ship a hand-drawn approximation as if it were official.
    'pamplona_boundary_path' => env('RANIAG_PAMPLONA_BOUNDARY_PATH', 'geo/pamplona_boundary.geojson'),

    // Path (relative to storage/app/private) to a GeoJSON FeatureCollection
    // of the 18 Pamplona barangay boundaries (PSGC 0201518000, 2023
    // release, sourced from faeldon/philippines-json-maps which itself
    // derives from altcoder/philippines-psgc-shapefiles). Each feature's
    // adm4_en property must exactly match an entry in 'barangays' below.
    // Used by GeofenceService::resolveBarangay() for real point-in-polygon
    // barangay detection instead of guessing from reverse-geocoded text.
    'pamplona_barangays_path' => env('RANIAG_PAMPLONA_BARANGAYS_PATH', 'geo/pamplona_barangays.geojson'),

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

    // Official PSGC 0201518000 barangay list for Pamplona, Cagayan (18
    // barangays). Must exactly match the adm4_en values in the boundary
    // file referenced by 'pamplona_barangays_path' above, since both the
    // geofence resolver and the StoreIncidentReportRequest validation
    // rule (Rule::in) depend on these names lining up.
    'barangays' => [
        'Abanqueruan',
        'Allasitan',
        'Bagu',
        'Balingit',
        'Bidduang',
        'Cabaggan',
        'Capalalian',
        'Casitan',
        'Centro',
        'Curva',
        'Gattu',
        'Masi',
        'Nagattatan',
        'Nagtupacan',
        'San Juan',
        'Santa Cruz',
        'Tabba',
        'Tupanna',
    ],

];

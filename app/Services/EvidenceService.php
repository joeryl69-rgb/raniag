<?php

namespace App\Services;

use App\Enums\EvidenceType;
use App\Models\Evidence;
use App\Models\Incident;
use App\Support\PrivateIncidentFiles;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EvidenceService
{
    /**
     * Attach files to an incident, checking for GPS metadata (either from the Web app capture or EXIF data)
     * and watermarking geotagged photos using PHP GD. Watermarks use the
     * report's supplied address or local Pamplona address data so uploads
     * never wait on an external reverse-geocoding request.
     *
     * @param  list<UploadedFile>  $files
     * @param  list<UploadedFile>  $files
     * @param  array  $gpsCapturesMetadata  List of web-captured GPS images metadata
     * @param  int|null  $uploadedBy  The user ID who uploaded the file
     */
    public function attachToIncident(Incident $incident, array $files, array $gpsCapturesMetadata = [], ?int $uploadedBy = null): void
    {
        // Build a lookup array for GPS captures by original filename
        $gpsLookup = [];
        foreach ($gpsCapturesMetadata as $metaItem) {
            if (! empty($metaItem['filename'])) {
                $gpsLookup[$metaItem['filename']] = $metaItem;
            }
        }

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $isGpsCapture = false;
            $latitude = null;
            $longitude = null;
            $locationSource = 'gps';
            $timestamp = now()->toDateTimeString();

            // 1. Check if it matches a web camera capture
            if (isset($gpsLookup[$originalName])) {
                $isGpsCapture = true;
                $latitude = (float) ($gpsLookup[$originalName]['latitude'] ?? null);
                $longitude = (float) ($gpsLookup[$originalName]['longitude'] ?? null);
                $locationSource = $this->normalizeLocationSource($gpsLookup[$originalName]['location_source'] ?? null);
                if (! empty($gpsLookup[$originalName]['captured_at'])) {
                    $timestamp = date('Y-m-d H:i:s', strtotime($gpsLookup[$originalName]['captured_at']));
                }
            }

            $directory = sprintf('incidents/%d/evidence', $incident->id);
            $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs($directory, $filename, PrivateIncidentFiles::DISK);
            $absolutePath = Storage::disk(PrivateIncidentFiles::DISK)->path($path);

            // 2. If it is NOT a web capture, check if it has EXIF geotags
            $fileType = $this->resolveType($file);
            if (! $isGpsCapture && $fileType === EvidenceType::Photo) {
                $exifCoords = $this->getExifGpsCoordinates($absolutePath);
                if ($exifCoords) {
                    $isGpsCapture = true;
                    $latitude = $exifCoords['latitude'];
                    $longitude = $exifCoords['longitude'];
                    $locationSource = 'exif';
                }
            }

            // 3. Watermark the photo if it has coordinates
            if ($isGpsCapture && $latitude !== null && $longitude !== null && $fileType === EvidenceType::Photo) {
                $place = $this->watermarkPhoto(
                    $absolutePath,
                    $latitude,
                    $longitude,
                    $incident->barangay,
                    $timestamp,
                    $incident->location_address,
                    $locationSource,
                );

                // Dynamically assign incident coordinates if not already set
                if (empty($incident->latitude) || empty($incident->longitude)) {
                    $incident->update([
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ]);
                }

                // Persist resolved place/address when available and not already set
                if (! empty($place) && (empty($incident->location_address) || $incident->location_address === null)) {
                    $incident->update(['location_address' => $place]);
                }
            }

            Evidence::query()->create([
                'incident_id' => $incident->id,
                'uploaded_by' => $uploadedBy,
                'type' => $fileType,
                'file_path' => $path,
                'original_filename' => $originalName,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'priority' => $isGpsCapture ? 1 : 0,
                'is_gps_capture' => $isGpsCapture,
            ]);
        }
    }

    /**
     * Fallback method for direct uploads or programmatically created GPS captures.
     */
    public function attachGpsCaptures(Incident $incident, array $gpsCaptures, ?int $uploadedBy = null): void
    {
        foreach ($gpsCaptures as $capture) {
            if (empty($capture['data']) || empty($capture['filename'])) {
                continue;
            }

            $directory = sprintf('incidents/%d/evidence', $incident->id);
            $filename = Str::uuid()->toString().'.jpg';
            $path = $directory.'/'.$filename;

            Storage::disk(PrivateIncidentFiles::DISK)->put($path, $capture['data']);
            $absolutePath = Storage::disk(PrivateIncidentFiles::DISK)->path($path);

            $latitude = (float) ($capture['latitude'] ?? null);
            $longitude = (float) ($capture['longitude'] ?? null);
            $timestamp = $capture['captured_at'] ?? now()->toDateTimeString();

            if ($latitude !== null && $longitude !== null) {
                $place = $this->watermarkPhoto(
                    $absolutePath,
                    $latitude,
                    $longitude,
                    $incident->barangay,
                    $timestamp,
                    $incident->location_address,
                    $this->normalizeLocationSource($capture['location_source'] ?? null),
                );

                if (empty($incident->latitude) || empty($incident->longitude)) {
                    $incident->update([
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ]);
                }

                if (! empty($place) && (empty($incident->location_address) || $incident->location_address === null)) {
                    $incident->update(['location_address' => $place]);
                }
            }

            Evidence::query()->create([
                'incident_id' => $incident->id,
                'uploaded_by' => $uploadedBy,
                'type' => EvidenceType::Photo,
                'file_path' => $path,
                'original_filename' => $capture['filename'] ?? 'GPS Capture',
                'mime_type' => 'image/jpeg',
                'file_size' => strlen($capture['data']),
                'priority' => 1,
                'is_gps_capture' => true,
            ]);
        }
    }

    private function normalizeLocationSource(mixed $source): string
    {
        return in_array($source, ['gps', 'map-pin', 'exif'], true) ? $source : 'gps';
    }

    private function resolveType(UploadedFile $file): EvidenceType
    {
        $mime = (string) $file->getMimeType();

        if (str_starts_with($mime, 'image/')) {
            return EvidenceType::Photo;
        }

        if (str_starts_with($mime, 'video/')) {
            return EvidenceType::Video;
        }

        if (str_starts_with($mime, 'audio/')) {
            return EvidenceType::Audio;
        }

        return EvidenceType::Document;
    }

    public function deleteFile(Evidence $evidence): void
    {
        if ($evidence->file_path) {
            PrivateIncidentFiles::delete($evidence->file_path);
        }
    }

    public function remove(Evidence $evidence): void
    {
        $this->deleteFile($evidence);
        $evidence->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function captureMetadata(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Extracts coordinates from JPEG EXIF metadata.
     */
    private function getExifGpsCoordinates(string $path): ?array
    {
        if (! function_exists('exif_read_data')) {
            return null;
        }

        try {
            $exif = @exif_read_data($path);
            if (! $exif || empty($exif['GPSLatitude']) || empty($exif['GPSLongitude'])) {
                return null;
            }

            $lat = $this->parseGpsRational($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N');
            $lng = $this->parseGpsRational($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E');

            if ($lat && $lng) {
                return ['latitude' => $lat, 'longitude' => $lng];
            }
        } catch (\Exception $e) {
            Log::warning('EXIF coordinate extraction failed: '.$e->getMessage());
        }

        return null;
    }

    private function parseGpsRational(array $rational, string $ref): float
    {
        $degrees = $this->rationalToFloat($rational[0]);
        $minutes = $this->rationalToFloat($rational[1]);
        $seconds = $this->rationalToFloat($rational[2]);

        $decimal = $degrees + ($minutes / 60.0) + ($seconds / 3600.0);

        if (in_array(strtoupper($ref), ['S', 'W'])) {
            $decimal = -$decimal;
        }

        return $decimal;
    }

    private function rationalToFloat(string $rational): float
    {
        $parts = explode('/', $rational);
        if (count($parts) === 2 && $parts[1] != 0) {
            return (float) $parts[0] / (float) $parts[1];
        }

        return (float) $rational;
    }

    /**
     * Watermarks a photo using PHP GD: title, coordinates, full address
     * (barangay/municipality/province/country), formatted date/time, and a
     * enlarged map preview thumbnail with a pin. Uses the submitted location
     * address or locally configured municipality details for the watermark.
     */
    private function watermarkPhoto(
        string $absolutePath,
        float $latitude,
        float $longitude,
        ?string $barangay,
        string $timestamp,
        ?string $locationAddress = null,
        string $locationSource = 'gps',
    ): ?string
    {
        if (! function_exists('imagecreatefromjpeg')) {
            return null;
        }

        try {
            $info = @getimagesize($absolutePath);
            if (! $info) {
                return null;
            }

            $mime = $info['mime'];
            switch ($mime) {
                case 'image/jpeg':
                case 'image/jpg':
                    $image = @imagecreatefromjpeg($absolutePath);
                    break;
                case 'image/png':
                    $image = @imagecreatefrompng($absolutePath);
                    break;
                case 'image/webp':
                    $image = @imagecreatefromwebp($absolutePath);
                    break;
                default:
                    return null;
            }

            if (! $image) {
                return null;
            }

            $width = imagesx($image);
            $height = imagesy($image);

            $place = $this->resolveFullAddress($barangay, $locationAddress);

            $sourcePrefix = match ($locationSource) {
                'map-pin' => 'Map pin (GPS unavailable) - ',
                'exif' => 'EXIF GPS - ',
                default => '',
            };
            $coordsText = $sourcePrefix.sprintf('%f, %f', $latitude, $longitude);
            $locationText = $place;
            $timeText = $this->formatWatermarkTimestamp($timestamp);

            $this->compositeWatermarkLayer($image, $width, $height, [
                ['text' => 'RANIAG GPS CAMERA', 'accent' => true],
                ['text' => $coordsText, 'accent' => false],
                ['text' => $locationText, 'accent' => false],
                ['text' => $timeText, 'accent' => false],
            ], $latitude, $longitude);

            // Overwrite original photo
            switch ($mime) {
                case 'image/jpeg':
                case 'image/jpg':
                    @imagejpeg($image, $absolutePath, 94);
                    break;
                case 'image/png':
                    @imagepng($image, $absolutePath);
                    break;
                case 'image/webp':
                    @imagewebp($image, $absolutePath, 94);
                    break;
            }

            @imagedestroy($image);

            // Return the resolved place for callers to persist if needed
            return $place;
        } catch (\Exception $e) {
            Log::warning('GD Photo watermarking failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Uses the address collected with the report when available. Otherwise,
     * compose a local address from the resolved barangay and configured
     * municipality; report submission must not depend on an external lookup.
     */
    private function resolveFullAddress(?string $barangay, ?string $locationAddress): string
    {
        $locationAddress = trim((string) $locationAddress);
        $defaults = config('raniag.address', []);
        $municipality = $defaults['municipality'] ?? 'Pamplona';
        $province = $defaults['province'] ?? 'Cagayan';
        $country = $defaults['country'] ?? 'Philippines';

        $parts = $locationAddress !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $locationAddress))))
            : [];
        $barangayLabel = trim((string) $barangay);
        $barangayPartIndex = collect($parts)->search(
            fn (string $part) => str_starts_with(mb_strtolower($part), 'barangay ')
                || str_starts_with(mb_strtolower($part), 'brgy. ')
        );
        $partsContainBarangay = $barangayPartIndex !== false;
        $matchingBarangayIndex = $barangayLabel === ''
            ? false
            : collect($parts)->search(fn (string $part) => str_contains(mb_strtolower($part), mb_strtolower($barangayLabel)));

        if ($barangayLabel !== '' && ! $partsContainBarangay && $matchingBarangayIndex !== false) {
            $parts[$matchingBarangayIndex] = 'Barangay '.$parts[$matchingBarangayIndex];
        } elseif ($barangayLabel !== '' && ! $partsContainBarangay) {
            array_unshift($parts, 'Barangay '.$barangayLabel);
        } elseif ($barangayLabel === '' && $parts !== [] && ! $partsContainBarangay) {
            array_unshift($parts, 'Barangay not resolved');
        } elseif ($parts === []) {
            $parts[] = $barangayLabel !== '' ? 'Barangay '.$barangayLabel : 'Barangay not resolved';
        }

        if ($locationAddress === '') {
            $parts[] = $municipality;
        }

        $addressText = mb_strtolower(implode(', ', $parts));
        foreach ([$province, $country] as $suffix) {
            if (! str_contains($addressText, mb_strtolower($suffix))) {
                $parts[] = $suffix;
                $addressText .= ', '.mb_strtolower($suffix);
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Formats a timestamp as: "Monday, 27/07/2026 08:23 PM GMT +08:00",
     * using the application timezone (Asia/Manila) so the printed offset
     * always matches the printed time.
     */
    private function formatWatermarkTimestamp(string $timestamp): string
    {
        try {
            $carbon = \Illuminate\Support\Carbon::parse($timestamp)->setTimezone(config('app.timezone', 'Asia/Manila'));

            return $carbon->format('l, d/m/Y h:i A \G\M\T P');
        } catch (\Throwable $t) {
            return $timestamp;
        }
    }

    private function isSupportedMapTile(string $bytes): bool
    {
        return str_starts_with($bytes, "\x89PNG") || str_starts_with($bytes, "\xFF\xD8\xFF");
    }

    /**
     * Builds a centered, high-resolution map crop from Mapbox satellite tiles
     * (with OpenStreetMap fallback). Cached tiles are reused; missing neighbors
     * are fetched concurrently so the pin remains centered in the watermark.
     *
     * @return array{image: \GdImage, px: float, py: float}|null
     */
    private function fetchMapTile(float $latitude, float $longitude, int $zoom = 17): ?array
    {
        try {
            $n = 2 ** $zoom;
            $mapboxToken = trim((string) config('raniag.map.mapbox_token', ''));
            $mapStyle = $mapboxToken !== '' ? 'satellite-streets-v12' : 'osm';
            $latRad = deg2rad($latitude);
            $xFloat = (($longitude + 180) / 360) * $n;
            $yFloat = ((1 - log(tan($latRad) + 1 / cos($latRad)) / M_PI) / 2) * $n;
            $xTile = (int) floor($xFloat);
            $yTile = (int) floor($yFloat);
            $tileImages = [];
            $missingTiles = [];

            for ($dy = -1; $dy <= 1; $dy++) {
                for ($dx = -1; $dx <= 1; $dx++) {
                    $tileX = (($xTile + $dx) % $n + $n) % $n;
                    $tileY = $yTile + $dy;
                    if ($tileY < 0 || $tileY >= $n) {
                        continue;
                    }

                    $key = "{$dx}:{$dy}";
                    $extension = $mapStyle === 'satellite-streets-v12' ? 'jpg' : 'png';
                    $cachePath = storage_path("app/map-tiles/{$mapStyle}/{$zoom}/{$tileX}/{$tileY}.{$extension}");
                    $tileData = is_readable($cachePath) ? @file_get_contents($cachePath) : false;

                    if (! is_string($tileData) || ! $this->isSupportedMapTile($tileData)) {
                        $missingTiles[$key] = [
                            'x' => $tileX,
                            'y' => $tileY,
                            'path' => $cachePath,
                        ];
                        continue;
                    }

                    $image = @imagecreatefromstring($tileData);
                    if ($image) {
                        $tileImages[$key] = $image;
                    } else {
                        $missingTiles[$key] = [
                            'x' => $tileX,
                            'y' => $tileY,
                            'path' => $cachePath,
                        ];
                    }
                }
            }

            if ($missingTiles !== []) {
                try {
                    $failedTileCount = 0;
                    $responses = Http::pool(function (Pool $pool) use ($missingTiles, $zoom, $mapboxToken) {
                        foreach ($missingTiles as $key => $tile) {
                            $request = $pool->as($key)->timeout(6);
                            if ($mapboxToken !== '') {
                                $request->get("https://api.mapbox.com/styles/v1/mapbox/satellite-streets-v12/tiles/256/{$zoom}/{$tile['x']}/{$tile['y']}@2x", [
                                    'access_token' => $mapboxToken,
                                ]);
                            } else {
                                $request->withHeaders(['User-Agent' => 'RANIAG/1.0'])
                                    ->get("https://tile.openstreetmap.org/{$zoom}/{$tile['x']}/{$tile['y']}.png");
                            }
                        }
                    }, concurrency: 9);

                    foreach ($missingTiles as $key => $tile) {
                        $response = $responses[$key] ?? null;
                        if (! $response instanceof Response || ! $response->ok()) {
                            if ($mapboxToken === '') {
                                $failedTileCount++;
                                continue;
                            }

                            try {
                                $response = Http::timeout(4)
                                    ->withHeaders(['User-Agent' => 'RANIAG/1.0'])
                                    ->get("https://tile.openstreetmap.org/{$zoom}/{$tile['x']}/{$tile['y']}.png");
                            } catch (\Throwable) {
                                $failedTileCount++;
                                continue;
                            }
                            if (! $response->ok()) {
                                $failedTileCount++;
                                continue;
                            }
                            $tile['path'] = storage_path("app/map-tiles/osm/{$zoom}/{$tile['x']}/{$tile['y']}.png");
                        }

                        $tileData = $response->body();
                        if (! $this->isSupportedMapTile($tileData)) {
                            if ($mapboxToken === '') {
                                $failedTileCount++;
                                continue;
                            }

                            try {
                                $response = Http::timeout(4)
                                    ->withHeaders(['User-Agent' => 'RANIAG/1.0'])
                                    ->get("https://tile.openstreetmap.org/{$zoom}/{$tile['x']}/{$tile['y']}.png");
                            } catch (\Throwable) {
                                $failedTileCount++;
                                continue;
                            }
                            if (! $response->ok() || ! $this->isSupportedMapTile($response->body())) {
                                $failedTileCount++;
                                continue;
                            }
                            $tileData = $response->body();
                            $tile['path'] = storage_path("app/map-tiles/osm/{$zoom}/{$tile['x']}/{$tile['y']}.png");
                        }
                        $image = @imagecreatefromstring($tileData);
                        if (! $image) {
                            $failedTileCount++;
                            continue;
                        }

                        $tileImages[$key] = $image;
                        @mkdir(dirname($tile['path']), 0755, true);
                        @file_put_contents($tile['path'], $tileData);
                    }
                    if ($failedTileCount > 0) {
                        Log::warning('Some map tiles were unavailable for evidence watermark.', [
                            'failed_tile_count' => $failedTileCount,
                        ]);
                    }
                } catch (\Throwable $exception) {
                    Log::warning('Surrounding map tiles could not be fetched for evidence watermark.', [
                        'message' => $exception->getMessage(),
                    ]);
                }
            }

            if (! isset($tileImages['0:0'])) {
                foreach ($tileImages as $image) {
                    imagedestroy($image);
                }

                return null;
            }

            $px = $xFloat - $xTile;
            $py = $yFloat - $yTile;
            $centered = $this->centeredMapCrop($tileImages, $px, $py);
            foreach ($tileImages as $image) {
                imagedestroy($image);
            }

            return $centered
                ? ['image' => $centered, 'px' => 0.5, 'py' => 0.5]
                : null;
        } catch (\Throwable $t) {
            Log::warning('Map tile fetch failed: '.$t->getMessage());

            return null;
        }
    }

    /**
     * @param  array<string, \GdImage>  $tileImages  Tiles keyed by "x-offset:y-offset"
     */
    private function centeredMapCrop(array $tileImages, float $px, float $py): ?\GdImage
    {
        $tileSize = imagesx(reset($tileImages));
        $mosaic = imagecreatetruecolor($tileSize * 3, $tileSize * 3);
        $background = imagecolorallocate($mosaic, 219, 230, 218);
        imagefill($mosaic, 0, 0, $background);

        foreach ($tileImages as $key => $image) {
            [$dx, $dy] = array_map('intval', explode(':', $key));
            imagecopyresampled(
                $mosaic,
                $image,
                ($dx + 1) * $tileSize,
                ($dy + 1) * $tileSize,
                0,
                0,
                $tileSize,
                $tileSize,
                imagesx($image),
                imagesy($image)
            );
        }

        $centered = imagecrop($mosaic, [
            'x' => (int) round($tileSize + ($px * $tileSize) - ($tileSize / 2)),
            'y' => (int) round($tileSize + ($py * $tileSize) - ($tileSize / 2)),
            'width' => $tileSize,
            'height' => $tileSize,
        ]);
        imagedestroy($mosaic);

        return $centered ?: null;
    }

    /**
     * Draws the watermark at a fixed reference size (crisp bitmap fonts), then scales
     * that layer to match the actual photo's resolution before blending it onto the
     * bottom of the image. This keeps the watermark legible and proportionate whether
     * the source photo is a small desktop capture or a full-resolution phone photo,
     * and preserves the semi-transparent gradient look used in the live preview.
     *
     * @param  array<int, array{text: string, accent: bool}>  $lines
     */
    private function compositeWatermarkLayer($image, int $width, int $height, array $lines, ?float $latitude = null, ?float $longitude = null): void
    {
        $refWidth = 760;
        $refBannerHeight = 250;

        $layer = imagecreatetruecolor($refWidth, $refBannerHeight);
        imagealphablending($layer, false);
        imagesavealpha($layer, true);
        $transparent = imagecolorallocatealpha($layer, 0, 0, 0, 127);
        imagefilledrectangle($layer, 0, 0, $refWidth, $refBannerHeight, $transparent);

        // Soft top-to-bottom gradient matching the live and recorded overlays.
        for ($y = 0; $y < $refBannerHeight; $y++) {
            $t = $y / max(1, $refBannerHeight - 1);
            $opacity = 0.3 + (0.92 - 0.3) * $t;
            $gdAlpha = (int) round(127 * (1 - $opacity));
            $gdAlpha = max(0, min(127, $gdAlpha));
            $rowColor = imagecolorallocatealpha($layer, 15, 23, 42, $gdAlpha);
            imageline($layer, 0, $y, $refWidth, $y, $rowColor);
        }

        imagealphablending($layer, true);

        $accentColor = imagecolorallocate($layer, 147, 197, 253);
        $pinColor = imagecolorallocate($layer, 61, 139, 253);
        $textColor = imagecolorallocate($layer, 255, 255, 255);

        $pad = 14;
        $textStartX = $pad;

        // Keep the map and pin visible even if the map tile service is offline.
        if ($latitude !== null && $longitude !== null) {
            $tile = $this->fetchMapTile($latitude, $longitude);
            $boxSize = $refBannerHeight - (2 * $pad);
            $mapBackground = imagecolorallocate($layer, 219, 230, 218);
            imagefilledrectangle($layer, $pad, $pad, $pad + $boxSize - 1, $pad + $boxSize - 1, $mapBackground);
            $roadColor = imagecolorallocate($layer, 255, 255, 255);
            $minorRoadColor = imagecolorallocate($layer, 155, 177, 158);
            imageline($layer, $pad, $pad + (int) ($boxSize * 0.34), $pad + $boxSize - 1, $pad + (int) ($boxSize * 0.69), $roadColor);
            imageline($layer, $pad + (int) ($boxSize * 0.7), $pad, $pad + (int) ($boxSize * 0.28), $pad + $boxSize - 1, $roadColor);
            imageline($layer, $pad, $pad + (int) ($boxSize * 0.82), $pad + $boxSize - 1, $pad + (int) ($boxSize * 0.53), $minorRoadColor);
            if ($tile) {
                $tileSize = imagesx($tile['image']);

                imagecopyresampled(
                    $layer,
                    $tile['image'],
                    $pad,
                    $pad,
                    0,
                    0,
                    $boxSize,
                    $boxSize,
                    $tileSize,
                    $tileSize
                );
                imagedestroy($tile['image']);
            }
            $border = imagecolorallocatealpha($layer, 255, 255, 255, 35);
            imagerectangle($layer, $pad, $pad, $pad + $boxSize - 1, $pad + $boxSize - 1, $border);

            $pinX = (int) round($pad + (($tile['px'] ?? 0.5) * $boxSize));
            $pinY = (int) round($pad + (($tile['py'] ?? 0.5) * $boxSize));
            $pinWhite = imagecolorallocate($layer, 255, 255, 255);
            imagefilledellipse($layer, $pinX, $pinY, 28, 28, $pinWhite);
            imagefilledellipse($layer, $pinX, $pinY, 18, 18, $pinColor);

            $textStartX = $pad + $boxSize + 10;
        }

        $y = $pad + 5;
        $detailScale = 24 / imagefontheight(3);
        $maxCharacters = max(1, (int) floor(($refWidth - $textStartX - $pad) / (imagefontwidth(3) * $detailScale)));
        foreach ($lines as $index => $line) {
            $font = $index === 0 ? 5 : 3;
            $fontScale = $index === 0 ? 32 / imagefontheight(5) : $detailScale;
            $color = ! empty($line['accent']) ? $accentColor : $textColor;
            $wrappedLines = $index === 0
                ? [$line['text']]
                : explode("\n", wordwrap($line['text'], $maxCharacters, "\n", true));
            foreach ($wrappedLines as $wrappedLine) {
                $this->drawScaledBitmapText($layer, $font, $textStartX, $y, $wrappedLine, $color, $fontScale);
                $y += $index === 0 ? 40 : 30;
            }
        }

        // Scale proportionally to the actual photo width so the watermark stays
        // legible on any device resolution, capped so it can never dominate a
        // very short/cropped image.
        $targetBannerHeight = (int) round($refBannerHeight * ($width / $refWidth));
        $targetBannerHeight = max(90, min($targetBannerHeight, (int) round($height * 0.48)));

        imagealphablending($image, true);
        imagecopyresampled(
            $image,
            $layer,
            0,
            $height - $targetBannerHeight,
            0,
            0,
            $width,
            $targetBannerHeight,
            $refWidth,
            $refBannerHeight
        );

        imagedestroy($layer);
    }

    private function drawScaledBitmapText($target, int $font, int $x, int $y, string $text, int $color, float $scale): void
    {
        if ($text === '') {
            return;
        }

        $width = imagefontwidth($font) * strlen($text);
        $height = imagefontheight($font);
        $glyph = imagecreatetruecolor($width, $height);
        imagealphablending($glyph, false);
        imagesavealpha($glyph, true);
        $transparent = imagecolorallocatealpha($glyph, 0, 0, 0, 127);
        imagefill($glyph, 0, 0, $transparent);
        imagealphablending($glyph, true);
        $rgb = imagecolorsforindex($target, $color);
        $glyphColor = imagecolorallocate($glyph, $rgb['red'], $rgb['green'], $rgb['blue']);
        imagestring($glyph, $font, 0, 0, $text, $glyphColor);

        imagecopyresampled(
            $target,
            $glyph,
            $x,
            $y,
            0,
            0,
            (int) ceil($width * $scale),
            (int) ceil($height * $scale),
            $width,
            $height
        );
        imagedestroy($glyph);
    }
}
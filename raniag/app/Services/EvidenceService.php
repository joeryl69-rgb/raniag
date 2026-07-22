<?php

namespace App\Services;

use App\Enums\EvidenceType;
use App\Models\Evidence;
use App\Models\Incident;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EvidenceService
{
    /**
     * Attach files to an incident, checking for GPS metadata (either from the Web app capture or EXIF data)
     * and watermarking geotagged photos using PHP GD.
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
            $timestamp = now()->toDateTimeString();

            // 1. Check if it matches a web camera capture
            if (isset($gpsLookup[$originalName])) {
                $isGpsCapture = true;
                $latitude = (float) ($gpsLookup[$originalName]['latitude'] ?? null);
                $longitude = (float) ($gpsLookup[$originalName]['longitude'] ?? null);
                if (! empty($gpsLookup[$originalName]['captured_at'])) {
                    $timestamp = date('Y-m-d H:i:s', strtotime($gpsLookup[$originalName]['captured_at']));
                }
            }

            $directory = sprintf('incidents/%d/evidence', $incident->id);
            $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs($directory, $filename, 'public');
            $absolutePath = Storage::disk('public')->path($path);

            // 2. If it is NOT a web capture, check if it has EXIF geotags
            $fileType = $this->resolveType($file);
            if (! $isGpsCapture && $fileType === EvidenceType::Photo) {
                $exifCoords = $this->getExifGpsCoordinates($absolutePath);
                if ($exifCoords) {
                    $isGpsCapture = true;
                    $latitude = $exifCoords['latitude'];
                    $longitude = $exifCoords['longitude'];
                }
            }

            // 3. Watermark the photo if it has coordinates
            if ($isGpsCapture && $latitude !== null && $longitude !== null && $fileType === EvidenceType::Photo) {
                $place = $this->watermarkPhoto($absolutePath, $latitude, $longitude, $incident->barangay, $incident->location_address, $timestamp);

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

            Storage::disk('public')->put($path, $capture['data']);
            $absolutePath = Storage::disk('public')->path($path);

            $latitude = (float) ($capture['latitude'] ?? null);
            $longitude = (float) ($capture['longitude'] ?? null);
            $timestamp = $capture['captured_at'] ?? now()->toDateTimeString();

            if ($latitude !== null && $longitude !== null) {
                $place = $this->watermarkPhoto($absolutePath, $latitude, $longitude, $incident->barangay, $incident->location_address, $timestamp);

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
        if ($evidence->file_path && Storage::disk('public')->exists($evidence->file_path)) {
            Storage::disk('public')->delete($evidence->file_path);
        }
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
     * Watermarks a photo using PHP GD.
     * Uses incident location_address when available; otherwise attempts reverse-geocoding
     * from OpenStreetMap (Nominatim) to produce an accurate place name for the watermark.
     */
    private function watermarkPhoto(string $absolutePath, float $latitude, float $longitude, ?string $barangay, ?string $locationAddress, string $timestamp): ?string
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

            // Banner height dynamically adjusted relative to the image height
            $bannerHeight = (int) ($height * 0.12);
            if ($bannerHeight < 60) {
                $bannerHeight = 60;
            }
            if ($bannerHeight > 180) {
                $bannerHeight = 180;
            }

            // Semi-transparent black banner box at the bottom
            $bannerColor = imagecolorallocatealpha($image, 15, 23, 42, 60);
            imagefilledrectangle($image, 0, $height - $bannerHeight, $width, $height, $bannerColor);

            // Standard GD built-in fonts (white and teal accent)
            $textColor = imagecolorallocate($image, 255, 255, 255);
            $accentColor = imagecolorallocate($image, 32, 201, 151);

            $startY = $height - $bannerHeight + 6;

            // Title Line
            imagestring($image, 4, 12, $startY, 'RANIAG GPS CAMERA', $accentColor);

            // Info lines (using slightly smaller font)
            $coordsText = sprintf('GPS COORDINATES: %f, %f', $latitude, $longitude);
            imagestring($image, 2, 12, $startY + 16, $coordsText, $textColor);

            // Determine a concise, accurate location string
            $place = null;
            if (! empty($locationAddress)) {
                $place = trim($locationAddress);
            } else {
                // Try reverse geocoding via OpenStreetMap Nominatim (no API key required)
                try {
                    $url = sprintf('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=%F&lon=%F&zoom=12&addressdetails=1', $latitude, $longitude);
                    $opts = [
                        'http' => [
                            'method' => 'GET',
                            'header' => "User-Agent: RANIAG/1.0\r\nAccept: application/json\r\n",
                            'timeout' => 5,
                        ],
                    ];
                    $context = stream_context_create($opts);
                    $resp = @file_get_contents($url, false, $context);
                    if ($resp) {
                        $json = json_decode($resp, true);
                        if (! empty($json['address'])) {
                            $addr = $json['address'];
                            $parts = [];
                            foreach (['city', 'town', 'village', 'hamlet', 'county'] as $k) {
                                if (! empty($addr[$k])) {
                                    $parts[] = $addr[$k];
                                    break;
                                }
                            }
                            if (! empty($addr['state'])) {
                                $parts[] = $addr['state'];
                            }
                            if (! empty($addr['country'])) {
                                $parts[] = $addr['country'];
                            }
                            if (! empty($parts)) {
                                $place = implode(', ', $parts);
                            }
                        }
                        if (! $place && ! empty($json['display_name'])) {
                            $place = $json['display_name'];
                        }
                    }
                } catch (\Throwable $t) {
                    Log::warning('Reverse geocode failed: '.$t->getMessage());
                }
            }

            if (! $place) {
                // Fallback: use barangay if present, otherwise keep a short fallback label
                if (! empty($barangay)) {
                    $place = 'Barangay '.strtoupper($barangay).', Pamplona, Cagayan, PH';
                } else {
                    $place = 'Pamplona, Cagayan, PH';
                }
            }

            $locationText = 'LOCATION: '.$place;
            imagestring($image, 2, 12, $startY + 28, $locationText, $textColor);

            $timeText = 'DATE/TIME: '.$timestamp;
            imagestring($image, 2, 12, $startY + 40, $timeText, $textColor);

            // Overwrite original photo
            switch ($mime) {
                case 'image/jpeg':
                case 'image/jpg':
                    @imagejpeg($image, $absolutePath, 88);
                    break;
                case 'image/png':
                    @imagepng($image, $absolutePath);
                    break;
                case 'image/webp':
                    @imagewebp($image, $absolutePath, 88);
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
}

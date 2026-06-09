<?php

namespace App\Services;

use App\Enums\EvidenceType;
use App\Models\Evidence;
use App\Models\Incident;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EvidenceService
{
    /**
     * @param  list<UploadedFile>  $files
     */
    public function attachToIncident(Incident $incident, array $files, bool $isGpsCapture = false): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $directory = sprintf('incidents/%d/evidence', $incident->id);
            $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs($directory, $filename, 'public');

            Evidence::query()->create([
                'incident_id' => $incident->id,
                'uploaded_by' => null,
                'type' => $this->resolveType($file),
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'priority' => $isGpsCapture ? 1 : 0,
                'is_gps_capture' => $isGpsCapture,
            ]);
        }
    }

    public function attachGpsCaptures(Incident $incident, array $gpsCaptures): void
    {
        foreach ($gpsCaptures as $capture) {
            if (empty($capture['data']) || empty($capture['filename'])) {
                continue;
            }

            $directory = sprintf('incidents/%d/evidence', $incident->id);
            $filename = Str::uuid()->toString().'.jpg';
            $path = $directory.'/'.$filename;

            Storage::disk('public')->put($path, $capture['data']);

            Evidence::query()->create([
                'incident_id' => $incident->id,
                'uploaded_by' => null,
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
}

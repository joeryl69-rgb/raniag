<?php

namespace App\Services;

use App\Enums\IncidentDocumentType;
use App\Models\Incident;
use App\Models\IncidentDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IncidentDocumentService
{
    /**
     * Store one case-document file (photo capture or regular upload) against an incident.
     */
    public function attachToIncident(
        Incident $incident,
        UploadedFile $file,
        IncidentDocumentType $documentType,
        bool $isCameraCapture = false,
        ?int $uploadedBy = null,
        ?string $notes = null,
    ): IncidentDocument {
        $directory = sprintf('incidents/%d/documents', $incident->id);
        $filename = Str::uuid()->toString().'.'.($file->getClientOriginalExtension() ?: 'jpg');
        $path = $file->storeAs($directory, $filename, 'public');

        return IncidentDocument::query()->create([
            'incident_id' => $incident->id,
            'uploaded_by' => $uploadedBy,
            'document_type' => $documentType,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'is_camera_capture' => $isCameraCapture,
            'notes' => $notes,
        ]);
    }

    public function deleteFile(IncidentDocument $document): void
    {
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }
    }
}

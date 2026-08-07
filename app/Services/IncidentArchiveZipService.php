<?php

namespace App\Services;

use App\Models\Incident;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Bundles an incident's filed documents and evidence photos into a single
 * downloadable ZIP for the Archived Reports repository.
 */
class IncidentArchiveZipService
{
    /**
     * Build a ZIP file on disk and return its absolute path. Caller is
     * responsible for deleting the temp file after the response is sent.
     */
    public function build(Incident $incident): string
    {
        $incident->loadMissing(['incidentDocuments', 'evidence', 'incidentType']);

        $tmpPath = tempnam(sys_get_temp_dir(), 'archive');
        unlink($tmpPath);
        $tmpPath .= '.zip';

        $zip = new ZipArchive;
        $result = $zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($result !== true) {
            throw new \RuntimeException("Unable to create archive ZIP (ZipArchive error code {$result}).");
        }

        $summary = "Tracking #: {$incident->tracking_number}\n"
            ."Type: {$incident->incidentType?->name}\n"
            ."Barangay: {$incident->barangay}\n"
            ."Status: ".(is_object($incident->status) ? $incident->status->value : $incident->status)."\n"
            ."Reported At: {$incident->reported_at?->format('Y-m-d H:i')}\n";
        $zip->addFromString('summary.txt', $summary);

        foreach ($incident->incidentDocuments as $doc) {
            $this->addFileToZip($zip, $doc->file_path, 'documents/', $doc->original_filename);
        }

        foreach ($incident->evidence as $evidence) {
            $this->addFileToZip($zip, $evidence->file_path, 'evidence/', $evidence->original_filename);
        }

        $zip->close();

        return $tmpPath;
    }

    /**
     * Build a single ZIP containing one subfolder per incident, for the
     * "select all / bulk download" action on the Archived Reports page.
     *
     * @param  \Illuminate\Support\Collection<int, Incident>  $incidents
     */
    public function buildMany($incidents): string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'archive');
        unlink($tmpPath);
        $tmpPath .= '.zip';

        $zip = new ZipArchive;
        $result = $zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($result !== true) {
            throw new \RuntimeException("Unable to create archive ZIP (ZipArchive error code {$result}).");
        }

        foreach ($incidents as $incident) {
            $incident->loadMissing(['incidentDocuments', 'evidence', 'incidentType']);
            $folder = $incident->tracking_number.'/';

            $summary = "Tracking #: {$incident->tracking_number}\n"
                ."Type: {$incident->incidentType?->name}\n"
                ."Barangay: {$incident->barangay}\n"
                ."Status: ".(is_object($incident->status) ? $incident->status->value : $incident->status)."\n"
                ."Reported At: {$incident->reported_at?->format('Y-m-d H:i')}\n";
            $zip->addFromString($folder.'summary.txt', $summary);

            foreach ($incident->incidentDocuments as $doc) {
                $this->addFileToZip($zip, $doc->file_path, $folder.'documents/', $doc->original_filename);
            }

            foreach ($incident->evidence as $evidence) {
                $this->addFileToZip($zip, $evidence->file_path, $folder.'evidence/', $evidence->original_filename);
            }
        }

        $zip->close();

        return $tmpPath;
    }

    protected function addFileToZip(ZipArchive $zip, ?string $path, string $folder, ?string $originalName): void
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return;
        }

        $name = $originalName ?: basename($path);
        $zip->addFromString($folder.$name, Storage::disk('local')->get($path));
    }
}

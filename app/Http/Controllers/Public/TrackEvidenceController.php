<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Evidence;
use App\Support\PrivateIncidentFiles;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrackEvidenceController extends Controller
{
    /**
     * Serve reporter-submitted evidence only after a successful /track lookup
     * in this session (tracking number + access code).
     */
    public function show(Evidence $evidence): StreamedResponse
    {
        $evidence->loadMissing('incident');

        if ($evidence->uploaded_by !== null) {
            abort(403);
        }

        if (! session()->get('track_verified.'.$evidence->incident_id)) {
            abort(403, 'Verify your tracking number and access code first.');
        }

        $path = $evidence->file_path;
        if (! $path || ! PrivateIncidentFiles::exists($path)) {
            abort(404);
        }

        $absolute = PrivateIncidentFiles::absolutePath($path);
        $mime = $evidence->mime_type ?: 'application/octet-stream';
        $downloadName = $evidence->original_filename ?: basename($path);

        return response()->stream(function () use ($absolute) {
            $stream = fopen($absolute, 'rb');
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

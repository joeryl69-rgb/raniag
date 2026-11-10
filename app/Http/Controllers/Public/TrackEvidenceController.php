<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Evidence;
use App\Services\PublicTrackingAccess;
use App\Support\PrivateIncidentFiles;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrackEvidenceController extends Controller
{
    public function __construct(private readonly PublicTrackingAccess $trackingAccess) {}

    /**
     * Serve reporter-submitted evidence only after a successful /track lookup
     * in this session (tracking number + access code).
     */
    public function show(Request $request, Evidence $evidence): StreamedResponse|BinaryFileResponse
    {
        $evidence->loadMissing('incident');

        if ($evidence->uploaded_by !== null) {
            abort(403);
        }

        if (! $this->trackingAccess->isVerified($request, $evidence->incident)) {
            abort(403, 'Verify your tracking number and access code first.');
        }

        $path = $evidence->file_path;
        if (! $path || ! PrivateIncidentFiles::exists($path)) {
            abort(404);
        }

        $absolute = PrivateIncidentFiles::absolutePath($path);
        $mime = $evidence->mime_type ?: 'application/octet-stream';
        $downloadName = $evidence->original_filename ?: basename($path);

        if (str_starts_with($mime, 'video/')) {
            return response()->file($absolute, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

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

<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use App\Support\PrivateIncidentFiles;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceFileController extends Controller
{
    public function show(Evidence $evidence): StreamedResponse
    {
        $evidence->loadMissing('incident');

        Gate::authorize('view', $evidence->incident);

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

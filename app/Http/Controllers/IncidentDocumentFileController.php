<?php

namespace App\Http\Controllers;

use App\Models\IncidentDocument;
use App\Support\PrivateIncidentFiles;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncidentDocumentFileController extends Controller
{
    public function show(IncidentDocument $document): StreamedResponse
    {
        $document->loadMissing('incident');

        Gate::authorize('view', $document->incident);

        $path = $document->file_path;
        if (! $path || ! PrivateIncidentFiles::exists($path)) {
            abort(404);
        }

        $absolute = PrivateIncidentFiles::absolutePath($path);
        $mime = $document->mime_type ?: 'application/octet-stream';
        $downloadName = $document->original_filename ?: basename($path);

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

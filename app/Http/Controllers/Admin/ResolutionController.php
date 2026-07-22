<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Resolution;
use Illuminate\Http\Request;

class ResolutionController extends Controller
{
    public function update(Request $request, Incident $incident, Resolution $resolution)
    {
        $data = $request->validate([
            'summary' => ['required', 'string', 'min:20'],
            'actions_taken' => ['required', 'string', 'min:20'],
        ]);

        $resolution->update([
            'summary' => $data['summary'],
            'actions_taken' => $data['actions_taken'],
        ]);

        return redirect()
            ->route('admin.incidents.show', $incident->id)
            ->with('success', 'Resolution report has been updated successfully by the Administrator.');
    }
}

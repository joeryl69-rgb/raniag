<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Incident;
use App\Models\IncidentType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $incidentTypes = IncidentType::orderBy('name')->get(['id', 'name']);
        $agencies = Agency::orderBy('name')->get(['id', 'name', 'code']);
        $barangays = config('raniag.barangays');

        return view('admin.reports.index', compact('incidentTypes', 'agencies', 'barangays'));
    }

    public function generate(Request $Request)
    {
        $validated = $Request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'barangay' => 'nullable|string|in:'.implode(',', config('raniag.barangays')),
            'agency_id' => 'nullable|exists:agencies,id',
            'incident_type_id' => 'nullable|exists:incident_types,id',
        ]);

        $query = Incident::with(['incidentType', 'agency', 'statusUpdates'])
            ->whereBetween('reported_at', [
                $validated['date_from'].' 00:00:00',
                $validated['date_to'].' 23:59:59',
            ]);

        if (! empty($validated['barangay'])) {
            $query->where('barangay', $validated['barangay']);
        }

        if (! empty($validated['agency_id'])) {
            $query->where('agency_id', $validated['agency_id']);
        }

        if (! empty($validated['incident_type_id'])) {
            $query->where('incident_type_id', $validated['incident_type_id']);
        }

        $incidents = $query->orderByDesc('reported_at')->get();

        $pdf = Pdf::loadView('admin.reports.pdf', [
            'incidents' => $incidents,
            'filters' => $validated,
            'generated_at' => now(),
        ]);

        return $pdf->download('raniag-report-'.now()->format('Y-m-d').'.pdf');
    }
}

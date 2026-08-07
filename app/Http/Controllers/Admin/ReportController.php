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
            'date_from' => 'required|date|before_or_equal:today',
            'date_to' => 'required|date|after_or_equal:date_from|before_or_equal:today',
            'barangay' => 'nullable|string|in:'.implode(',', config('raniag.barangays')),
            'agency_id' => 'nullable|exists:agencies,id',
            'incident_type_id' => 'nullable|exists:incident_types,id',
        ]);

        $query = Incident::with(['incidentType', 'agency', 'statusUpdates', 'assignments.agency'])
            ->whereBetween('reported_at', [
                $validated['date_from'].' 00:00:00',
                $validated['date_to'].' 23:59:59',
            ]);

        if (! empty($validated['barangay'])) {
            $query->where('barangay', $validated['barangay']);
        }

        if (! empty($validated['agency_id'])) {
            // Agency can be set on incidents.agency_id directly OR via the assignments table
            $query->where(function ($q) use ($validated) {
                $q->where('agency_id', $validated['agency_id'])
                  ->orWhereHas('assignments', function ($a) use ($validated) {
                      $a->where('agency_id', $validated['agency_id']);
                  });
            });
        }

        if (! empty($validated['incident_type_id'])) {
            $query->where('incident_type_id', $validated['incident_type_id']);
        }

        $incidents = $query->orderByDesc('reported_at')->get();

        // Resolve agency name for the filter header
        $agencyName = null;
        if (! empty($validated['agency_id'])) {
            $agencyName = Agency::find($validated['agency_id'])?->name ?? 'N/A';
        }

        if ($incidents->isEmpty()) {
            return redirect()->route('admin.reports.index')
                ->withInput()
                ->with('warning', 'No incidents found for the selected filters. Please try a different date range or criteria.');
        }

        $resolvedAgencyNames = $incidents->mapWithKeys(
            fn ($incident) => [$incident->id => $this->resolveAgencyName($incident)]
        );

        $pdf = Pdf::loadView('admin.reports.pdf', [
            'incidents' => $incidents,
            'filters' => $validated,
            'agencyName' => $agencyName,
            'resolvedAgencyNames' => $resolvedAgencyNames,
            'generated_at' => now(),
        ]);

        return $pdf->download('raniag-report-'.now()->format('Y-m-d').'.pdf')
            ->cookie('download_token', $Request->input('download_token'), 1, null, null, null, false);
    }

    public function generateExcel(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'required|date|before_or_equal:today',
            'date_to' => 'required|date|after_or_equal:date_from|before_or_equal:today',
            'barangay' => 'nullable|string|in:'.implode(',', config('raniag.barangays')),
            'agency_id' => 'nullable|exists:agencies,id',
            'incident_type_id' => 'nullable|exists:incident_types,id',
        ]);

        $query = Incident::with(['incidentType', 'agency', 'assignments.agency'])
            ->whereBetween('reported_at', [
                $validated['date_from'].' 00:00:00',
                $validated['date_to'].' 23:59:59',
            ]);

        if (! empty($validated['barangay'])) {
            $query->where('barangay', $validated['barangay']);
        }
        if (! empty($validated['agency_id'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('agency_id', $validated['agency_id'])
                  ->orWhereHas('assignments', function ($a) use ($validated) {
                      $a->where('agency_id', $validated['agency_id']);
                  });
            });
        }
        if (! empty($validated['incident_type_id'])) {
            $query->where('incident_type_id', $validated['incident_type_id']);
        }

        $incidents = $query->orderByDesc('reported_at')->get();

        if ($incidents->isEmpty()) {
            return redirect()->route('admin.reports.index')
                ->withInput()
                ->with('warning', 'No incidents found for the selected filters. Please try a different date range or criteria.');
        }

        // Dashboard-style analytics for the same filtered period
        $byStatus = (clone $query)->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');
        $byType = (clone $query)->join('incident_types', 'incidents.incident_type_id', '=', 'incident_types.id')
            ->selectRaw('incident_types.name, COUNT(*) as count')->groupBy('incident_types.name')->orderByDesc('count')->pluck('count', 'name');
        $accidentProneAreas = (clone $query)->join('incident_types', 'incidents.incident_type_id', '=', 'incident_types.id')
            ->whereNotNull('barangay')
            ->selectRaw('barangay, incident_types.name as type, COUNT(*) as count')
            ->groupBy('barangay', 'incident_types.name')
            ->having('count', '>', 1)
            ->orderByDesc('count')
            ->limit(15)
            ->get();

        $writer = new \App\Services\SimpleXlsxWriter;
        $writer->setColumnWidths([16, 14, 16, 20, 14, 18]);

        $writer->addRow(['RANIAG Incident Report'], $writer::STYLE_TITLE);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Period: '.$validated['date_from'].' to '.$validated['date_to'].'  |  Generated: '.now()->format('M d, Y h:i A')], $writer::STYLE_SUBTITLE);
        $writer->mergeRow(0, 5);
        $writer->addRow([]);

        $writer->addRow(['Summary by Status'], $writer::STYLE_SECTION);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Status', 'Count'], $writer::STYLE_HEADER);
        foreach ($byStatus as $status => $count) {
            $writer->addRow([ucfirst(str_replace('_', ' ', $status)), $count], $writer::STYLE_BODY);
        }
        $writer->addRow([]);

        $writer->addRow(['Summary by Incident Type'], $writer::STYLE_SECTION);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Type', 'Count'], $writer::STYLE_HEADER);
        foreach ($byType as $type => $count) {
            $writer->addRow([$type, $count], $writer::STYLE_BODY);
        }
        $writer->addRow([]);

        $writer->addRow(['Accident-Prone Areas (Barangay Hotspots)'], $writer::STYLE_SECTION);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Barangay', 'Incident Type', 'Cases'], $writer::STYLE_HEADER);
        if ($accidentProneAreas->isEmpty()) {
            $writer->addRow(['No repeat hotspots found for this period.'], $writer::STYLE_BODY);
            $writer->mergeRow(0, 2);
        } else {
            foreach ($accidentProneAreas as $area) {
                $writer->addRow([$area->barangay, $area->type, $area->count], $writer::STYLE_BODY);
            }
        }
        $writer->addRow([]);

        $writer->addRow(['Incident Detail'], $writer::STYLE_SECTION);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Tracking #', 'Type', 'Barangay', 'Agency', 'Status', 'Reported At'], $writer::STYLE_HEADER);
        foreach ($incidents as $incident) {
            $status = is_object($incident->status) ? $incident->status->value : $incident->status;
            $writer->addRow([
                $incident->tracking_number,
                $incident->incidentType?->name,
                $incident->barangay,
                $this->resolveAgencyName($incident),
                ucfirst(str_replace('_', ' ', (string) $status)),
                $incident->reported_at?->format('Y-m-d H:i'),
            ], $writer::STYLE_BODY);
        }

        return response($writer->toBinary(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="raniag-report-'.now()->format('Y-m-d').'.xlsx"',
        ])->cookie('download_token', $request->input('download_token'), 1, null, null, null, false);
    }

    /**
     * Resolve the agency actually assigned to an incident, checking the
     * direct agency_id first, then the most recent assignment record
     * (regardless of whether it's still flagged active — a resolved
     * incident's assignment is no longer "active" but was still real).
     * Returns null (not "Unassigned") when no agency was ever selected.
     */
    private function resolveAgencyName(Incident $incident): ?string
    {
        if ($incident->agency?->name) {
            return $incident->agency->name;
        }

        $assignments = $incident->relationLoaded('assignments')
            ? $incident->assignments
            : $incident->assignments()->get();

        // Prefer the currently active assignment; fall back to the most
        // recent assignment that actually has an agency attached (covers
        // incidents reassigned to "unassigned" after a real agency worked it).
        $active = $assignments->firstWhere('is_active', true);
        if ($active?->agency?->name) {
            return $active->agency->name;
        }

        return $assignments->sortByDesc('created_at')
            ->first(fn ($a) => $a->agency?->name)
            ?->agency?->name;
    }
}

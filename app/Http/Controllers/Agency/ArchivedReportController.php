<?php

namespace App\Http\Controllers\Agency;

use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only "Resolved Reports" browser for the agency. Resolved/closed
 * incidents disappear from the active Dispatches list, so this is where
 * the agency can still look them up. There is intentionally no download-as-
 * ZIP "repository" feature here anymore — once an incident is resolved,
 * its official paperwork is generated/tracked through Document Requests.
 */
class ArchivedReportController extends Controller
{
    private const VALID_STATUSES = [IncidentStatus::Resolved->value, IncidentStatus::Closed->value];

    public function index(Request $request): View
    {
        $agencyId = $request->user()?->agency_id;
        abort_if(! $agencyId, 403, 'No agency is associated with this account.');

        $query = Incident::query()
            ->whereIn('status', self::VALID_STATUSES)
            ->whereHas('currentAssignments', fn ($q) => $q->where('agency_id', $agencyId))
            ->with(['incidentType'])
            ->withCount(['incidentDocuments', 'evidence']);

        $q = trim((string) $request->string('q'));
        if ($q !== '') {
            $query->where('tracking_number', 'like', "%{$q}%");
        }

        \App\Support\Filters::dateRange($query, 'reported_at', [
            'date_from' => $request->string('date_from')->trim()->value(),
            'date_to' => $request->string('date_to')->trim()->value(),
        ]);

        $barangay = $request->string('barangay')->value();
        if ($barangay !== '' && $barangay !== 'all') {
            $query->where('barangay', $barangay);
        }

        $status = $request->string('status')->value();
        if ($status !== '' && $status !== 'all' && in_array($status, self::VALID_STATUSES, true)) {
            $query->where('status', $status);
        }

        $sort = $request->string('sort')->value() ?: 'reported_at';
        $direction = $request->string('direction')->value() === 'asc' ? 'asc' : 'desc';
        $sortable = ['reported_at', 'tracking_number', 'barangay', 'status'];
        $query->orderBy(in_array($sort, $sortable, true) ? $sort : 'reported_at', $direction);

        $reports = $query->paginate(12)->withQueryString();

        $barangays = Incident::query()
            ->whereIn('status', self::VALID_STATUSES)
            ->whereHas('currentAssignments', fn ($q) => $q->where('agency_id', $agencyId))
            ->whereNotNull('barangay')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay');

        return view('agency.archived_reports.index', compact('reports', 'barangays'));
    }

    public function show(Request $request, Incident $incident): View
    {
        $this->authorizeAccess($request, $incident);

        $incident->load(['incidentType', 'incidentDocuments', 'evidence', 'statusUpdates']);

        return view('agency.archived_reports.show', compact('incident'));
    }

    private function authorizeAccess(Request $request, Incident $incident): void
    {
        $agencyId = $request->user()?->agency_id;
        abort_if(! $agencyId, 403, 'No agency is associated with this account.');

        abort_unless(in_array(
            is_object($incident->status) ? $incident->status->value : $incident->status,
            self::VALID_STATUSES,
            true
        ), 404);

        $hasAssignment = $incident->currentAssignments()
            ->where('agency_id', $agencyId)
            ->exists();

        abort_if(! $hasAssignment, 403, 'This report is not associated with your agency.');
    }
}

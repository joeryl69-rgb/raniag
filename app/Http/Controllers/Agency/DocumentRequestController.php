<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\StoreDocumentRequestRequest;
use App\Models\DocumentRequest;
use App\Models\Incident;
use App\Models\SystemNotification;
use App\Services\DocumentRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentRequestController extends Controller
{
    public function __construct(
        private readonly DocumentRequestService $documentRequests,
    ) {}

    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;
        abort_if(! $agencyId, 403, 'No agency associated with this account.');

        $query = DocumentRequest::query()
            ->with(['incident'])
            ->where('requesting_agency_id', $agencyId)
            ->orderByDesc('created_at');

        $status = $request->input('status');
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $requestType = $request->input('request_type');
        if ($requestType && $requestType !== 'all') {
            $query->where('request_type', $requestType);
        }

        $documentRequests = $query->paginate(10)->appends($request->query());

        // Mark ALL unread document_request notifications for this agency/user as read.
        // Previously this only covered document_request_ids on the current paginated page
        // (10 per page), so notifications for older requests on later pages never cleared,
        // and the badge could stay stuck or reappear inconsistently with what was on screen.
        SystemNotification::query()
            ->whereNull('read_at')
            ->where('type', 'document_request')
            ->where(function ($q) use ($agencyId, $request) {
                $q->where('user_id', $request->user()->id)
                    ->orWhere(function ($q2) use ($agencyId) {
                        $q2->whereNull('user_id')->where('data->agency_id', $agencyId);
                    });
            })
            ->update(['read_at' => now()]);

        $requestedIncidentIds = DocumentRequest::query()
            ->where('requesting_agency_id', $agencyId)
            ->whereIn('status', ['pending', 'approved', 'sent'])
            ->pluck('incident_id');

        $eligibleIncidents = Incident::query()
            ->whereIn('status', ['resolved', 'closed'])
            ->whereHas('currentAssignments', fn ($q) => $q->where('agency_id', $agencyId))
            ->whereNotIn('id', $requestedIncidentIds)
            ->orderByDesc('created_at')
            ->get(['id', 'tracking_number']);

        return view('agency.document_requests.index', compact('documentRequests', 'eligibleIncidents'));
    }

    public function store(StoreDocumentRequestRequest $request, int $incident): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        $record = Incident::query()->findOrFail($incident);

        // Agency must have an assignment on this incident to request documents
        $agencyId = $request->user()->agency_id;
        $hasAnyAssignment = $record->currentAssignments()
            ->where('agency_id', $agencyId)
            ->exists();

        abort_if(
            ! $hasAnyAssignment,
            403,
            'Your agency is not assigned to this incident and cannot request documents.'
        );

        $requestModel = $this->documentRequests->createPendingRequest(
            incident: $record,
            requestingAgencyId: $request->user()->agency_id,
            requestedBy: $request->user(),
            requestType: $data['request_type'],
            requestNote: $data['request_note'] ?? null,
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Printable document request submitted successfully.',
                'document_request' => $requestModel,
            ], 201);
        }

        return redirect()
            ->route('agency.incidents.show', $incident)
            ->with('success', 'Printable copy requested. Please wait for admin approval.');
    }
}
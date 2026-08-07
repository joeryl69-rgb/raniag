<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\StoreDocumentRequestRequest;
use App\Models\DocumentRequest;
use App\Models\Incident;
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
        if ($status === 'archived') {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }
        }

        $requestType = $request->input('request_type');
        if ($requestType && $requestType !== 'all') {
            $query->where('request_type', $requestType);
        }

        $documentRequests = $query->paginate(10)->appends($request->query());

        $requestedIncidentIds = DocumentRequest::query()
            ->where('requesting_agency_id', $agencyId)
            ->whereIn('status', ['pending', 'approved', 'sent'])
            ->pluck('incident_id');

        $eligibleIncidents = Incident::query()
            ->whereIn('status', ['resolved', 'closed'])
            ->whereHas('currentAssignments', fn ($q) => $q->where('agency_id', $agencyId))
            ->whereNotIn('id', $requestedIncidentIds)
            ->with('incidentDocuments:id,incident_id,document_type')
            ->orderByDesc('created_at')
            ->get(['id', 'tracking_number']);

        // Map of incident_id => {tracking, missing[]}, so the UI can warn the
        // agency (by tracking number, not internal id) before an incomplete submit.
        $incompleteIncidents = $eligibleIncidents
            ->mapWithKeys(function ($inc) {
                $missing = $inc->missingRequiredDocumentTypes();

                return $missing
                    ? [$inc->id => ['tracking' => $inc->tracking_number, 'missing' => $missing]]
                    : [];
            })
            ->all();

        // incident_id => {type_value => bool}, so the section picker can grey
        // out/disable a document type the moment it's not available yet.
        $documentAvailability = $eligibleIncidents
            ->mapWithKeys(fn ($inc) => [$inc->id => $inc->documentAvailability()])
            ->all();

        return view('agency.document_requests.index', compact('documentRequests', 'eligibleIncidents', 'incompleteIncidents', 'documentAvailability'));
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

        if ($error = $this->documentRequests->assertSectionsAvailable($record, $data['requested_sections'] ?? null)) {
            abort(422, $error.' Please wait until it is on file, or submit without selecting it.');
        }

        $requestModel = $this->documentRequests->createPendingRequest(
            incident: $record,
            requestingAgencyId: $request->user()->agency_id,
            requestedBy: $request->user(),
            requestType: $data['request_type'],
            requestNote: $data['request_note'] ?? null,
            requestedSections: $data['requested_sections'] ?? null,
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

    public function storeBulk(Request $request): RedirectResponse|JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        abort_if(! $agencyId, 403, 'No agency associated with this account.');

        $data = $request->validate([
            'incident_ids' => ['required', 'array', 'min:1'],
            'incident_ids.*' => ['integer', 'exists:incidents,id'],
            'request_note' => ['nullable', 'string', 'max:1000'],
            'requested_sections' => ['nullable', 'array'],
            'requested_sections.*' => ['string', 'in:incident_details,narrative,resolutions,evidence_photos,status_timeline,call_taker_form,dispatch_form,narrative_report,endorsement_sheet'],
        ]);

        $incidents = Incident::query()
            ->whereIn('id', $data['incident_ids'])
            ->whereIn('status', ['resolved', 'closed'])
            ->whereHas('currentAssignments', fn ($q) => $q->where('agency_id', $agencyId))
            ->get();

        abort_if($incidents->isEmpty(), 422, 'None of the selected reports are eligible for a printable request.');

        $unavailable = $incidents
            ->map(fn ($incident) => $this->documentRequests->assertSectionsAvailable($incident, $data['requested_sections'] ?? null))
            ->filter();

        abort_if(
            $unavailable->isNotEmpty(),
            422,
            $unavailable->join(' ').' Please wait until these are on file, or submit without selecting them.'
        );

        foreach ($incidents as $incident) {
            $this->documentRequests->createPendingRequest(
                incident: $incident,
                requestingAgencyId: $agencyId,
                requestedBy: $request->user(),
                requestType: 'bulk',
                requestNote: $data['request_note'] ?? null,
                requestedSections: $data['requested_sections'] ?? null,
            );
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => count($incidents).' printable document requests submitted.'], 201);
        }

        return redirect()
            ->route('agency.document_requests.index')
            ->with('success', count($incidents).' printable copies requested. Please wait for admin approval.');
    }

    public function archive(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        abort_if($documentRequest->requesting_agency_id !== $request->user()->agency_id, 403);

        $documentRequest->update(['archived_at' => now()]);

        return back()->with('success', 'Document request moved to archive.');
    }

    public function unarchive(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        abort_if($documentRequest->requesting_agency_id !== $request->user()->agency_id, 403);

        $documentRequest->update(['archived_at' => null]);

        return back()->with('success', 'Document request restored from archive.');
    }
}
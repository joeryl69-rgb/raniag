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

        $documentRequests = DocumentRequest::query()
            ->with(['incident'])
            ->where('requesting_agency_id', $agencyId)
            ->orderByDesc('created_at')
            ->get();

        // Mark any document-request notifications for this agency as read when viewing the list
        $docIds = $documentRequests->pluck('id')->all();
        if (! empty($docIds)) {
            SystemNotification::query()
                ->whereNull('read_at')
                ->where('type', 'document_request')
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.document_request_id')) IN (".implode(',', array_map('intval', $docIds)).')')
                ->update(['read_at' => now()]);
        }

        return view('agency.document_requests.index', compact('documentRequests'));
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

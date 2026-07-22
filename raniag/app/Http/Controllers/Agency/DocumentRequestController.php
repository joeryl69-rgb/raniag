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

        // Mark document-request notifications visible to this agency user as read when viewing the list.
        // Scoped so it never touches the admin-only broadcast row for the same request.
        $docIds = $documentRequests->pluck('id')->all();
        if (! empty($docIds)) {
            $user = $request->user();
            SystemNotification::query()
                ->whereNull('read_at')
                ->where('type', 'document_request')
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.document_request_id')) IN (".implode(',', array_map('intval', $docIds)).')')
                ->where(function ($q) use ($user, $agencyId) {
                    $q->where('user_id', $user->id)
                        ->orWhere(function ($q2) use ($agencyId) {
                            $q2->whereNull('user_id')
                                ->where('data->agency_id', $agencyId)
                                ->where(function ($q3) {
                                    $q3->whereNull('data->audience')
                                        ->orWhere('data->audience', '!=', 'admin');
                                });
                        });
                })
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

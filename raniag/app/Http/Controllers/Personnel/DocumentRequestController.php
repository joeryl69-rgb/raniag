<?php

namespace App\Http\Controllers\Personnel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\StoreDocumentRequestRequest;
use App\Models\Incident;
use App\Services\DocumentRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class DocumentRequestController extends Controller
{
    public function __construct(
        private readonly DocumentRequestService $documentRequests,
    ) {}

    public function store(StoreDocumentRequestRequest $request, int $incident): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        $record = Incident::query()->findOrFail($incident);

        $personnelId = $request->user()->id;
        $hasAnyAssignment = $record->currentAssignments()
            ->where('assigned_to', $personnelId)
            ->exists();

        abort_if(
            ! $hasAnyAssignment,
            403,
            'Your personnel account is not assigned to this incident and cannot request documents.'
        );

        $requestModel = $this->documentRequests->createPendingRequest(
            incident: $record,
            requestingAgencyId: null,
            requestedBy: $request->user(),
            requestType: $data['request_type'],
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Printable copy request submitted successfully.',
                'document_request' => $requestModel,
            ], 201);
        }

        return redirect()
            ->route('personnel.incidents.show', $incident)
            ->with('success', 'Printable copy requested. Please wait for admin approval.');
    }
}

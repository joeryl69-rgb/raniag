<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveDocumentRequestRequest;
use App\Models\DocumentRequest;
use App\Services\PrintableReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintableReportRequestController extends Controller
{
    public function __construct(
        private readonly PrintableReportService $printableReportService,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $query = DocumentRequest::query()
            ->with(['incident', 'requestingAgency', 'requestedByUser'])
            ->orderByDesc('created_at');

        // status filter:
        // - omitted / 0 / empty: pending only
        // - status=all: no filtering
        // - otherwise exact match
        $status = $request->input('status');

        if ($status === null || $status === '' || $status === '0') {
            $query->where('status', 'pending');
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $query->get(),
            ]);
        }

        $documentRequests = $query->paginate(10)->appends($request->query());

        return view('admin.document_requests.index', [
            'documentRequests' => $documentRequests,
        ]);
    }

    public function approve(ApproveDocumentRequestRequest $request, DocumentRequest $documentRequest): RedirectResponse|JsonResponse
    {
        $result = $this->printableReportService->approveAndGenerate(
            documentRequest: $documentRequest,
            admin: $request->user(),
            adminComment: $request->validated('admin_comment'),
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Printable request approved and generated.',
                'document_request' => $result->fresh(),
            ]);
        }

        return redirect()
            ->route('admin.document_requests.index')
            ->with('success', 'Printable request approved and generated.');
    }

    public function reject(ApproveDocumentRequestRequest $request, DocumentRequest $documentRequest): RedirectResponse|JsonResponse
    {
        $comment = $request->validated('admin_comment');

        $documentRequest->update([
            'status' => 'rejected',
            'admin_comment' => $comment,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Printable request rejected.',
                'document_request' => $documentRequest->fresh(),
            ]);
        }

        return redirect()
            ->route('admin.document_requests.index')
            ->with('success', 'Printable request rejected.');
    }
}

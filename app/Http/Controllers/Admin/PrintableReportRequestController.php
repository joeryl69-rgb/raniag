<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveDocumentRequestRequest;
use App\Models\DocumentRequest;
use App\Services\PrintableReportService;
use App\Support\FilterOptions;
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
            ->with(['incident', 'requestingAgency', 'requestedByUser']);

        // status filter:
        // - omitted / 0 / empty: pending only
        // - status=all: no filtering
        // - otherwise exact match
        $status = $request->input('status');
        $focusId = $request->integer('request');

        if ($status === null || $status === '' || $status === '0') {
            $query->where(function ($inner) use ($focusId) {
                $inner->where('status', 'pending');
                if ($focusId) {
                    $inner->orWhere('id', $focusId);
                }
            });
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $filters = \App\Support\Filters::fromRequest($request);

        $term = trim((string) ($filters['q'] ?? ''));
        if ($term !== '') {
            $query->where(function ($w) use ($term) {
                $w->where('request_note', 'like', "%{$term}%")
                    ->orWhereHas('incident', fn ($q) => $q->where('tracking_number', 'like', "%{$term}%"))
                    ->orWhereHas('requestingAgency', fn ($q) => $q->where('name', 'like', "%{$term}%"));
            });
        }

        \App\Support\Filters::dateRange($query, 'created_at', $filters);

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $query->get(),
            ]);
        }

        $sort = $request->string('sort')->value() ?: 'created_at';
        $direction = $request->string('direction')->value() === 'asc' ? 'asc' : 'desc';
        $sortable = ['created_at', 'status', 'request_type'];
        if ($focusId) {
            $query->orderByRaw('case when id = ? then 0 else 1 end', [$focusId]);
        }
        $documentRequests = $query
            ->orderBy(in_array($sort, $sortable, true) ? $sort : 'created_at', $direction)
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $statusOptions = FilterOptions::present([
            'sent' => 'Emailed',
            'approved' => 'PDF ready',
            'failed' => 'Email failed',
            'rejected' => 'Rejected',
        ], DocumentRequest::query()->distinct()->pluck('status'), $request->input('status'));

        return view('admin.document_requests.index', [
            'documentRequests' => $documentRequests,
            'statusOptions' => $statusOptions,
        ]);
    }

    public function approve(ApproveDocumentRequestRequest $request, DocumentRequest $documentRequest): RedirectResponse|JsonResponse
    {
        abort_unless($documentRequest->status === 'pending', 422, 'This request is no longer pending.');

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

        $deliveryStatus = (string) $result->status;
        [$title, $message, $tone] = match ($deliveryStatus) {
            'sent' => [
                'Request approved and emailed',
                'The PDF is ready, and the requesting office has been emailed.',
                'success',
            ],
            'failed' => [
                'PDF generated; email delivery failed',
                'The request was approved and the PDF is ready, but the email could not be sent. The request details show the delivery error.',
                'warning',
            ],
            default => [
                'Request approved',
                'The request was approved and the PDF is ready for the requesting office.',
                'success',
            ],
        };

        return redirect()
            ->route('admin.document_requests.index')
            ->with('document_request_success', [
                'tone' => $tone,
                'title' => $title,
                'message' => $message,
                'tracking_number' => $result->incident?->tracking_number,
            ]);
    }

    public function reject(ApproveDocumentRequestRequest $request, DocumentRequest $documentRequest): RedirectResponse|JsonResponse
    {
        abort_unless($documentRequest->status === 'pending', 422, 'This request is no longer pending.');

        $comment = $request->validated('admin_comment');
        if ($comment === null || trim($comment) === '') {
            $comment = 'Rejected by admin';
        }

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
            ->with('document_request_success', [
                'tone' => 'warning',
                'title' => 'Request rejected',
                'message' => 'The printable document request was rejected and the requesting office can see the decision.',
                'tracking_number' => $documentRequest->incident?->tracking_number,
            ]);
    }
}

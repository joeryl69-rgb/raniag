<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveDocumentRequestRequest;
use App\Models\DocumentRequest;
use App\Models\SystemNotification;
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

        // Mark ALL unread document_request notifications visible to this admin as read.
        // Previously this only covered document_request_ids present on the current
        // status-filtered/paginated page (default filter = pending). That meant:
        //  - approving/rejecting moves a request out of the default "pending" view, so the
        //    "approved"/"rejected" notification for it was never marked read -> the sidebar
        //    badge kept counting it even while the pending list showed nothing.
        //  - merely opening the page marked the on-screen pending items read immediately,
        //    before any action was taken, hiding the badge too early.
        // Scoping to "all notifications addressed to this admin" instead of "notifications
        // for whatever happens to be on screen" keeps the badge consistent with what the
        // admin has actually opened this section to check.
        try {
            SystemNotification::query()
                ->whereNull('read_at')
                ->where('type', 'document_request')
                ->where(function ($q) {
                    $q->whereNull('user_id')->orWhere('user_id', auth()->id());
                })
                ->update(['read_at' => now()]);
        } catch (\Throwable $e) {
            // best-effort: don't block view if DB JSON functions are missing
        }

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

        // Notify requesting agency via system notification so their sidebar badge appears
        try {
            $agency = $documentRequest->requestingAgency()->first();
            // Global/admin notification if not exists
            try {
                $existsAdmin = SystemNotification::query()
                    ->where('type', 'document_request')
                    ->where('user_id', null)
                    ->where('data->document_request_id', $documentRequest->id)
                    ->where('data->action', 'rejected')
                    ->exists();

                if (! $existsAdmin) {
                    SystemNotification::create([
                        'user_id' => null,
                        'incident_id' => $documentRequest->incident_id,
                        'type' => 'document_request',
                        'title' => 'Printable Request Rejected',
                        'message' => "Printable request #{$documentRequest->id} for {$documentRequest->incident->tracking_number} has been rejected.",
                        'channel' => NotificationChannel::Database->value,
                        'data' => [
                            'incident_id' => $documentRequest->incident_id,
                            'agency_id' => $agency?->id,
                            'document_request_id' => $documentRequest->id,
                            'action' => 'rejected',
                        ],
                    ]);
                }
            } catch (\Throwable $e) {
                // ignore
            }

            // Direct notification to requesting user if not exists
            if ($documentRequest->requested_by) {
                try {
                    $existsUser = SystemNotification::query()
                        ->where('type', 'document_request')
                        ->where('user_id', $documentRequest->requested_by)
                        ->where('data->document_request_id', $documentRequest->id)
                        ->where('data->action', 'rejected')
                        ->exists();

                    if (! $existsUser) {
                        SystemNotification::create([
                            'user_id' => $documentRequest->requested_by,
                            'incident_id' => $documentRequest->incident_id,
                            'type' => 'document_request',
                            'title' => 'Your Printable Request was Rejected',
                            'message' => "Your printable request #{$documentRequest->id} for {$documentRequest->incident->tracking_number} has been rejected.",
                            'channel' => NotificationChannel::Database->value,
                            'data' => [
                                'incident_id' => $documentRequest->incident_id,
                                'agency_id' => $agency?->id,
                                'document_request_id' => $documentRequest->id,
                                'action' => 'rejected',
                            ],
                        ]);
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        } catch (\Throwable $e) {
            // suppression: creating notification is best-effort and should not block the admin flow
        }

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
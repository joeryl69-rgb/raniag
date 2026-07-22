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

        $documentRequests = $query->get();

        // Mark related document_request notifications as read for admin when viewing the list.
        // Scoped to rows meant for admins (own or global) so the requester's personal
        // notification isn't silently marked read before they've seen it.
        $docIds = $documentRequests->pluck('id')->all();
        if (! empty($docIds)) {
            try {
                $adminId = $request->user()->id;
                SystemNotification::query()
                    ->whereNull('read_at')
                    ->where('type', 'document_request')
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.document_request_id')) IN (".implode(',', array_map('intval', $docIds)).')')
                    ->where(function ($q) use ($adminId) {
                        $q->where('user_id', $adminId)->orWhereNull('user_id');
                    })
                    ->update(['read_at' => now()]);
            } catch (\Throwable $e) {
                // best-effort: don't block view if DB JSON functions are missing
            }
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
                    ->whereNull('user_id')
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.document_request_id')) = ?", [$documentRequest->id])
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.action')) = 'rejected'")
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
                            'audience' => 'admin',
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
                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.document_request_id')) = ?", [$documentRequest->id])
                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.action')) = 'rejected'")
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
                                'audience' => 'requester',
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

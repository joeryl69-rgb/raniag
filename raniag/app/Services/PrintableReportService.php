<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Mail\DocumentRequestApprovedMail;
use App\Models\DocumentRequest;
use App\Models\Incident;
use App\Models\SystemNotification;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PrintableReportService
{
    public function approveAndGenerate(
        DocumentRequest $documentRequest,
        User $admin,
        ?string $adminComment = null,
    ): DocumentRequest {
        $incident = $documentRequest->incident()->firstOrFail();
        $requestingAgency = $documentRequest->requestingAgency()->first();

        // Generate a single-incident PDF
        $pdf = Pdf::loadView('admin.reports.single_pdf', [
            'incident' => $incident->load(['incidentType', 'agency', 'evidence', 'statusUpdates', 'resolutions']),
            'tracking_number' => $incident->tracking_number,
            'generated_at' => now(),
        ]);

        $filename = 'raniag-document-'.$incident->tracking_number.'-'.$documentRequest->id.'.pdf';
        $path = 'document_requests/'.$filename;

        Storage::disk('public')->put($path, $pdf->output());

        $documentRequest->update([
            'status' => 'approved',
            'admin_comment' => $adminComment,
            'generated_path' => $path,
            'generated_at' => now(),
        ]);

        // Notify globally (admins) and directly to the requesting user
        // Global admin notification (create only if not existing for this document_request approval)
        try {
            $existsAdmin = SystemNotification::query()
                ->where('type', 'document_request')
                ->whereNull('user_id')
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.document_request_id')) = ?", [$documentRequest->id])
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.action')) = 'approved'")
                ->exists();

            if (! $existsAdmin) {
                SystemNotification::create([
                    'user_id' => null,
                    'incident_id' => $incident->id,
                    'type' => 'document_request',
                    'title' => 'Printable Report Approved',
                    'message' => "Printable request for {$incident->tracking_number} has been approved. Open this notification to view the report.",
                    'channel' => NotificationChannel::Database->value,
                    'data' => [
                        'incident_id' => $incident->id,
                        'agency_id' => $requestingAgency?->id,
                        'document_request_id' => $documentRequest->id,
                        'action' => 'approved',
                        'audience' => 'admin',
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            // best-effort
        }

        // Also notify the requesting user specifically (if available) — only if not exists
        if ($documentRequest->requested_by) {
            try {
                $existsUser = SystemNotification::query()
                    ->where('type', 'document_request')
                    ->where('user_id', $documentRequest->requested_by)
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.document_request_id')) = ?", [$documentRequest->id])
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.action')) = 'approved'")
                    ->exists();

                if (! $existsUser) {
                    SystemNotification::create([
                        'user_id' => $documentRequest->requested_by,
                        'incident_id' => $incident->id,
                        'type' => 'document_request',
                        'title' => 'Your Printable Request was Approved',
                        'message' => "Your printable request #{$documentRequest->id} for {$incident->tracking_number} has been approved.",
                        'channel' => NotificationChannel::Database->value,
                        'data' => [
                            'incident_id' => $incident->id,
                            'agency_id' => $requestingAgency?->id,
                            'document_request_id' => $documentRequest->id,
                            'action' => 'approved',
                            'audience' => 'requester',
                        ],
                    ]);
                }
            } catch (\Throwable $e) {
                // best-effort
            }
        }

        // Delivery: send the approved PDF to requesting agency email.
        // Panel requirement says SMS alerts exist; for document delivery we use email here.
        $requestingAgency = $documentRequest->requestingAgency()->first();
        $email = $requestingAgency?->email;

        if ($email) {
            try {
                Mail::to($email)->send(
                    new DocumentRequestApprovedMail($documentRequest->fresh())
                );

                $documentRequest->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'failed_reason' => null,
                ]);
            } catch (\Throwable $e) {
                $documentRequest->update([
                    'status' => 'failed',
                    'failed_reason' => $e->getMessage(),
                ]);
            }
        } else {
            // If there is no requesting agency email, keep the approval state and do not fail the request.
            $documentRequest->update([
                'status' => 'approved',
                'failed_reason' => null,
            ]);
        }

        return $documentRequest->fresh();
    }
}

<?php

namespace App\Services;

use App\Mail\DocumentRequestApprovedMail;
use App\Models\DocumentRequest;
use App\Models\Incident;
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

        $allSections = [
            'incident_details', 'narrative', 'resolutions', 'status_timeline',
            'evidence_photos', 'call_taker_form', 'dispatch_form', 'narrative_report', 'endorsement_sheet',
        ];
        $requestedSections = $documentRequest->requested_sections;
        $sections = ! empty($requestedSections) ? $requestedSections : $allSections;

        // Generate a single-incident PDF
        $pdf = Pdf::loadView('admin.reports.single_pdf', [
            'incident' => $incident->load(['incidentType', 'agency', 'evidence', 'statusUpdates', 'resolutions', 'incidentDocuments']),
            'tracking_number' => $incident->tracking_number,
            'generated_at' => now(),
            'sections' => $sections,
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

<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Models\DocumentRequest;
use App\Models\Incident;
use App\Models\SystemNotification;
use App\Models\User;

class DocumentRequestService
{
    public function createPendingRequest(
        Incident $incident,
        ?int $requestingAgencyId,
        User $requestedBy,
        string $requestType,
        ?string $requestNote = null,
    ): DocumentRequest {
        $dr = DocumentRequest::create([
            'incident_id' => $incident->id,
            'requesting_agency_id' => $requestingAgencyId,
            'requested_by' => $requestedBy->id,
            'request_type' => $requestType,
            'request_note' => $requestNote,
            'status' => 'pending',
        ]);

        // Create a system notification for administrators so they see new requests
        try {
            $existsAdmin = SystemNotification::query()
                ->where('type', 'document_request')
                ->whereNull('user_id')
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.document_request_id')) = ?", [$dr->id])
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.action')) = 'submitted'")
                ->exists();

            if (! $existsAdmin) {
                SystemNotification::create([
                    'user_id' => null,
                    'incident_id' => $incident->id,
                    'type' => 'document_request',
                    'title' => 'Printable Request Submitted',
                    'message' => "Printable request #{$dr->id} submitted by {$requestedBy->name} for {$incident->tracking_number}.",
                    'channel' => NotificationChannel::Database->value,
                    'data' => [
                        'incident_id' => $incident->id,
                        'agency_id' => $requestingAgencyId,
                        'document_request_id' => $dr->id,
                        'action' => 'submitted',
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            // best-effort: do not fail request creation if notification can't be created
        }

        // Notify requesting user specifically (if available) but only if not already present
        if ($dr->requested_by) {
            try {
                $existsUser = SystemNotification::query()
                    ->where('type', 'document_request')
                    ->where('user_id', $dr->requested_by)
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.document_request_id')) = ?", [$dr->id])
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.action')) = 'submitted'")
                    ->exists();

                if (! $existsUser) {
                    SystemNotification::create([
                        'user_id' => $dr->requested_by,
                        'incident_id' => $incident->id,
                        'type' => 'document_request',
                        'title' => 'Your Printable Request was Submitted',
                        'message' => "Your printable request #{$dr->id} for {$incident->tracking_number} has been submitted.",
                        'channel' => NotificationChannel::Database->value,
                        'data' => [
                            'incident_id' => $incident->id,
                            'agency_id' => $requestingAgencyId,
                            'document_request_id' => $dr->id,
                            'action' => 'submitted',
                        ],
                    ]);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return $dr;
    }
}

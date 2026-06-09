<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\SmsLogStatus;
use App\Models\Assignment;
use App\Models\Incident;
use App\Models\Resolution;
use App\Models\SmsLog;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function notifyAgencyAssigned(Assignment $assignment): void
    {
        $incident = $assignment->incident;
        $agency = $assignment->agency;

        if (!$agency->phone) {
            return;
        }

        $message = "RANIAG Alert: New incident assigned [{$incident->tracking_number}]. Check system for details.";

        $this->sendSms(
            recipientPhone: $agency->phone,
            message: $message,
            incident: $incident,
        );

        SystemNotification::create([
            'user_id' => null,
            'incident_id' => $incident->id,
            'type' => 'assignment',
            'title' => 'New Assignment',
            'message' => "New incident assigned to {$agency->name}: {$incident->tracking_number}",
            'channel' => NotificationChannel::Database->value,
            'data' => [
                'incident_id' => $incident->id,
                'agency_id' => $agency->id,
            ],
        ]);
    }

    public function notifyAgencyStatusRequest(Assignment $assignment, string $message): void
    {
        $agency = $assignment->agency;

        if ($agency->phone) {
            $this->sendSms(
                recipientPhone: $agency->phone,
                message: $message,
                incident: $assignment->incident,
            );
        }
    }

    public function notifyAdminResolutionSubmitted(Resolution $resolution): void
    {
        $incident = $resolution->incident;
        $adminUsers = User::where('role', 'administrator')->where('is_active', true)->get();

        foreach ($adminUsers as $admin) {
            if ($admin->phone) {
                $message = "RANIAG Alert: Resolution submitted for [{$incident->tracking_number}]. Review required.";

                $this->sendSms(
                    recipientPhone: $admin->phone,
                    message: $message,
                    incident: $incident,
                    user: $admin,
                );
            }

            SystemNotification::create([
                'user_id' => $admin->id,
                'incident_id' => $incident->id,
                'type' => 'resolution',
                'title' => 'Resolution Submitted',
                'message' => "Resolution submitted for incident {$incident->tracking_number}",
                'channel' => NotificationChannel::Database->value,
                'data' => [
                    'incident_id' => $incident->id,
                    'resolution_id' => $resolution->id,
                ],
            ]);
        }
    }

    public function notifyPublicStatusUpdate(Incident $incident, string $updateMessage): void
    {
        SystemNotification::create([
            'user_id' => null,
            'incident_id' => $incident->id,
            'type' => 'status_update',
            'title' => 'Status Update',
            'message' => $updateMessage,
            'channel' => NotificationChannel::Database->value,
            'data' => [
                'incident_id' => $incident->id,
                'status' => $incident->status->value,
            ],
        ]);
    }

    private function sendSms(
        string $recipientPhone,
        string $message,
        Incident $incident,
        ?User $user = null,
    ): void {
        DB::transaction(function () use ($recipientPhone, $message, $incident, $user) {
            $smsLog = SmsLog::create([
                'incident_id' => $incident->id,
                'user_id' => $user?->id,
                'recipient_phone' => $recipientPhone,
                'message' => $message,
                'status' => SmsLogStatus::Pending->value,
                'provider' => config('services.sms.provider', 'twilio'),
                'sent_at' => null,
                'failed_at' => null,
            ]);

            try {
                $this->dispatchSms($smsLog);
            } catch (\Exception $e) {
                $smsLog->update([
                    'status' => SmsLogStatus::Failed->value,
                    'failed_at' => now(),
                    'provider_response' => [
                        'error' => $e->getMessage(),
                    ],
                ]);
            }
        });
    }

    private function dispatchSms(SmsLog $smsLog): void
    {
        // TODO: Implement actual SMS sending via Twilio or configured provider
        // For now, mark as sent immediately (placeholder)
        $smsLog->update([
            'status' => SmsLogStatus::Sent->value,
            'sent_at' => now(),
            'provider_message_id' => 'msg_' . uniqid(),
            'provider_response' => [
                'status' => 'queued',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}

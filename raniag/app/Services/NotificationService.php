<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\SmsLogStatus;
use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\Incident;
use App\Models\Resolution;
use App\Models\SmsLog;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class NotificationService
{
    public function notifyAdminNewIncident(Incident $incident): void
    {
        $admins = User::where('role', UserRole::Administrator)->where('is_active', true)->get();
        foreach ($admins as $admin) {
            if ($admin->phone) {
                $message = "RANIAG Alert: New incident submitted [Tracking: {$incident->tracking_number}]. Please review and assign.";
                $this->sendSms(
                    recipientPhone: $admin->phone,
                    message: $message,
                    incident: $incident,
                    user: $admin,
                );
            }
        }
    }

    public function notifyReporterStatusUpdate(Incident $incident, string $updateMessage): void
    {
        if (! $incident->is_anonymous && $incident->reporter_phone) {
            $message = "RANIAG Alert: Your report [Tracking: {$incident->tracking_number}] status is updated: {$updateMessage}";
            $this->sendSms(
                recipientPhone: $incident->reporter_phone,
                message: $message,
                incident: $incident,
            );
        }
    }

    public function notifyAgencyAssigned(Assignment $assignment): void
    {
        $incident = $assignment->incident;
        $agency = $assignment->agency;

        if (! $agency?->phone) {
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

    public function notifyPersonnelAssigned(Assignment $assignment): void
    {
        $incident = $assignment->incident;
        $personnel = $assignment->assignee;

        if (! $personnel || ! $personnel->phone) {
            return;
        }

        $message = "RANIAG Alert: New incident assigned [{$incident->tracking_number}]. Check system for details.";

        $this->sendSms(
            recipientPhone: $personnel->phone,
            message: $message,
            incident: $incident,
            user: $personnel,
        );

        SystemNotification::create([
            'user_id' => $personnel->id,
            'incident_id' => $incident->id,
            'type' => 'assignment',
            'title' => 'New Assignment',
            'message' => "New incident assigned to {$personnel->display_title}: {$incident->tracking_number}",
            'channel' => NotificationChannel::Database->value,
            'data' => [
                'incident_id' => $incident->id,
                'assigned_to' => $personnel->id,
            ],
        ]);
    }

    public function notifyAgencyStatusRequest(Assignment $assignment, string $message): void
    {
        $agency = $assignment->agency;
        $incident = $assignment->incident;

        if ($agency->phone) {
            $this->sendSms(
                recipientPhone: $agency->phone,
                message: $message,
                incident: $incident,
            );
        }

        // Also record an in-app notification so this shows up in the agency's bell/badges.
        // Previously this was SMS-only and never appeared in the sidebar.
        SystemNotification::create([
            'user_id' => null,
            'incident_id' => $incident->id,
            'type' => 'status_request',
            'title' => 'Status Update Requested',
            'message' => $message,
            'channel' => NotificationChannel::Database->value,
            'data' => [
                'incident_id' => $incident->id,
                'agency_id' => $agency->id,
                'audience' => 'agency',
            ],
        ]);
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
                'provider' => config('services.sms.provider', env('SMS_PROVIDER', 'textbee')),
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
        $provider = config('services.sms.provider', env('SMS_PROVIDER', 'textbee'));

        if ($provider === 'textbee') {
            $this->sendViaTextBee($smsLog);
        } elseif ($provider === 'twilio') {
            $this->sendViaTwilio($smsLog);
        } else {
            $this->sendViaPlaceholder($smsLog);
        }
    }

    private function sendViaTextBee(SmsLog $smsLog): void
    {
        try {
            $deviceId = config('services.textbee.device_id');
            $apiKey = config('services.textbee.api_key');

            if (! $deviceId || ! $apiKey) {
                throw new \Exception('TextBee configuration incomplete. Check services.php and .env');
            }

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
            ])->post("https://api.textbee.dev/api/v1/gateway/devices/{$deviceId}/send-sms", [
                'recipients' => [$smsLog->recipient_phone],
                'message' => $smsLog->message,
            ]);

            if ($response->failed()) {
                throw new \Exception('TextBee API response failed: '.$response->body());
            }

            $smsLog->update([
                'status' => SmsLogStatus::Sent->value,
                'sent_at' => now(),
                'provider_message_id' => $response->json('data.messageId') ?? $response->json('messageId') ?? 'textbee_'.uniqid(),
                'provider_response' => [
                    'body' => $response->json(),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            $smsLog->update([
                'status' => SmsLogStatus::Failed->value,
                'failed_at' => now(),
                'provider_response' => [
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);

            Log::error('SMS dispatch via TextBee failed', [
                'sms_log_id' => $smsLog->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendViaTwilio(SmsLog $smsLog): void
    {
        try {
            $accountSid = config('services.twilio.account_sid');
            $authToken = config('services.twilio.auth_token');
            $twilioPhone = config('services.twilio.phone_number');

            if (! $accountSid || ! $authToken || ! $twilioPhone) {
                throw new \Exception('Twilio configuration incomplete. Check .env');
            }

            $twilio = new Client($accountSid, $authToken);

            $message = $twilio->messages->create(
                $smsLog->recipient_phone,
                [
                    'from' => $twilioPhone,
                    'body' => $smsLog->message,
                ]
            );

            $smsLog->update([
                'status' => SmsLogStatus::Sent->value,
                'sent_at' => now(),
                'provider_message_id' => $message->sid,
                'provider_response' => [
                    'status' => $message->status,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            $smsLog->update([
                'status' => SmsLogStatus::Failed->value,
                'failed_at' => now(),
                'provider_response' => [
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);

            Log::error('SMS dispatch failed', [
                'sms_log_id' => $smsLog->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendViaPlaceholder(SmsLog $smsLog): void
    {
        $smsLog->update([
            'status' => SmsLogStatus::Sent->value,
            'sent_at' => now(),
            'provider_message_id' => 'msg_'.uniqid(),
            'provider_response' => [
                'status' => 'queued_placeholder',
                'note' => 'Using placeholder SMS dispatcher. Configure TextBee or Twilio to send real SMS.',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}

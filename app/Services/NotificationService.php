<?php

namespace App\Services;

use App\Enums\SmsLogStatus;
use App\Enums\UserRole;
use App\Jobs\DispatchSmsJob;
use App\Models\Assignment;
use App\Models\DocumentRequest;
use App\Models\Incident;
use App\Models\Notification;
use App\Models\Resolution;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class NotificationService
{
    /**
     * Write an in-app (database-channel) notification for a single user.
     * This is deliberately separate from SMS: SMS is a one-way fire-and-log
     * alert, while these back the bell dropdown/notification center and
     * carry read/unread state. Called alongside, never instead of, the SMS
     * paths below so both channels stay in sync for the same event.
     */
    public function notify(
        User $user,
        string $type,
        string $title,
        string $message,
        ?Incident $incident = null,
        array $data = [],
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'incident_id' => $incident?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data ?: null,
            'channel' => 'database',
        ]);
    }

    /**
     * Same event, fanned out to every active user of a role. Used for
     * "all admins should see this" style alerts (new incident, resolution
     * submitted) where there's no single natural recipient.
     */
    public function notifyRole(UserRole $role, string $type, string $title, string $message, ?Incident $incident = null, array $data = []): void
    {
        User::where('role', $role)->where('is_active', true)->get()
            ->each(fn (User $user) => $this->notify($user, $type, $title, $message, $incident, $data));
    }

    private function fullLocation(Incident $incident): string
    {
        $parts = array_filter([
            $incident->location_address,
            $incident->barangay ? "Brgy. {$incident->barangay}" : null,
        ]);

        return $parts ? implode(', ', $parts) : 'Location not specified';
    }

    private function incidentUrl(Incident $incident, string $area = 'admin'): string
    {
        $base = rtrim(config('app.url'), '/');

        // Matches routes/{admin,agency,personnel}.php: prefix('{area}')->prefix('incidents')->get('/{incident}')
        return "{$base}/{$area}/incidents/{$incident->id}";
    }

    public function notifyAdminNewIncident(Incident $incident): void
    {
        $location = $this->fullLocation($incident);

        $this->notifyRole(
            UserRole::Administrator,
            'incident.new',
            'New incident reported',
            "[{$incident->tracking_number}] at {$location}. Awaiting review and assignment.",
            $incident,
        );

        $admins = User::where('role', UserRole::Administrator)->where('is_active', true)->get();
        foreach ($admins as $admin) {
            if ($admin->phone) {
                $message = "RANIAG Alert: New incident submitted [Tracking: {$incident->tracking_number}] at {$location}. Please review and assign.";
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

        $location = $this->fullLocation($incident);

        if ($agency) {
            $agencyUsers = User::where('role', UserRole::Agency)
                ->where('agency_id', $agency->id)
                ->where('is_active', true)
                ->get();

            foreach ($agencyUsers as $agencyUser) {
                $this->notify(
                    $agencyUser,
                    'incident.assigned',
                    'Incident routed to your agency',
                    "[{$incident->tracking_number}] at {$location}.",
                    $incident,
                );
            }
        }

        if (! $agency?->phone) {
            return;
        }

        $message = "RANIAG Alert: New incident assigned [{$incident->tracking_number}] at {$location}.";

        $this->sendSms(
            recipientPhone: $agency->phone,
            message: $message,
            incident: $incident,
        );
    }

    public function notifyPersonnelAssigned(Assignment $assignment): void
    {
        $incident = $assignment->incident;
        $personnel = $assignment->assignee;

        if (! $personnel) {
            return;
        }

        $location = $this->fullLocation($incident);

        $this->notify(
            $personnel,
            'incident.assigned',
            'Incident assigned to you',
            "[{$incident->tracking_number}] at {$location}.",
            $incident,
        );

        if (! $personnel->phone) {
            return;
        }

        $message = "RANIAG Alert: New incident assigned [{$incident->tracking_number}] at {$location}.";

        $this->sendSms(
            recipientPhone: $personnel->phone,
            message: $message,
            incident: $incident,
            user: $personnel,
        );
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
    }

    public function notifyAdminResolutionSubmitted(Resolution $resolution): void
    {
        $incident = $resolution->incident;

        $this->notifyRole(
            UserRole::Administrator,
            'resolution.submitted',
            'Resolution submitted for review',
            "[{$incident->tracking_number}] has a resolution awaiting your review.",
            $incident,
        );

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
        }
    }

    /**
     * Reached from IncidentController when an admin flags a report as
     * Outside AOR — no SMS pairing exists for this event today, so this is
     * in-app only (visible to the admin who actioned it, for their audit
     * trail in the bell dropdown).
     */
    public function notifyOutsideAor(Incident $incident, User $admin): void
    {
        $this->notify(
            $admin,
            'incident.outside_aor',
            'Incident marked Outside AOR',
            "[{$incident->tracking_number}] was referred outside Pamplona's area of responsibility.",
            $incident,
        );
    }

    public function notifyAdminsNewDocumentRequest(DocumentRequest $documentRequest): void
    {
        $incident = $documentRequest->incident;
        $requesterName = $documentRequest->requestedByUser?->name ?? 'A user';

        $this->notifyRole(
            UserRole::Administrator,
            'document_request.new',
            'New document request',
            "{$requesterName} requested documents for [{$incident->tracking_number}].",
            $incident,
        );
    }

    public function notifyRequesterDocumentReady(DocumentRequest $documentRequest): void
    {
        $requester = $documentRequest->requestedByUser;
        $incident = $documentRequest->incident;

        if (! $requester) {
            return;
        }

        $this->notify(
            $requester,
            'document_request.ready',
            'Document request approved',
            "Your document request for [{$incident->tracking_number}] is ready to download.",
            $incident,
        );
    }

    private function sendSms(
        string $recipientPhone,
        string $message,
        Incident $incident,
        ?User $user = null,
    ): void {
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

        // Dispatched after the enclosing DB transaction commits (afterCommit
        // is the app-wide queue default; see config/queue.php connections),
        // so a slow/unreachable SMS provider never blocks the incident
        // submission or status-change request itself.
        DispatchSmsJob::dispatch($smsLog->id)->afterCommit();
    }

    /**
     * Send a single logged SMS via its configured provider. Public so the
     * queued DispatchSmsJob can invoke it outside the request/transaction
     * that created the SmsLog row.
     */
    public function dispatchSms(SmsLog $smsLog): void
    {
        $provider = config('services.sms.provider', env('SMS_PROVIDER', 'textbee'));

        if ($provider === 'textbee') {
            $this->sendViaTextBee($smsLog);
        } elseif ($provider === 'twilio') {
            $this->sendViaTwilio($smsLog);
        } elseif ($provider === 'philsms') {
            $this->sendViaPhilSms($smsLog);
        } elseif (app()->environment('local')) {
            $this->sendViaPlaceholder($smsLog);
        } else {
            // Refuse to fake a "sent" SMS outside local dev. A misconfigured
            // SMS_PROVIDER in staging/production should surface loudly as a
            // failed log entry, not silently pass as delivered.
            $smsLog->update([
                'status' => SmsLogStatus::Failed->value,
                'failed_at' => now(),
                'provider_response' => [
                    'error' => "Unknown or unconfigured SMS provider '{$provider}' outside local environment; refusing placeholder send.",
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);

            Log::critical('SMS dispatch blocked: no real provider configured outside local env', [
                'sms_log_id' => $smsLog->id,
                'provider' => $provider,
                'environment' => app()->environment(),
            ]);
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

    private function sendViaPhilSms(SmsLog $smsLog): void
    {
        try {
            $apiToken = config('services.philsms.api_token');
            $senderId = config('services.philsms.sender_id', 'PhilSMS');

            if (! $apiToken) {
                throw new \Exception('PhilSMS configuration incomplete. Check services.php and .env (PHILSMS_API_TOKEN).');
            }

            $recipient = $this->normalizePhilippineNumber($smsLog->recipient_phone);

            $response = Http::withToken($apiToken)
                ->acceptJson()
                ->post('https://dashboard.philsms.com/api/v3/sms/send', [
                    'recipient' => $recipient,
                    'sender_id' => $senderId,
                    'type' => 'plain',
                    'message' => $smsLog->message,
                ]);

            if ($response->failed() || ($response->json('status') ?? null) === 'error') {
                throw new \Exception('PhilSMS API response failed: '.$response->body());
            }

            $smsLog->update([
                'status' => SmsLogStatus::Sent->value,
                'sent_at' => now(),
                'provider_message_id' => $response->json('data.uid') ?? 'philsms_'.uniqid(),
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

            Log::error('SMS dispatch via PhilSMS failed', [
                'sms_log_id' => $smsLog->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * PhilSMS expects Philippine numbers in 63XXXXXXXXXX format (no plus,
     * no leading zero). Numbers in the app are typically stored in local
     * 09XXXXXXXXX format, so normalize before sending.
     */
    private function normalizePhilippineNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '0')) {
            return '63'.substr($digits, 1);
        }

        if (str_starts_with($digits, '63')) {
            return $digits;
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '63'.$digits;
        }

        return $digits;
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

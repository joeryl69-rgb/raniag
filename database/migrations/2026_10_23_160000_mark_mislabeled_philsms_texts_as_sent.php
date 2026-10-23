<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const VOICE_LABEL_ERROR = 'PhilSMS routed this alert as a voice call. A text message was requested.';

    public function up(): void
    {
        $logs = DB::table('sms_logs')
            ->where('status', 'failed')
            ->whereNotNull('provider_response')
            ->orderBy('id')
            ->get();

        foreach ($logs as $log) {
            $response = is_array($log->provider_response)
                ? $log->provider_response
                : json_decode((string) $log->provider_response, true);
            if (($response['error'] ?? null) !== self::VOICE_LABEL_ERROR) {
                continue;
            }

            DB::table('sms_logs')->where('id', $log->id)->update([
                'status' => 'sent',
                'sent_at' => $log->failed_at ?? $log->created_at,
                'failed_at' => null,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $logs = DB::table('sms_logs')
            ->where('status', 'sent')
            ->whereNotNull('provider_response')
            ->orderBy('id')
            ->get();

        foreach ($logs as $log) {
            $response = is_array($log->provider_response)
                ? $log->provider_response
                : json_decode((string) $log->provider_response, true);
            if (($response['error'] ?? null) !== self::VOICE_LABEL_ERROR) {
                continue;
            }

            DB::table('sms_logs')->where('id', $log->id)->update([
                'status' => 'failed',
                'failed_at' => $log->sent_at ?? $log->created_at,
                'sent_at' => null,
                'updated_at' => now(),
            ]);
        }
    }
};

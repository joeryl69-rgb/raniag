<?php

namespace App\Console\Commands;

use App\Models\SmsLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SmsHealth extends Command
{
    protected $signature = 'raniag:sms-health';

    protected $description = 'Report SMS configuration and recent delivery results without secrets';

    public function handle(): int
    {
        $token = (string) config('services.philsms.api_token');

        $this->line('env='.app()->environment());
        $this->line('queue='.(string) config('queue.default'));
        $this->line('provider='.(string) config('services.sms.provider'));
        $this->line('token_present='.($token !== '' ? 'yes' : 'no'));
        $this->line('token_length='.strlen($token));
        $this->line('sender_id='.(string) config('services.philsms.sender_id'));
        $this->reportEnvFile();

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(20)
                ->get('https://dashboard.philsms.com/api/v3/balance');
            $json = $response->json() ?? [];
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];
            $this->line('balance_http='.$response->status());
            $this->line('balance_status='.(string) ($json['status'] ?? ''));
            $this->line('balance_message='.$this->redact((string) ($json['message'] ?? '')));
            $this->line('balance_amount='.$this->ascii((string) ($data['remaining_balance'] ?? '')));
        } catch (\Throwable $e) {
            $this->line('balance_error='.$this->redact($e->getMessage()));
        }

        if (Schema::hasTable('sms_logs')) {
            $counts = SmsLog::query()
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            if ($counts->isEmpty()) {
                $this->line('sms_logs=0');
            }

            foreach ($counts as $status => $count) {
                $this->line('sms_'.$status.'='.$count);
            }

            $recent = SmsLog::query()->latest('id')->limit(8)->get();
            $recent->each(function (SmsLog $log): void {
                $phone = preg_replace('/\D+/', '', (string) $log->recipient_phone) ?? '';
                $prefix = str_starts_with($phone, '63') ? '63' : (str_starts_with($phone, '0') ? '0' : 'other');
                $message = (string) $log->message;
                $response = $log->provider_response;
                $detail = '';
                $bodyStatus = '';
                $shape = '';
                if (is_array($response)) {
                    $detail = (string) ($response['error'] ?? ($response['body']['message'] ?? ''));
                    $body = $response['body']['data'] ?? null;
                    if (is_array($body)) {
                        $shape = array_is_list($body)
                            ? 'list:'.count($body)
                            : 'keys:'.implode(',', array_slice(array_keys($body), 0, 8));
                        $record = array_is_list($body) ? ($body[0] ?? []) : $body;
                        if (is_array($record)) {
                            $bodyStatus = (string) ($record['status'] ?? $record['customer_status'] ?? '');
                        }
                    } else {
                        $shape = gettype($body);
                    }
                }
                $this->line(sprintf(
                    'log id=%s status=%s at=%s phone_prefix=%s phone_len=%d msg_len=%d unicode=%s has_url=%s provider_id=%s data=%s body_status=%s detail=%s',
                    $log->id,
                    $log->status?->value ?? $log->getRawOriginal('status'),
                    $log->created_at,
                    $prefix,
                    strlen($phone),
                    strlen($message),
                    preg_match('/[^\x00-\x7F]/', $message) ? 'yes' : 'no',
                    str_contains($message, 'http') ? 'yes' : 'no',
                    $this->redact((string) $log->provider_message_id),
                    $shape,
                    $this->redact($bodyStatus),
                    $this->redact($detail),
                ));
            });

            $latestId = (string) ($recent->first()?->provider_message_id ?? '');
            if ($latestId !== '' && ! str_starts_with($latestId, 'philsms_')) {
                $this->reportRemoteMessage($token, $latestId);
            }
        }

        if (Schema::hasTable('jobs')) {
            $this->line('queued_jobs='.DB::table('jobs')->count());
            $this->line('queued_sms_jobs='.DB::table('jobs')->where('payload', 'like', '%DispatchSmsJob%')->count());
        }

        if (Schema::hasTable('failed_jobs')) {
            $this->line('failed_jobs='.DB::table('failed_jobs')->count());
            $latest = DB::table('failed_jobs')->orderByDesc('id')->first();
            if ($latest) {
                $this->line('latest_failed_at='.(string) ($latest->failed_at ?? ''));
                $this->line('latest_failed='.$this->redact(substr((string) $latest->exception, 0, 400)));
            }
        }

        return self::SUCCESS;
    }

    private function reportRemoteMessage(string $token, string $uid): void
    {
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(20)
                ->get('https://dashboard.philsms.com/api/v3/sms/'.$uid);
            $json = $response->json() ?? [];
            $this->line('remote_http='.$response->status());
            $this->line('remote_status='.$this->redact((string) ($json['status'] ?? '')));
            $this->line('remote_message='.$this->redact((string) ($json['message'] ?? '')));
            $data = $json['data'] ?? null;
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (! is_scalar($value) || in_array($key, ['recipient', 'to', 'phone', 'message'], true)) {
                        continue;
                    }
                    $this->line('remote_'.$key.'='.$this->redact($this->ascii((string) $value)));
                }
            }
        } catch (\Throwable $e) {
            $this->line('remote_error='.$this->redact($e->getMessage()));
        }
    }

    private function ascii(string $value): string
    {
        return preg_replace('/[^\x20-\x7E]/', '', $value) ?? '';
    }

    private function reportEnvFile(): void
    {
        $path = base_path('.env');
        if (! is_file($path)) {
            $this->line('env_file=missing');

            return;
        }

        $wanted = ['APP_ENV', 'QUEUE_CONNECTION', 'SMS_PROVIDER', 'PHILSMS_SENDER_ID'];
        $found = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (! preg_match('/^([A-Z0-9_]+)=(.*)$/', $line, $match)) {
                continue;
            }
            if ($match[1] === 'PHILSMS_API_TOKEN') {
                $value = trim($match[2], " \t\"'");
                $this->line('env_token_present='.($value !== '' ? 'yes' : 'no'));
                $this->line('env_token_length='.strlen($value));
            }
            if (in_array($match[1], $wanted, true)) {
                $found[$match[1]] = trim($match[2], " \t\"'");
            }
        }

        foreach ($wanted as $key) {
            $this->line('env_'.$key.'='.($found[$key] ?? 'missing'));
        }
    }

    private function redact(string $value): string
    {
        $value = preg_replace('/[A-Za-z0-9_\-|]{24,}/', '[redacted]', $value) ?? $value;

        return substr(preg_replace('/\s+/', ' ', $value) ?? $value, 0, 240);
    }
}

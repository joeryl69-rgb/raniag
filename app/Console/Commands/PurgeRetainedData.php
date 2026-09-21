<?php

namespace App\Console\Commands;

use App\Enums\IncidentStatus;
use App\Models\Evidence;
use App\Models\Incident;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeRetainedData extends Command
{
    protected $signature = 'raniag:purge-retained {--dry-run : Report actions without deleting}';

    protected $description = 'Hard-delete soft-deleted incidents and scrub aged closed-incident PII (RA 10173 retention)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $softDeleteDays = (int) config('raniag.retention.soft_delete_days', 90);
        $piiDays = (int) config('raniag.retention.closed_pii_days', 365);
        $softCutoff = now()->subDays($softDeleteDays);
        $piiCutoff = now()->subDays($piiDays);
        $deleted = 0;
        $scrubbed = 0;

        Incident::onlyTrashed()
            ->where('deleted_at', '<=', $softCutoff)
            ->orderBy('id')
            ->chunkById(50, function ($incidents) use ($dryRun, &$deleted) {
                foreach ($incidents as $incident) {
                    $this->line("Purge soft-deleted #{$incident->id} {$incident->tracking_number}");
                    if ($dryRun) {
                        $deleted++;
                        continue;
                    }
                    $incident->evidence()->each(function (Evidence $evidence) {
                        if ($evidence->file_path) {
                            Storage::disk('local')->delete($evidence->file_path);
                        }
                        $evidence->delete();
                    });
                    $incident->forceDelete();
                    $deleted++;
                }
            });

        Incident::query()
            ->whereIn('status', [IncidentStatus::Closed->value, IncidentStatus::Resolved->value])
            ->where(function ($q) use ($piiCutoff) {
                $q->where(function ($q2) use ($piiCutoff) {
                    $q2->whereNotNull('closed_at')->where('closed_at', '<=', $piiCutoff);
                })->orWhere(function ($q2) use ($piiCutoff) {
                    $q2->whereNull('closed_at')
                        ->whereNotNull('resolved_at')
                        ->where('resolved_at', '<=', $piiCutoff);
                });
            })
            ->where(function ($q) {
                $q->whereNotNull('reporter_name')
                    ->orWhereNotNull('reporter_phone')
                    ->orWhereNotNull('reporter_email');
            })
            ->orderBy('id')
            ->chunkById(50, function ($incidents) use ($dryRun, &$scrubbed) {
                foreach ($incidents as $incident) {
                    $this->line("Scrub PII #{$incident->id} {$incident->tracking_number}");
                    if ($dryRun) {
                        $scrubbed++;
                        continue;
                    }
                    $meta = is_array($incident->meta) ? $incident->meta : [];
                    $meta['pii_purged_at'] = now()->toIso8601String();
                    $incident->forceFill([
                        'reporter_name' => null,
                        'reporter_phone' => null,
                        'reporter_email' => null,
                        'meta' => $meta,
                    ])->save();
                    $scrubbed++;
                }
            });

        $this->info("Soft-deleted purged: {$deleted}. PII scrubbed: {$scrubbed}.");

        return self::SUCCESS;
    }
}

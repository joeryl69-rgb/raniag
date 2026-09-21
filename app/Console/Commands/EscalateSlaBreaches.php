<?php

namespace App\Console\Commands;

use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Models\Incident;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class EscalateSlaBreaches extends Command
{
    protected $signature = 'raniag:escalate-sla {--dry-run : List breaches without notifying}';

    protected $description = 'Notify admins when open incidents exceed the SLA target hours';

    public function handle(NotificationService $notifications): int
    {
        $hours = (int) config('raniag.sla_target_hours', 48);
        $cutoff = now()->subHours($hours);
        $dryRun = (bool) $this->option('dry-run');
        $count = 0;

        $open = [
            IncidentStatus::Submitted->value,
            IncidentStatus::Received->value,
            IncidentStatus::Assigned->value,
            IncidentStatus::InProgress->value,
            IncidentStatus::PendingInfo->value,
        ];

        Incident::query()
            ->whereIn('status', $open)
            ->where('reported_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(50, function ($incidents) use ($notifications, $dryRun, $hours, &$count) {
                foreach ($incidents as $incident) {
                    $meta = is_array($incident->meta) ? $incident->meta : [];
                    if (! empty($meta['sla_escalated_at'])) {
                        continue;
                    }

                    $count++;
                    $this->line("SLA breach: {$incident->tracking_number} (reported {$incident->reported_at})");

                    if ($dryRun) {
                        continue;
                    }

                    $notifications->notifyRole(
                        UserRole::Administrator,
                        'incident.sla_breach',
                        'SLA breach',
                        "[{$incident->tracking_number}] still open after {$hours}h. Review and escalate assignment.",
                        $incident,
                        ['sla_hours' => $hours],
                    );

                    $meta['sla_escalated_at'] = now()->toIso8601String();
                    $incident->forceFill(['meta' => $meta])->save();
                }
            });

        $this->info(($dryRun ? 'Would escalate' : 'Escalated').": {$count}");

        return self::SUCCESS;
    }
}

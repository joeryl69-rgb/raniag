<?php

namespace App\Console\Commands;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MonthlyMdrrmoSummary extends Command
{
    protected $signature = 'raniag:monthly-summary {--month= : YYYY-MM (defaults to previous month)}';

    protected $description = 'Generate monthly MDRRMO incident summary PDF and CSV';

    public function handle(): int
    {
        $month = $this->option('month') ?: now()->subMonth()->format('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->error('Invalid --month; use YYYY-MM');

            return self::FAILURE;
        }

        [$year, $mon] = array_map('intval', explode('-', $month));
        $start = now()->setDate($year, $mon, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $incidents = Incident::query()
            ->with('incidentType')
            ->whereBetween('reported_at', [$start, $end])
            ->orderBy('reported_at')
            ->get();

        $byType = $incidents->groupBy(fn ($i) => $i->incidentType?->name ?? 'Unknown')->map->count();
        $byStatus = $incidents->groupBy(fn ($i) => $i->status instanceof IncidentStatus ? $i->status->value : (string) $i->status)->map->count();

        $dir = 'monthly_summaries';
        Storage::disk('local')->makeDirectory($dir);

        $pdf = Pdf::loadView('admin.reports.monthly_summary_pdf', [
            'month' => $month,
            'incidents' => $incidents,
            'byType' => $byType,
            'byStatus' => $byStatus,
            'generatedAt' => now(),
        ]);
        $pdfPath = "{$dir}/raniag-{$month}.pdf";
        Storage::disk('local')->put($pdfPath, $pdf->output());

        $csv = "Tracking,Type,Status,Priority,Barangay,Reported at\n";
        foreach ($incidents as $inc) {
            $csv .= implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', [
                $inc->tracking_number,
                $inc->incidentType?->name,
                $inc->status instanceof IncidentStatus ? $inc->status->value : $inc->status,
                $inc->priority instanceof \BackedEnum ? $inc->priority->value : $inc->priority,
                $inc->barangay,
                optional($inc->reported_at)->toDateTimeString(),
            ]))."\n";
        }
        $csvPath = "{$dir}/raniag-{$month}.csv";
        Storage::disk('local')->put($csvPath, $csv);

        $this->info("PDF: storage/app/{$pdfPath}");
        $this->info("CSV: storage/app/{$csvPath}");

        return self::SUCCESS;
    }
}

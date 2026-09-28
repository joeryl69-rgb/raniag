<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Incident;
use App\Models\IncidentType;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Chart keys the admin is allowed to pick on the "Generate Reports" form.
     * Single source of truth — used for validation AND for the order charts
     * render in on the PDF, so what's picked is exactly (and only) what's
     * generated, in the same order every time.
     */
    private const CHART_KEYS = [
        'open_workload',
        'response_time',
        'where_to_send',
        'agency_load',
        'when_reports_arrived',
    ];

    private const CHART_LABELS = [
        'open_workload' => 'What still needs a team',
        'response_time' => 'How fast the office answered',
        'where_to_send' => 'Where to send people',
        'agency_load' => 'Which office is carrying the cases',
        'when_reports_arrived' => 'When reports came in',
    ];

    public function index(): View
    {
        $incidentTypes = IncidentType::orderBy('name')->get(['id', 'name']);
        $agencies = Agency::orderBy('name')->get(['id', 'name', 'code']);
        $barangays = config('raniag.barangays');

        return view('admin.reports.index', [
            'incidentTypes' => $incidentTypes,
            'agencies' => $agencies,
            'barangays' => $barangays,
            'chartKeys' => self::CHART_KEYS,
            'chartLabels' => self::CHART_LABELS,
        ]);
    }

    public function generate(Request $Request)
    {
        $validated = $this->validateFilters($Request);
        $query = $this->buildFilteredQuery($validated, ['incidentType', 'agency', 'statusUpdates', 'assignments.agency']);
        $incidents = $query->orderByDesc('reported_at')->get();

        $agencyName = null;
        if (! empty($validated['agency_id'])) {
            $agencyName = Agency::find($validated['agency_id'])?->name ?? 'N/A';
        }

        if ($incidents->isEmpty()) {
            return redirect()->route('admin.reports.index')
                ->withInput()
                ->with('warning', 'No incidents found for the selected filters. Please try a different date range or criteria.');
        }

        $resolvedAgencyNames = $incidents->mapWithKeys(
            fn ($incident) => [$incident->id => $this->resolveAgencyName($incident)]
        );

        $pdf = Pdf::loadView('admin.reports.pdf', [
            'incidents' => $incidents,
            'filters' => $validated,
            'agencyName' => $agencyName,
            'resolvedAgencyNames' => $resolvedAgencyNames,
            'generated_at' => now(),
        ]);

        return $pdf->download('raniag-report-'.now()->format('Y-m-d').'.pdf')
            ->cookie('download_token', $Request->input('download_token'), 1, null, null, null, false);
    }

    public function generateExcel(Request $request)
    {
        $validated = $this->validateFilters($request);
        $query = $this->buildFilteredQuery($validated, ['incidentType', 'agency', 'assignments.agency']);
        $incidents = $query->orderByDesc('reported_at')->get();

        if ($incidents->isEmpty()) {
            return redirect()->route('admin.reports.index')
                ->withInput()
                ->with('warning', 'No incidents found for the selected filters. Please try a different date range or criteria.');
        }

        $writer = new \App\Services\SimpleXlsxWriter;
        $writer->setColumnWidths([16, 14, 16, 20, 14, 18]);

        $writer->addRow(['RANIAG Incident Report'], $writer::STYLE_TITLE);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Period: '.$validated['date_from'].' to '.$validated['date_to'].'  |  Generated: '.now()->format('M d, Y h:i A')], $writer::STYLE_SUBTITLE);
        $writer->mergeRow(0, 5);
        $writer->addRow([]);

        $decision = $this->decisionTables($incidents);

        $writer->addRow(['What still needs a team'], $writer::STYLE_SECTION);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Still open', 'Count'], $writer::STYLE_HEADER);
        foreach ($decision['open_workload']['rows'] as $row) {
            $writer->addRow([$row['label'], $row['count']], $writer::STYLE_BODY);
        }
        $writer->addRow([]);

        $writer->addRow(['How fast the office answered'], $writer::STYLE_SECTION);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Measure', 'Value'], $writer::STYLE_HEADER);
        foreach ($decision['response_time']['rows'] as $row) {
            $writer->addRow([$row['label'], $row['hint'] ?? $row['count']], $writer::STYLE_BODY);
        }
        $writer->addRow([]);

        $writer->addRow(['Where to send people'], $writer::STYLE_SECTION);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Place', 'Main type of case', 'Cases', 'Still open'], $writer::STYLE_HEADER);
        foreach ($decision['where_to_send']['rows'] as $row) {
            $writer->addRow([$row['label'], $row['type'], $row['count'], $row['open']], $writer::STYLE_BODY);
        }
        $writer->addRow([]);

        $writer->addRow(['Which office is carrying the cases'], $writer::STYLE_SECTION);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Office', 'Cases', 'Still open'], $writer::STYLE_HEADER);
        foreach ($decision['agency_load']['rows'] as $row) {
            $writer->addRow([$row['label'], $row['count'], $row['open']], $writer::STYLE_BODY);
        }
        $writer->addRow([]);

        $writer->addRow(['Incident Detail'], $writer::STYLE_SECTION);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Tracking #', 'Type', 'Barangay', 'Agency', 'Status', 'Reported At'], $writer::STYLE_HEADER);
        foreach ($incidents as $incident) {
            $status = is_object($incident->status) ? $incident->status->value : $incident->status;
            $writer->addRow([
                $incident->tracking_number,
                $incident->incidentType?->name,
                $incident->barangay,
                $this->resolveAgencyName($incident),
                ucfirst(str_replace('_', ' ', (string) $status)),
                $incident->reported_at?->format('Y-m-d H:i'),
            ], $writer::STYLE_BODY);
        }

        return response($writer->toBinary(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="raniag-report-'.now()->format('Y-m-d').'.xlsx"',
        ])->cookie('download_token', $request->input('download_token'), 1, null, null, null, false);
    }

    /**
     * "Chart Summary" report — the AOR-aware, period-bucketed (weekly/
     * monthly/periodic) companion to the PDF/Excel exports above, letting
     * the admin choose exactly which charts to generate. Every number here
     * comes straight out of deterministic COUNT()/GROUP BY aggregation and
     * fixed arithmetic comparisons (share of total, first-half vs.
     * second-half of the period) — nothing is phrased by a language model,
     * so the same filters always produce the exact same output, byte for
     * byte, which is the "consistent, good results" the admin asked for.
     */
    public function chartSummary(Request $request)
    {
        $validated = $this->validateFilters($request, withView: true);
        $query = $this->buildFilteredQuery($validated, ['incidentType', 'agency', 'assignments.agency']);
        $incidents = $query->orderBy('reported_at')->get();

        if ($incidents->isEmpty()) {
            return redirect()->route('admin.reports.index')
                ->withInput()
                ->with('warning', 'No incidents found for the selected filters. Please try a different date range or criteria.');
        }

        $selectedCharts = array_values(array_intersect(self::CHART_KEYS, $validated['charts'] ?? []));
        if (empty($selectedCharts)) {
            $selectedCharts = self::CHART_KEYS;
        }

        $viewMode = $validated['view_mode'] ?? 'periodic';
        $periods = $this->bucketByPeriod($incidents, $viewMode, $validated['date_from'], $validated['date_to']);

        $tables = $this->decisionTables($incidents);
        $charts = [];
        foreach ($selectedCharts as $key) {
            $charts[$key] = match ($key) {
                'open_workload' => $tables['open_workload'],
                'response_time' => $tables['response_time'],
                'where_to_send' => $tables['where_to_send'],
                'agency_load' => $tables['agency_load'],
                'when_reports_arrived' => $this->buildTrendChart($periods, $viewMode),
                default => null,
            };
        }

        $agencyName = null;
        if (! empty($validated['agency_id'])) {
            $agencyName = Agency::find($validated['agency_id'])?->name ?? 'N/A';
        }

        $narrative = $this->buildNarrative($incidents, $periods, $viewMode, $charts);

        $pdf = Pdf::loadView('admin.reports.chart_summary_pdf', [
            'incidents' => $incidents,
            'filters' => $validated,
            'agencyName' => $agencyName,
            'viewMode' => $viewMode,
            'periods' => $periods,
            'charts' => $charts,
            'chartLabels' => self::CHART_LABELS,
            'narrative' => $narrative,
            'generated_at' => now(),
        ]);

        return $pdf->download('raniag-chart-summary-'.now()->format('Y-m-d').'.pdf')
            ->cookie('download_token', $request->input('download_token'), 1, null, null, null, false);
    }

    /**
     * Shared filter validation for all three report actions — one rule set,
     * used everywhere, so "AOR scope" and the rest behave identically on
     * PDF, Excel, and Chart Summary instead of drifting between them.
     */
    private function validateFilters(Request $request, bool $withView = false): array
    {
        $rules = [
            'date_from' => 'required|date|before_or_equal:today',
            'date_to' => 'required|date|after_or_equal:date_from|before_or_equal:today',
            'barangay' => 'nullable|string|in:'.implode(',', config('raniag.barangays')),
            'agency_id' => 'nullable|exists:agencies,id',
            'incident_type_id' => 'nullable|exists:incident_types,id',
            'aor_scope' => 'nullable|string|in:aor_only,outside_aor_only,all',
        ];

        if ($withView) {
            $rules['view_mode'] = 'nullable|string|in:periodic,weekly,monthly';
            $rules['charts'] = 'nullable|array';
            $rules['charts.*'] = 'string|in:'.implode(',', self::CHART_KEYS);
        }

        $validated = $request->validate($rules);
        $validated['aor_scope'] = $validated['aor_scope'] ?? 'aor_only';

        if ($withView) {
            $validated['view_mode'] = $validated['view_mode'] ?? 'weekly';
            $validated['charts'] = $validated['charts'] ?? self::CHART_KEYS;
        }

        return $validated;
    }

    /**
     * Applies date range + barangay/agency/incident-type/AOR-scope filters
     * consistently across every report action.
     *
     * AOR scope replaces the old single "Include Outside-AOR" checkbox with
     * an explicit three-way choice, so "AOR" vs. "Outside-AOR" is a real
     * selectable distinction rather than an on/off toggle bolted onto a
     * report that's otherwise always AOR-only:
     *   - aor_only          (default) real MDRRMO Pamplona cases only
     *   - outside_aor_only  only incidents referred to another jurisdiction
     *   - all               both, clearly labeled in the output
     */
    private function buildFilteredQuery(array $validated, array $with = []): Builder
    {
        $query = Incident::with($with)
            ->whereBetween('reported_at', [
                $validated['date_from'].' 00:00:00',
                $validated['date_to'].' 23:59:59',
            ]);

        $outsideAor = \App\Enums\IncidentStatus::OutsideAor->value;
        match ($validated['aor_scope']) {
            'outside_aor_only' => $query->where('status', $outsideAor),
            'all' => $query,
            default => $query->where('status', '!=', $outsideAor),
        };

        if (! empty($validated['barangay'])) {
            $query->where('barangay', $validated['barangay']);
        }

        if (! empty($validated['agency_id'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('agency_id', $validated['agency_id'])
                  ->orWhereHas('assignments', function ($a) use ($validated) {
                      $a->where('agency_id', $validated['agency_id']);
                  });
            });
        }

        if (! empty($validated['incident_type_id'])) {
            $query->where('incident_type_id', $validated['incident_type_id']);
        }

        return $query;
    }

    /**
     * Groups incidents into period buckets for the Trend chart / summary
     * table. "periodic" is a single bucket spanning the whole selected
     * range (today's behavior, unchanged); "weekly"/"monthly" split the
     * range into calendar weeks/months so the admin can see week-over-week
     * or month-over-month movement for better decision-making.
     *
     * @return array<int, array{label: string, from: Carbon, to: Carbon, count: int}>
     */
    private function bucketByPeriod($incidents, string $viewMode, string $dateFrom, string $dateTo): array
    {
        $start = Carbon::parse($dateFrom)->startOfDay();
        $end = Carbon::parse($dateTo)->endOfDay();

        if ($viewMode === 'periodic') {
            return [[
                'label' => $start->format('M d, Y').' – '.$end->format('M d, Y'),
                'from' => $start,
                'to' => $end,
                'count' => $incidents->count(),
            ]];
        }

        $periods = [];
        $cursor = $viewMode === 'monthly' ? $start->copy()->startOfMonth() : $start->copy()->startOfWeek();

        while ($cursor->lte($end)) {
            $bucketEnd = $viewMode === 'monthly' ? $cursor->copy()->endOfMonth() : $cursor->copy()->endOfWeek();
            $rangeFrom = $cursor->max($start);
            $rangeTo = $bucketEnd->min($end);

            $count = $incidents->filter(function ($incident) use ($rangeFrom, $rangeTo) {
                return $incident->reported_at && $incident->reported_at->between($rangeFrom, $rangeTo, true);
            })->count();

            $periods[] = [
                'label' => $viewMode === 'monthly' ? $cursor->format('F Y') : 'Week of '.$rangeFrom->format('M d, Y'),
                'from' => $rangeFrom,
                'to' => $rangeTo,
                'count' => $count,
            ];

            $cursor = $viewMode === 'monthly' ? $cursor->copy()->addMonthNoOverflow() : $cursor->copy()->addWeek();
        }

        return $periods;
    }

    /**
     * @return array{rows: array<int, array{label:string, count:int, pct:float}>, total:int}
     */
    private function buildStatusBreakdown($incidents): array
    {
        $total = $incidents->count();
        $counts = $incidents->groupBy(fn ($i) => is_object($i->status) ? $i->status->value : $i->status)
            ->map->count()
            ->sortDesc();

        $rows = [];
        foreach ($counts as $status => $count) {
            $label = $status === 'outside_aor' ? 'Outside AOR (Referred)' : ucfirst(str_replace('_', ' ', (string) $status));
            $rows[] = ['label' => $label, 'count' => $count, 'pct' => $total > 0 ? round($count / $total * 100, 1) : 0.0];
        }

        return ['rows' => $rows, 'total' => $total];
    }

    private function buildTypeBreakdown($incidents): array
    {
        $total = $incidents->count();
        $counts = $incidents->groupBy(fn ($i) => $i->incidentType->name ?? 'Uncategorized')
            ->map->count()
            ->sortDesc();

        $rows = [];
        foreach ($counts as $type => $count) {
            $rows[] = ['label' => $type, 'count' => $count, 'pct' => $total > 0 ? round($count / $total * 100, 1) : 0.0];
        }

        return ['rows' => $rows, 'total' => $total];
    }

    private function buildBarangayHotspots($incidents): array
    {
        $total = $incidents->count();
        $counts = $incidents->whereNotNull('barangay')
            ->groupBy('barangay')
            ->map->count()
            ->sortDesc()
            ->take(10);

        $rows = [];
        foreach ($counts as $barangay => $count) {
            $rows[] = ['label' => $barangay, 'count' => $count, 'pct' => $total > 0 ? round($count / $total * 100, 1) : 0.0];
        }

        return ['rows' => $rows, 'total' => $total];
    }

    private function buildTrendChart(array $periods, string $viewMode): array
    {
        $max = collect($periods)->max('count') ?: 1;
        $rows = array_map(fn ($p) => [
            'label' => $p['label'],
            'count' => $p['count'],
            'pct' => round($p['count'] / $max * 100, 1),
        ], $periods);

        return ['rows' => $rows, 'total' => collect($periods)->sum('count'), 'bucketed' => $viewMode !== 'periodic'];
    }

    /**
     * Fixed-template narrative built entirely from the numbers already
     * computed above — same inputs always produce the same sentences, word
     * for word. No free-text generation, so there's nothing for the "not
     * consistent" complaint to attach to.
     */
    /**
     * Numbers an MDRRMO desk actually uses: what is still open, how long
     * the first assignment took, which place needs which kind of team, and
     * which office is holding the open cases.
     *
     * @return array<string, array{rows: array<int, array<string, mixed>>, total: int}>
     */
    private function decisionTables($incidents): array
    {
        $openStatuses = ['submitted', 'received', 'assigned', 'in_progress', 'pending_info'];
        $statusOf = fn ($incident) => is_object($incident->status) ? $incident->status->value : (string) $incident->status;
        $open = $incidents->filter(fn ($incident) => in_array($statusOf($incident), $openStatuses, true));

        $priorityRows = [];
        foreach (['critical', 'high', 'medium', 'low'] as $priority) {
            $count = $open->filter(function ($incident) use ($priority) {
                $value = is_object($incident->priority) ? $incident->priority->value : (string) $incident->priority;

                return $value === $priority;
            })->count();
            if ($count > 0) {
                $priorityRows[] = [
                    'label' => ucfirst($priority).' priority, still open',
                    'count' => $count,
                    'pct' => $open->count() > 0 ? round($count / $open->count() * 100, 1) : 0.0,
                ];
            }
        }
        if ($open->isEmpty()) {
            $priorityRows[] = ['label' => 'Nothing is still waiting for a team', 'count' => 0, 'pct' => 0.0];
        }

        $assignMinutes = [];
        $resolveMinutes = [];
        foreach ($incidents as $incident) {
            $assignedAt = $incident->assignments
                ->filter(fn ($assignment) => $assignment->assigned_at)
                ->min('assigned_at');
            if ($incident->reported_at && $assignedAt) {
                $assignMinutes[] = max(0, (int) $incident->reported_at->diffInMinutes(\Carbon\Carbon::parse($assignedAt)));
            }
            if ($incident->reported_at && $incident->resolved_at) {
                $resolveMinutes[] = max(0, (int) $incident->reported_at->diffInMinutes($incident->resolved_at));
            }
        }
        $within30 = count(array_filter($assignMinutes, fn ($m) => $m <= 30));
        $within2h = count(array_filter($assignMinutes, fn ($m) => $m > 30 && $m <= 120));
        $after2h = count(array_filter($assignMinutes, fn ($m) => $m > 120));
        $notAssigned = $incidents->count() - count($assignMinutes);
        $responseRows = [
            ['label' => 'First team assigned within 30 minutes', 'count' => $within30, 'hint' => (string) $within30, 'pct' => 0],
            ['label' => 'First team assigned in 30 minutes to 2 hours', 'count' => $within2h, 'hint' => (string) $within2h, 'pct' => 0],
            ['label' => 'First team assigned after 2 hours', 'count' => $after2h, 'hint' => (string) $after2h, 'pct' => 0],
            ['label' => 'No team assigned yet', 'count' => $notAssigned, 'hint' => (string) $notAssigned, 'pct' => 0],
            ['label' => 'Median time to first assignment', 'count' => 0, 'hint' => $this->medianLabel($assignMinutes), 'pct' => 0],
            ['label' => 'Median time to resolution', 'count' => 0, 'hint' => $this->medianLabel($resolveMinutes), 'pct' => 0],
        ];

        $places = $incidents->groupBy(fn ($incident) => $incident->barangay ?: 'No barangay recorded')
            ->map(function ($group, $place) use ($statusOf, $openStatuses) {
                $types = $group->groupBy(fn ($incident) => $incident->incidentType->name ?? 'Uncategorized')->map->count()->sortDesc();

                return [
                    'label' => $place,
                    'type' => (string) ($types->keys()->first() ?? '—'),
                    'count' => $group->count(),
                    'open' => $group->filter(fn ($incident) => in_array($statusOf($incident), $openStatuses, true))->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(8)
            ->values();
        $placeTotal = max(1, (int) $places->sum('count'));
        $placeRows = $places->map(fn ($row) => $row + ['pct' => round($row['count'] / $placeTotal * 100, 1)])->all();

        $offices = [];
        foreach ($incidents as $incident) {
            $name = $this->resolveAgencyName($incident) ?: 'Not assigned to an office';
            $offices[$name]['count'] = ($offices[$name]['count'] ?? 0) + 1;
            if (in_array($statusOf($incident), $openStatuses, true)) {
                $offices[$name]['open'] = ($offices[$name]['open'] ?? 0) + 1;
            }
        }
        uasort($offices, fn ($a, $b) => ($b['open'] ?? 0) <=> ($a['open'] ?? 0));
        $officeRows = [];
        foreach ($offices as $name => $row) {
            $officeRows[] = [
                'label' => $name,
                'count' => $row['count'],
                'open' => $row['open'] ?? 0,
                'pct' => $incidents->count() > 0 ? round($row['count'] / $incidents->count() * 100, 1) : 0.0,
            ];
        }

        return [
            'open_workload' => ['rows' => $priorityRows, 'total' => $open->count()],
            'response_time' => ['rows' => $responseRows, 'total' => $incidents->count()],
            'where_to_send' => ['rows' => $placeRows, 'total' => $incidents->count()],
            'agency_load' => ['rows' => $officeRows, 'total' => $incidents->count()],
        ];
    }

    private function medianLabel(array $minutes): string
    {
        if ($minutes === []) {
            return 'Not enough closed or assigned cases yet';
        }
        sort($minutes);
        $mid = (int) floor((count($minutes) - 1) / 2);
        $median = count($minutes) % 2 === 0
            ? (int) round(($minutes[$mid] + $minutes[$mid + 1]) / 2)
            : $minutes[$mid];
        if ($median < 60) {
            return $median.' minutes';
        }

        return round($median / 60, 1).' hours';
    }

    private function buildNarrative($incidents, array $periods, string $viewMode, array $charts): array
    {
        $lines = [];
        $total = $incidents->count();
        $lines[] = "{$total} report".($total === 1 ? '' : 's').' matched these filters.';

        $openTotal = $charts['open_workload']['total'] ?? null;
        if ($openTotal !== null) {
            $lines[] = $openTotal > 0
                ? "{$openTotal} of those still need a team. Start with the critical and high priority rows."
                : 'Every report in this period is already resolved, closed, or referred.';
        }

        $median = collect($charts['response_time']['rows'] ?? [])->firstWhere('label', 'Median time to first assignment');
        if ($median) {
            $lines[] = 'Median time from the report to the first assigned office: '.$median['hint'].'.';
        }

        if (isset($charts['where_to_send']['rows'][0])) {
            $top = $charts['where_to_send']['rows'][0];
            $lines[] = "{$top['label']} has the most reports ({$top['count']}), mostly {$top['type']}. {$top['open']} there ".($top['open'] === 1 ? 'is' : 'are').' still open.';
        }

        $busiestOffice = collect($charts['agency_load']['rows'] ?? [])->sortByDesc('open')->first();
        if ($busiestOffice && ($busiestOffice['open'] ?? 0) > 0) {
            $lines[] = "{$busiestOffice['label']} is holding the most open cases ({$busiestOffice['open']}).";
        }

        if ($viewMode !== 'periodic' && count($periods) >= 2) {
            $midpoint = (int) ceil(count($periods) / 2);
            $firstHalf = array_slice($periods, 0, $midpoint);
            $secondHalf = array_slice($periods, $midpoint);
            $firstSum = array_sum(array_column($firstHalf, 'count'));
            $secondSum = array_sum(array_column($secondHalf, 'count'));

            if ($firstSum === 0 && $secondSum === 0) {
                $lines[] = 'No incidents were recorded in either half of the selected period.';
            } else {
                $delta = $secondSum - $firstSum;
                $direction = $delta > 0 ? 'increased' : ($delta < 0 ? 'decreased' : 'stayed level');
                $pctChange = $firstSum > 0 ? round(abs($delta) / $firstSum * 100, 1) : null;
                $changeText = $pctChange !== null ? " ({$pctChange}%)" : '';
                $unit = $viewMode === 'monthly' ? 'months' : 'weeks';
                $lines[] = "Comparing the first and second half of the selected {$unit}, incident volume {$direction}{$changeText} — from {$firstSum} to {$secondSum}.";
            }

            $busiest = collect($periods)->sortByDesc('count')->first();
            if ($busiest && $busiest['count'] > 0) {
                $lines[] = "The busiest period was {$busiest['label']} with {$busiest['count']} incident".($busiest['count'] === 1 ? '' : 's').'.';
            }
        }

        return $lines;
    }

    /**
     * Resolve the agency actually assigned to an incident, checking the
     * direct agency_id first, then the most recent assignment record
     * (regardless of whether it's still flagged active — a resolved
     * incident's assignment is no longer "active" but was still real).
     * Returns null (not "Unassigned") when no agency was ever selected.
     */
    private function resolveAgencyName(Incident $incident): ?string
    {
        if ($incident->agency?->name) {
            return $incident->agency->name;
        }

        $assignments = $incident->relationLoaded('assignments')
            ? $incident->assignments
            : $incident->assignments()->get();

        // Prefer the currently active assignment; fall back to the most
        // recent assignment that actually has an agency attached (covers
        // incidents reassigned to "unassigned" after a real agency worked it).
        $active = $assignments->firstWhere('is_active', true);
        if ($active?->agency?->name) {
            return $active->agency->name;
        }

        return $assignments->sortByDesc('created_at')
            ->first(fn ($a) => $a->agency?->name)
            ?->agency?->name;
    }
}

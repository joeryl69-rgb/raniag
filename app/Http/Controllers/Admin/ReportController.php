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
    private const CHART_KEYS = ['status_breakdown', 'type_breakdown', 'barangay_hotspots', 'trend'];

    private const CHART_LABELS = [
        'status_breakdown' => 'Incidents by Status',
        'type_breakdown' => 'Incidents by Type',
        'barangay_hotspots' => 'Barangay Hotspots',
        'trend' => 'Trend Over Time',
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

        // Dashboard-style analytics for the same filtered period
        $byStatus = (clone $query)->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');
        $byType = (clone $query)->join('incident_types', 'incidents.incident_type_id', '=', 'incident_types.id')
            ->selectRaw('incident_types.name, COUNT(*) as count')->groupBy('incident_types.name')->orderByDesc('count')->pluck('count', 'name');
        $accidentProneAreas = (clone $query)->join('incident_types', 'incidents.incident_type_id', '=', 'incident_types.id')
            ->whereNotNull('barangay')
            ->selectRaw('barangay, incident_types.name as type, COUNT(*) as count')
            ->groupBy('barangay', 'incident_types.name')
            ->having('count', '>', 1)
            ->orderByDesc('count')
            ->limit(15)
            ->get();

        $writer = new \App\Services\SimpleXlsxWriter;
        $writer->setColumnWidths([16, 14, 16, 20, 14, 18]);

        $writer->addRow(['RANIAG Incident Report'], $writer::STYLE_TITLE);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Period: '.$validated['date_from'].' to '.$validated['date_to'].'  |  Generated: '.now()->format('M d, Y h:i A')], $writer::STYLE_SUBTITLE);
        $writer->mergeRow(0, 5);
        $writer->addRow([]);

        $writer->addRow(['Summary by Status'], $writer::STYLE_SECTION);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Status', 'Count'], $writer::STYLE_HEADER);
        foreach ($byStatus as $status => $count) {
            $writer->addRow([ucfirst(str_replace('_', ' ', $status)), $count], $writer::STYLE_BODY);
        }
        $writer->addRow([]);

        $writer->addRow(['Summary by Incident Type'], $writer::STYLE_SECTION);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Type', 'Count'], $writer::STYLE_HEADER);
        foreach ($byType as $type => $count) {
            $writer->addRow([$type, $count], $writer::STYLE_BODY);
        }
        $writer->addRow([]);

        $writer->addRow(['Accident-Prone Areas (Barangay Hotspots)'], $writer::STYLE_SECTION);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Barangay', 'Incident Type', 'Cases'], $writer::STYLE_HEADER);
        if ($accidentProneAreas->isEmpty()) {
            $writer->addRow(['No repeat hotspots found for this period.'], $writer::STYLE_BODY);
            $writer->mergeRow(0, 2);
        } else {
            foreach ($accidentProneAreas as $area) {
                $writer->addRow([$area->barangay, $area->type, $area->count], $writer::STYLE_BODY);
            }
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
        $query = $this->buildFilteredQuery($validated, ['incidentType']);
        $incidents = $query->orderBy('reported_at')->get(['id', 'incident_type_id', 'barangay', 'status', 'reported_at']);

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

        $charts = [];
        foreach ($selectedCharts as $key) {
            $charts[$key] = match ($key) {
                'status_breakdown' => $this->buildStatusBreakdown($incidents),
                'type_breakdown' => $this->buildTypeBreakdown($incidents),
                'barangay_hotspots' => $this->buildBarangayHotspots($incidents),
                'trend' => $this->buildTrendChart($periods, $viewMode),
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
            $validated['view_mode'] = $validated['view_mode'] ?? 'periodic';
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
    private function buildNarrative($incidents, array $periods, string $viewMode, array $charts): array
    {
        $lines = [];
        $total = $incidents->count();
        $lines[] = "A total of {$total} incident".($total === 1 ? '' : 's')." matched the selected filters.";

        if (isset($charts['type_breakdown']['rows'][0])) {
            $top = $charts['type_breakdown']['rows'][0];
            $lines[] = "The most frequent incident type was \"{$top['label']}\" with {$top['count']} case".($top['count'] === 1 ? '' : 's')." ({$top['pct']}% of total).";
        }

        if (isset($charts['barangay_hotspots']['rows'][0])) {
            $top = $charts['barangay_hotspots']['rows'][0];
            $lines[] = "{$top['label']} recorded the most incidents among barangays with {$top['count']} case".($top['count'] === 1 ? '' : 's')." ({$top['pct']}% of total).";
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

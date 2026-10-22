<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\Announcement;
use App\Models\EvacuationCenter;
use App\Models\Evacuee;
use App\Models\FeedbackSubmission;
use App\Models\HazardZone;
use App\Models\Incident;
use App\Models\IncidentDocument;
use App\Models\IncidentType;
use App\Models\ReportArchive;
use App\Models\SmsLog;
use App\Support\PeriodRange;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Chart keys the admin is allowed to pick on the "Generate Reports" form.
     * Single source of truth — used for validation AND for the order charts
     * render in on the PDF, so what's picked is exactly (and only) what's
     * generated, in the same order every time.
     */
    private const DECISION_SECTIONS = [
        'summary',
        'status',
        'priority',
        'types',
        'places',
        'arrivals',
        'comparison',
        'projection',
        'repeats',
        'shelters',
    ];

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

    private const OPERATIONS = [
        'daily' => 'Operations file',
        'shelter' => 'Shelter guests',
        'places' => 'Hazard areas and shelters',
        'messages' => 'Texts sent',
        'papers' => 'Papers filed',
        'support' => 'Support messages',
        'notices' => 'Public notices',
        'activity' => 'Office activity',
    ];

    public function index(): View
    {
        $incidentTypes = IncidentType::orderBy('name')->get(['id', 'name']);
        $agencies = Agency::orderBy('name')->get(['id', 'name', 'code']);
        $barangays = config('raniag.barangays');

        $archives = Schema::hasTable('report_archives')
            ? ReportArchive::query()->with('user:id,name')->latest()->limit(20)->get()
            : collect();

        return view('admin.reports.index', [
            'incidentTypes' => $incidentTypes,
            'agencies' => $agencies,
            'barangays' => $barangays,
            'chartKeys' => self::CHART_KEYS,
            'chartLabels' => self::CHART_LABELS,
            'archives' => $archives,
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
            'shelters' => $this->shelterPicture($validated['date_from'], $validated['date_to']),
            'repeats' => $this->repeatAreas($incidents),
            'generated_at' => now(),
        ])->setPaper('a4', 'portrait');

        return $this->sendFile($pdf->output(), 'application/pdf', 'raniag-report-'.now()->format('Y-m-d').'.pdf', $Request, [
            'kind' => 'register',
            'title' => 'Incident register',
            'format' => 'pdf',
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
        ]);
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
        $status = $this->buildStatusBreakdown($incidents);
        $types = $this->buildTypeBreakdown($incidents);

        $writer->addRow(['Incident status'], $writer::STYLE_SECTION);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Status', 'Reports'], $writer::STYLE_HEADER);
        foreach ($status['rows'] as $row) {
            $writer->addRow([$row['label'], $row['count']], $writer::STYLE_BODY);
        }
        $writer->addRow([]);

        $writer->addRow(['Incidents by type'], $writer::STYLE_SECTION);
        $writer->mergeRow(0, 5);
        $writer->addRow(['Type', 'Reports'], $writer::STYLE_HEADER);
        foreach ($types['rows'] as $row) {
            $writer->addRow([$row['label'], $row['count']], $writer::STYLE_BODY);
        }
        $writer->addRow([]);

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

        $shelters = $this->shelterPicture($validated['date_from'], $validated['date_to']);
        $writer->addRow(['People in shelters'], $writer::STYLE_SECTION);
        $writer->mergeRow(0, 5);
        $writer->addRow(['From Pamplona', $shelters['inside'], 'From outside', $shelters['outside'], 'Still inside', $shelters['still_in']], $writer::STYLE_BODY);
        $writer->addRow(['Name', 'Shelter', 'From', 'Origin', 'Checked in', 'Status'], $writer::STYLE_HEADER);
        foreach ($shelters['rows'] as $row) {
            $writer->addRow([$row['name'], $row['shelter'], $row['from'], $row['origin'], $row['checked_in'], $row['status']], $writer::STYLE_BODY);
        }
        if ($shelters['rows'] === []) {
            $writer->addRow(['No evacuees checked in during this range.'], $writer::STYLE_BODY);
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

        return $this->sendFile(
            $writer->toBinary(),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'raniag-report-'.now()->format('Y-m-d').'.xlsx',
            $request,
            [
                'kind' => 'sheet',
                'title' => 'Working spreadsheet',
                'format' => 'xlsx',
                'date_from' => $validated['date_from'],
                'date_to' => $validated['date_to'],
            ]
        );
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
                'open_workload' => [
                    'rows' => $this->orderedPriorityRows($tables['open_workload']['rows']),
                    'total' => $tables['open_workload']['total'],
                ],
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
        ])->setPaper('a4', 'portrait');

        return $this->sendFile($pdf->output(), 'application/pdf', 'raniag-chart-summary-'.now()->format('Y-m-d').'.pdf', $request, [
            'kind' => 'brief',
            'title' => 'Operations brief',
            'format' => 'pdf',
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
        ]);
    }

    /**
     * Downloadable decision file. The admin picks the sections, the main
     * date range, and an optional second range to compare. The projection
     * is the straight-line change across those periods, not a guessed event.
     */
    public function decision(Request $request)
    {
        $validated = $this->validateFilters($request, withView: true);
        $sections = array_values(array_intersect(self::DECISION_SECTIONS, $validated['sections'] ?? self::DECISION_SECTIONS));
        if ($sections === []) {
            $sections = self::DECISION_SECTIONS;
        }

        $incidents = $this->buildFilteredQuery($validated, ['incidentType', 'agency', 'assignments.agency'])
            ->orderBy('reported_at')
            ->get();

        if ($incidents->isEmpty()) {
            return redirect()->route('admin.reports.index')
                ->withInput()
                ->with('warning', 'No incidents found for the selected filters. Please try a different date range or criteria.');
        }

        $viewMode = $validated['view_mode'] ?? 'weekly';
        if ($viewMode === 'periodic') {
            $viewMode = 'weekly';
        }
        $current = $this->incidentPicture($incidents, $validated['date_from'], $validated['date_to'], $viewMode);

        $comparison = null;
        $compareFilters = null;
        if (in_array('comparison', $sections, true)) {
            [$compareFrom, $compareTo] = $this->comparisonWindow($validated);
            $compareFilters = array_merge($validated, [
                'date_from' => $compareFrom->toDateString(),
                'date_to' => $compareTo->toDateString(),
            ]);
            $earlier = $this->buildFilteredQuery($compareFilters, ['incidentType', 'agency', 'assignments.agency'])
                ->orderBy('reported_at')
                ->get();
            $comparison = $this->incidentPicture($earlier, $compareFilters['date_from'], $compareFilters['date_to'], $viewMode);
        }

        $projection = in_array('projection', $sections, true)
            ? $this->projectNextPeriod($current['arrivals'])
            : null;
        $paired = $comparison ? [
            'status' => $this->pairCounts($current['status'], $comparison['status']),
            'priority' => $this->pairCounts($current['priority'], $comparison['priority']),
            'types' => $this->pairCounts($current['types'], $comparison['types']),
        ] : null;

        $agencyName = null;
        if (! empty($validated['agency_id'])) {
            $agencyName = Agency::find($validated['agency_id'])?->name ?? 'N/A';
        }

        $pdf = Pdf::loadView('admin.reports.decision_pdf', [
            'filters' => $validated,
            'compareFilters' => $compareFilters,
            'agencyName' => $agencyName,
            'sections' => $sections,
            'current' => $current,
            'comparison' => $comparison,
            'paired' => $paired,
            'projection' => $projection,
            'shelters' => $this->shelterPicture($validated['date_from'], $validated['date_to']),
            'generated_at' => now(),
        ])->setPaper('a4', 'portrait');

        return $this->sendFile($pdf->output(), 'application/pdf', 'raniag-decision-'.now()->format('Y-m-d').'.pdf', $request, [
            'kind' => 'decision',
            'title' => 'Decision report',
            'format' => 'pdf',
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function comparisonWindow(array $validated): array
    {
        if (! empty($validated['compare_from']) && ! empty($validated['compare_to'])) {
            return [
                Carbon::parse($validated['compare_from'])->startOfDay(),
                Carbon::parse($validated['compare_to'])->endOfDay(),
            ];
        }

        $from = Carbon::parse($validated['date_from'])->startOfDay();
        $to = Carbon::parse($validated['date_to'])->endOfDay();
        $days = (int) $from->diffInDays($to) + 1;
        $compareTo = $from->copy()->subDay()->endOfDay();

        return [$compareTo->copy()->subDays($days - 1)->startOfDay(), $compareTo];
    }

    /**
     * @param  array<int, array{label: string, count: int}>  $current
     * @param  array<int, array{label: string, count: int}>  $previous
     * @return array<int, array{label: string, current: int, previous: int, change: int}>
     */
    private function pairCounts(array $current, array $previous): array
    {
        $labels = [];
        foreach (array_merge($current, $previous) as $row) {
            $labels[$row['label']] = true;
        }
        $now = collect($current)->keyBy('label');
        $then = collect($previous)->keyBy('label');
        $rows = [];
        foreach (array_keys($labels) as $label) {
            $a = (int) ($now[$label]['count'] ?? 0);
            $b = (int) ($then[$label]['count'] ?? 0);
            $rows[] = ['label' => $label, 'current' => $a, 'previous' => $b, 'change' => $a - $b];
        }

        return $rows;
    }

    /**
     * @param  array<int, array{label: string, count: int}>  $arrivals
     * @return array{count: int, direction: string, note: string}
     */
    private function projectNextPeriod(array $arrivals): array
    {
        $counts = array_map(fn ($row) => (int) $row['count'], $arrivals);
        $n = count($counts);
        if ($n === 0) {
            return [
                'count' => 0,
                'direction' => 'flat',
                'note' => 'There are no periods in this range to project from.',
            ];
        }
        if ($n === 1) {
            return [
                'count' => $counts[0],
                'direction' => 'flat',
                'note' => 'Only one period is in this range, so the next period is projected at the same number of reports ('.$counts[0].').',
            ];
        }

        $slope = ($counts[$n - 1] - $counts[0]) / ($n - 1);
        $next = max(0, (int) round($counts[$n - 1] + $slope));
        $direction = $slope > 0.5 ? 'up' : ($slope < -0.5 ? 'down' : 'about level');
        $unit = 'period';

        return [
            'count' => $next,
            'direction' => $direction,
            'note' => 'If the change from the first '.$unit.' to the last continues, the next '.$unit.' would have about '.$next.' reports. The line is '.$direction.' across this range. This is a planning figure from these dates, not a prediction of a particular incident.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function incidentPicture($incidents, string $dateFrom, string $dateTo, string $viewMode): array
    {
        $tables = $this->decisionTables($incidents);
        $periods = $this->bucketByPeriod($incidents, $viewMode, $dateFrom, $dateTo);
        $openTypes = $this->buildTypeBreakdown($incidents->filter(function ($incident) {
            $status = is_object($incident->status) ? $incident->status->value : (string) $incident->status;

            return in_array($status, ['submitted', 'received', 'assigned', 'in_progress', 'pending_info'], true);
        }));
        $median = collect($tables['response_time']['rows'])->firstWhere('label', 'Median time to first assignment');

        return [
            'total' => $incidents->count(),
            'open' => $tables['open_workload']['total'],
            'median_assignment' => $median['hint'] ?? 'Not enough assigned cases yet',
            'status' => $this->orderedStatusRows($incidents),
            'priority' => $this->orderedPriorityRows($tables['open_workload']['rows']),
            'types' => $openTypes['rows'],
            'places' => $tables['where_to_send']['rows'],
            'offices' => $tables['agency_load']['rows'],
            'arrivals' => array_map(fn ($period) => [
                'label' => $period['label'],
                'count' => $period['count'],
            ], $periods),
            'repeats' => $this->repeatAreas($incidents),
            'lines' => $this->buildNarrative($incidents, $periods, $viewMode, [
                'open_workload' => $tables['open_workload'],
                'response_time' => $tables['response_time'],
                'where_to_send' => $tables['where_to_send'],
                'agency_load' => $tables['agency_load'],
            ]),
        ];
    }

    /**
     * @return array<int, array{label: string, count: int}>
     */
    private function orderedStatusRows($incidents): array
    {
        $order = [
            'submitted' => 'Submitted',
            'received' => 'Received',
            'assigned' => 'Assigned',
            'in_progress' => 'In progress',
            'pending_info' => 'Pending information',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            'rejected' => 'Rejected',
            'outside_aor' => 'Referred outside Pamplona',
        ];
        $counts = $incidents->groupBy(fn ($incident) => is_object($incident->status) ? $incident->status->value : (string) $incident->status)
            ->map->count();
        $rows = [];
        foreach ($order as $key => $label) {
            $count = (int) ($counts[$key] ?? 0);
            if ($count > 0) {
                $rows[] = ['label' => $label, 'count' => $count];
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{label: string, count: int}>
     */
    private function orderedPriorityRows(array $rows): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $counts[strtolower((string) $row['label'])] = (int) $row['count'];
        }
        $out = [];
        foreach (['critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $key => $label) {
            $match = 0;
            foreach ($counts as $stored => $count) {
                if (str_starts_with($stored, $key)) {
                    $match = $count;
                }
            }
            $out[] = ['label' => $label, 'count' => $match];
        }

        return $out;
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
            'barangay' => ['nullable', 'string', \Illuminate\Validation\Rule::in(array_merge(config('raniag.barangays'), ['inside', 'outside', 'all']))],
            'agency_id' => 'nullable|exists:agencies,id',
            'incident_type_id' => 'nullable|exists:incident_types,id',
            'aor_scope' => 'nullable|string|in:aor_only,outside_aor_only,all',
        ];

        if ($withView) {
            $rules['view_mode'] = 'nullable|string|in:periodic,weekly,monthly,yearly';
            $rules['charts'] = 'nullable|array';
            $rules['charts.*'] = 'string|in:'.implode(',', self::CHART_KEYS);
            $rules['sections'] = 'nullable|array';
            $rules['sections.*'] = 'string|in:'.implode(',', self::DECISION_SECTIONS);
            $rules['compare_from'] = 'nullable|date|before_or_equal:today';
            $rules['compare_to'] = 'nullable|date|after_or_equal:compare_from|before_or_equal:today';
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

        \App\Support\PlaceScope::applyBarangayFilter($query, $validated['barangay'] ?? null);

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
        $cursor = match ($viewMode) {
            'monthly' => $start->copy()->startOfMonth(),
            'yearly' => $start->copy()->startOfYear(),
            default => $start->copy()->startOfWeek(),
        };

        while ($cursor->lte($end)) {
            $bucketEnd = match ($viewMode) {
                'monthly' => $cursor->copy()->endOfMonth(),
                'yearly' => $cursor->copy()->endOfYear(),
                default => $cursor->copy()->endOfWeek(),
            };
            $rangeFrom = $cursor->copy()->max($start);
            $rangeTo = $bucketEnd->copy()->min($end);

            $count = $incidents->filter(function ($incident) use ($rangeFrom, $rangeTo) {
                return $incident->reported_at && $incident->reported_at->between($rangeFrom, $rangeTo, true);
            })->count();

            $label = match ($viewMode) {
                'monthly' => $cursor->format('F Y'),
                'yearly' => $cursor->format('Y'),
                default => 'Week of '.$rangeFrom->format('M d, Y'),
            };

            $periods[] = [
                'label' => $label,
                'from' => $rangeFrom,
                'to' => $rangeTo,
                'count' => $count,
            ];

            $cursor = match ($viewMode) {
                'monthly' => $cursor->copy()->addMonthNoOverflow(),
                'yearly' => $cursor->copy()->addYear(),
                default => $cursor->copy()->addWeek(),
            };
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
            return $median.' '.($median === 1 ? 'minute' : 'minutes');
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
                $unit = match ($viewMode) {
                    'monthly' => 'months',
                    'yearly' => 'years',
                    default => 'weeks',
                };
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

    /**
     * Places where the same incident type was reported more than once
     * in the selected range.
     *
     * @return list<array{place: string, type: string, count: int, band: string}>
     */
    private function repeatAreas($incidents): array
    {
        return $incidents
            ->groupBy(fn ($incident) => ($incident->barangay ?: 'Unspecified')."\n".($incident->incidentType->name ?? 'Incident'))
            ->map(function ($rows, $key) {
                [$place, $type] = explode("\n", $key, 2);
                $count = $rows->count();

                return [
                    'place' => $place,
                    'type' => $type,
                    'count' => $count,
                    'band' => PeriodRange::band($count),
                ];
            })
            ->filter(fn ($row) => $row['count'] >= 2)
            ->sortByDesc('count')
            ->take(8)
            ->values()
            ->all();
    }

    /**
     * Evacuees checked in during the report dates, split by whether they
     * live in Pamplona or came from another municipality.
     *
     * @return array{inside: int, outside: int, still_in: int, rows: list<array<string, string>>}
     */
    private function shelterPicture(string $dateFrom, string $dateTo): array
    {
        $empty = ['inside' => 0, 'outside' => 0, 'still_in' => 0, 'rows' => []];
        if (! Schema::hasTable('evacuees')) {
            return $empty;
        }

        $people = Evacuee::query()
            ->with('center')
            ->whereBetween('checked_in_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->orderBy('full_name')
            ->get();

        return [
            'inside' => $people->where('origin_scope', '!=', 'outside')->count(),
            'outside' => $people->where('origin_scope', 'outside')->count(),
            'still_in' => $people->whereNull('checked_out_at')->count(),
            'rows' => $people->map(fn (Evacuee $person) => [
                'name' => $person->full_name,
                'shelter' => $person->center?->name ?? '—',
                'from' => $person->homeLabel(),
                'origin' => $person->isOutsideMunicipality() ? 'Outside municipality' : 'Pamplona',
                'checked_in' => $person->checked_in_at?->format('M j, Y g:i a') ?? '—',
                'status' => $person->checked_out_at ? 'Left '.$person->checked_out_at->format('M j') : 'Inside',
            ])->all(),
        ];
    }

    /**
     * One PDF for a day-to-day record that is not an incident export:
     * the operations packet, shelter guests, the map, texts, papers,
     * support messages, notices, or the office activity list.
     */
    public function operations(Request $request)
    {
        $validated = $request->validate([
            'report_kind' => 'required|string|in:'.implode(',', array_keys(self::OPERATIONS)),
            'date_from' => 'required|date|before_or_equal:today',
            'date_to' => 'required|date|after_or_equal:date_from|before_or_equal:today',
            'barangay' => 'nullable|string',
            'agency_id' => 'nullable',
            'incident_type_id' => 'nullable',
            'aor_scope' => 'nullable|string|in:aor_only,outside_aor_only,all',
        ]);

        $kind = $validated['report_kind'];
        $document = $this->operationDocument($kind, $validated);
        $filename = 'raniag-'.$kind.'-'.now()->format('Y-m-d').'.pdf';
        $pdf = Pdf::loadView('admin.reports.operations_pdf', [
            'title' => $document['title'],
            'intro' => $document['intro'],
            'from' => Carbon::parse($validated['date_from'])->format('M j, Y'),
            'to' => Carbon::parse($validated['date_to'])->format('M j, Y'),
            'sections' => $document['sections'],
            'generated_at' => now(),
        ])->setPaper('a4', 'portrait');

        return $this->sendFile($pdf->output(), 'application/pdf', $filename, $request, [
            'kind' => $kind,
            'title' => $document['title'],
            'format' => 'pdf',
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
        ]);
    }

    public function copy(ReportArchive $archive)
    {
        if (! Storage::disk('local')->exists($archive->path)) {
            return redirect()->route('admin.reports.index')
                ->with('warning', 'That saved file is no longer on this system.');
        }

        return Storage::disk('local')->download($archive->path, $archive->filename);
    }

    /**
     * @param  array{kind: string, title: string, format: string, date_from?: string|null, date_to?: string|null}  $meta
     */
    private function sendFile(string $binary, string $mime, string $filename, Request $request, array $meta)
    {
        try {
            $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?: 'report.bin';
            $path = 'report-archive/'.now()->format('Y/m').'/'.now()->format('Ymd-His').'-'.$safe;
            Storage::disk('local')->put($path, $binary);
            if (Schema::hasTable('report_archives')) {
                ReportArchive::query()->create([
                    'user_id' => $request->user()?->id,
                    'kind' => $meta['kind'],
                    'title' => $meta['title'],
                    'format' => $meta['format'],
                    'date_from' => $meta['date_from'] ?? null,
                    'date_to' => $meta['date_to'] ?? null,
                    'filename' => $safe,
                    'path' => $path,
                    'bytes' => strlen($binary),
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return response($binary, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ])->cookie('download_token', (string) $request->input('download_token'), 1, null, null, null, false);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{title: string, intro: string, sections: list<array{heading: string, columns: list<string>, rows: list<list<string>>, note?: string}>}
     */
    private function operationDocument(string $kind, array $validated): array
    {
        $from = $validated['date_from'];
        $to = $validated['date_to'];
        $sections = match ($kind) {
            'daily' => array_merge(
                [$this->incidentRowsSection($validated)],
                $this->shelterSections($from, $to),
                [$this->messageSection($from, $to), $this->paperSection($from, $to), $this->supportSection($from, $to)]
            ),
            'shelter' => $this->shelterSections($from, $to),
            'places' => $this->placeSections(),
            'messages' => [$this->messageSection($from, $to)],
            'papers' => [$this->paperSection($from, $to)],
            'support' => [$this->supportSection($from, $to)],
            'notices' => [$this->noticeSection($from, $to)],
            'activity' => [$this->activitySection($from, $to)],
            default => [],
        };

        $intro = match ($kind) {
            'daily' => 'Incidents, shelter guests, texts, papers filed, and support messages for these dates.',
            'shelter' => 'People who checked into a shelter during these dates, and the shelters as they stand now.',
            'places' => 'Hazard areas and shelters as they stand when this file is made.',
            'messages' => 'Texts the office sent during these dates.',
            'papers' => 'Papers uploaded onto a case during these dates.',
            'support' => 'Messages received by the support desk during these dates.',
            'notices' => 'Public notices published during these dates.',
            'activity' => 'What staff did in the system during these dates.',
            default => 'Office record for these dates.',
        };

        return [
            'title' => self::OPERATIONS[$kind] ?? 'Office record',
            'intro' => $intro,
            'sections' => $sections,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{heading: string, columns: list<string>, rows: list<list<string>>, note?: string}
     */
    private function incidentRowsSection(array $validated): array
    {
        $validated['aor_scope'] = $validated['aor_scope'] ?? 'aor_only';
        $incidents = $this->buildFilteredQuery($validated, ['incidentType'])->orderByDesc('reported_at')->get();
        $open = $incidents->filter(function ($incident) {
            $status = $incident->status instanceof IncidentStatus ? $incident->status : IncidentStatus::tryFrom((string) $incident->status);

            return $status && ! in_array($status, [IncidentStatus::Resolved, IncidentStatus::Closed, IncidentStatus::Rejected, IncidentStatus::OutsideAor], true);
        });
        $shown = $incidents->take(200);

        return $this->tableSection(
            'Incidents',
            ['Tracking', 'Type', 'Barangay', 'Status', 'Reported'],
            $shown->map(function ($incident) {
                $status = $incident->status instanceof IncidentStatus ? $incident->status->label() : ucfirst(str_replace('_', ' ', (string) $incident->status));

                return [
                    (string) $incident->tracking_number,
                    (string) ($incident->incidentType?->name ?? '—'),
                    (string) ($incident->barangay ?: '—'),
                    $status,
                    $incident->reported_at?->format('M j, Y g:i a') ?? '—',
                ];
            })->all(),
            $incidents->count(),
            $open->count().' still open of '.$incidents->count().' reports in these dates.'
        );
    }

    /**
     * @return list<array{heading: string, columns: list<string>, rows: list<list<string>>, note?: string}>
     */
    private function shelterSections(string $from, string $to): array
    {
        $picture = $this->shelterPicture($from, $to);
        $centers = Schema::hasTable('evacuation_centers')
            ? EvacuationCenter::query()->withCount(['evacuees as inside_count' => fn ($q) => $q->whereNull('checked_out_at')])->orderBy('name')->get()
            : collect();

        return [
            $this->tableSection(
                'Shelters now',
                ['Shelter', 'Barangay', 'Open', 'Capacity', 'Inside now'],
                $centers->map(fn (EvacuationCenter $center) => [
                    $center->name,
                    $center->barangay ?: '—',
                    $center->is_open ? 'Open' : 'Closed',
                    $center->capacity ? (string) $center->capacity : '—',
                    (string) $center->inside_count,
                ])->all(),
                $centers->count(),
                'This table is the shelters at the moment the file is made.'
            ),
            $this->tableSection(
                'People who checked in',
                ['Name', 'Shelter', 'From', 'Checked in', 'Now'],
                array_map(fn (array $row) => [
                    $row['name'],
                    $row['shelter'],
                    $row['from'].' ('.$row['origin'].')',
                    $row['checked_in'],
                    $row['status'],
                ], $picture['rows']),
                count($picture['rows']),
                $picture['still_in'].' of these guests are still inside. '.$picture['outside'].' live outside Pamplona.'
            ),
        ];
    }

    /**
     * @return list<array{heading: string, columns: list<string>, rows: list<list<string>>, note?: string}>
     */
    private function placeSections(): array
    {
        $zones = Schema::hasTable('hazard_zones')
            ? HazardZone::query()->with('type')->orderBy('name')->get()
            : collect();
        $shelterSections = $this->shelterSections(now()->toDateString(), now()->toDateString());

        return [
            $this->tableSection(
                'Hazard areas',
                ['Area', 'Type', 'Barangay', 'Shown to the public'],
                $zones->map(fn (HazardZone $zone) => [
                    $zone->name,
                    (string) ($zone->type?->name ?? '—'),
                    $zone->barangay ?: '—',
                    $zone->is_active ? 'Yes' : 'No',
                ])->all(),
                $zones->count(),
                'This is the map at the moment the file is made.'
            ),
            $shelterSections[0],
        ];
    }

    /**
     * @return array{heading: string, columns: list<string>, rows: list<list<string>>, note?: string}
     */
    private function messageSection(string $from, string $to): array
    {
        if (! Schema::hasTable('sms_logs')) {
            return $this->tableSection('Texts sent', ['When', 'To', 'Case', 'Status', 'Message'], [], 0);
        }

        $query = SmsLog::query()->with('incident:id,tracking_number')->whereBetween('created_at', $this->dayWindow($from, $to))->latest();
        $total = (clone $query)->count();
        $rows = $query->limit(200)->get()->map(function (SmsLog $log) {
            $status = is_object($log->status) && method_exists($log->status, 'label') ? $log->status->label() : (string) $log->status;

            return [
                ($log->sent_at ?? $log->created_at)?->format('M j, Y g:i a') ?? '—',
                (string) $log->recipient_phone,
                (string) ($log->incident?->tracking_number ?? '—'),
                $status,
                $this->clip((string) $log->message, 180),
            ];
        })->all();

        return $this->tableSection('Texts sent', ['When', 'To', 'Case', 'Status', 'Message'], $rows, $total);
    }

    /**
     * @return array{heading: string, columns: list<string>, rows: list<list<string>>, note?: string}
     */
    private function paperSection(string $from, string $to): array
    {
        if (! Schema::hasTable('incident_documents')) {
            return $this->tableSection('Papers filed', ['When', 'Case', 'Paper', 'File', 'Filed by'], [], 0);
        }

        $query = IncidentDocument::query()->with(['incident:id,tracking_number', 'uploader:id,name'])->whereBetween('created_at', $this->dayWindow($from, $to))->latest();
        $total = (clone $query)->count();
        $rows = $query->limit(200)->get()->map(function (IncidentDocument $paper) {
            $type = is_object($paper->document_type) && method_exists($paper->document_type, 'label')
                ? $paper->document_type->label()
                : (string) $paper->document_type;

            return [
                $paper->created_at?->format('M j, Y g:i a') ?? '—',
                (string) ($paper->incident?->tracking_number ?? '—'),
                $type,
                $this->clip((string) $paper->original_filename, 80),
                (string) ($paper->uploader?->name ?? '—'),
            ];
        })->all();

        return $this->tableSection('Papers filed', ['When', 'Case', 'Paper', 'File', 'Filed by'], $rows, $total);
    }

    /**
     * @return array{heading: string, columns: list<string>, rows: list<list<string>>, note?: string}
     */
    private function supportSection(string $from, string $to): array
    {
        if (! Schema::hasTable('feedback_submissions')) {
            return $this->tableSection('Support messages', ['When', 'Topic', 'Subject', 'From', 'Status'], [], 0);
        }

        $query = FeedbackSubmission::query()->whereBetween('created_at', $this->dayWindow($from, $to))->latest();
        $total = (clone $query)->count();
        $rows = $query->limit(200)->get()->map(fn (FeedbackSubmission $item) => [
            $item->created_at?->format('M j, Y g:i a') ?? '—',
            $item->categoryLabel(),
            $this->clip((string) $item->subject, 80),
            $this->clip((string) ($item->submitter_name ?: '—'), 60),
            ucfirst((string) $item->status),
        ])->all();

        return $this->tableSection('Support messages', ['When', 'Topic', 'Subject', 'From', 'Status'], $rows, $total);
    }

    /**
     * @return array{heading: string, columns: list<string>, rows: list<list<string>>, note?: string}
     */
    private function noticeSection(string $from, string $to): array
    {
        if (! Schema::hasTable('announcements')) {
            return $this->tableSection('Public notices', ['When', 'Title', 'On the public page', 'Text'], [], 0);
        }

        $query = Announcement::query()->whereBetween('published_at', $this->dayWindow($from, $to))->latest('published_at');
        $total = (clone $query)->count();
        $rows = $query->limit(200)->get()->map(fn (Announcement $notice) => [
            $notice->published_at?->format('M j, Y g:i a') ?? '—',
            $this->clip((string) $notice->title, 80),
            $notice->is_published ? 'Yes' : 'No',
            $this->clip(trim(preg_replace('/\s+/', ' ', (string) $notice->body) ?? ''), 180),
        ])->all();

        return $this->tableSection('Public notices', ['When', 'Title', 'On the public page', 'Text'], $rows, $total);
    }

    /**
     * @return array{heading: string, columns: list<string>, rows: list<list<string>>, note?: string}
     */
    private function activitySection(string $from, string $to): array
    {
        if (! Schema::hasTable('activity_logs')) {
            return $this->tableSection('Office activity', ['When', 'Who', 'What happened'], [], 0);
        }

        $query = ActivityLog::query()->with('user:id,name')->whereBetween('created_at', $this->dayWindow($from, $to))->latest();
        $total = (clone $query)->count();
        $rows = $query->limit(200)->get()->map(fn (ActivityLog $log) => [
            $log->created_at?->format('M j, Y g:i a') ?? '—',
            (string) ($log->user?->name ?? 'System'),
            $this->clip((string) $log->description, 180),
        ])->all();

        return $this->tableSection('Office activity', ['When', 'Who', 'What happened'], $rows, $total);
    }

    /**
     * @param  list<list<string>>  $rows
     * @return array{heading: string, columns: list<string>, rows: list<list<string>>, note?: string}
     */
    private function tableSection(string $heading, array $columns, array $rows, int $total, ?string $note = null): array
    {
        if ($total > count($rows)) {
            $extra = 'Showing the first '.count($rows).' of '.$total.'.';
            $note = $note ? $note.' '.$extra : $extra;
        }

        return [
            'heading' => $heading,
            'columns' => $columns,
            'rows' => $rows,
            'note' => $note,
        ];
    }

    /** @return array{0: string, 1: string} */
    private function dayWindow(string $from, string $to): array
    {
        return [$from.' 00:00:00', $to.' 23:59:59'];
    }

    private function clip(string $value, int $length): string
    {
        $value = trim($value);
        if (mb_strlen($value) <= $length) {
            return $value !== '' ? $value : '—';
        }

        return mb_substr($value, 0, $length - 1).'…';
    }
}

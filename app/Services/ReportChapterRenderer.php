<?php

namespace App\Services;

use App\Enums\IncidentDocumentType;
use App\Enums\IncidentStatus;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\EvacuationCenter;
use App\Models\Evacuee;
use App\Models\FeedbackSubmission;
use App\Models\HazardZone;
use App\Models\Incident;
use App\Models\SmsLog;
use App\Support\ReportChapterCatalog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ReportChapterRenderer
{
    /**
     * @param  array{key?: string, type?: string, options?: array<string, mixed>}  $chapter
     * @return array{key: string, type: string, title: string, note: string, columns: list<string>, rows: list<list<string>>, preview: list<list<string>>, total: int, options: array<string, mixed>}
     */
    public function render(array $chapter, string $from, string $to): array
    {
        $type = (string) ($chapter['type'] ?? '');
        $options = is_array($chapter['options'] ?? null) ? $chapter['options'] : [];
        $built = match ($type) {
            'open_cases' => $this->openCases($options, $from, $to),
            'response' => $this->response($options, $from, $to),
            'repeats' => $this->repeats($from, $to),
            'shelter' => $this->shelter($options, $from, $to),
            'map' => $this->map(),
            'papers' => $this->papers($options, $from, $to),
            'texts' => $this->texts($options, $from, $to),
            'support' => $this->support($options, $from, $to),
            'notices' => $this->notices($options, $from, $to),
            'activity' => $this->activity($options, $from, $to),
            'one_case' => $this->oneCase($options),
            default => ['columns' => [], 'rows' => [], 'total' => 0, 'note' => 'This chapter is not recognized.'],
        };

        $rows = $built['rows'];
        $total = $built['total'];
        $note = $built['note'];
        if ($total > count($rows)) {
            $extra = 'Showing the first '.count($rows).' of '.$total.'.';
            $note = trim($note.' '.$extra);
        }

        return [
            'key' => (string) ($chapter['key'] ?? ''),
            'type' => $type,
            'title' => ReportChapterCatalog::title($type),
            'note' => $note,
            'columns' => $built['columns'],
            'rows' => $rows,
            'preview' => array_slice($rows, 0, 8),
            'total' => $total,
            'bars' => $built['bars'] ?? [],
            'options' => $options,
        ];
    }

    /**
     * The numbers a Monday briefing actually needs, for one period.
     *
     * @return array{
     *     reading: string,
     *     reports: int,
     *     open: int,
     *     outside: int,
     *     median: string,
     *     shelter_inside: int,
     *     shelter_outside: int,
     *     by_type: list<array{label: string, count: int}>,
     *     by_place: list<array{label: string, count: int}>,
     *     response: list<array{label: string, count: int}>
     * }
     */
    public function picture(string $from, string $to): array
    {
        $incidents = $this->incidents($from, $to, 'aor_only')->with('assignments')->get();
        $outside = $this->incidents($from, $to, 'outside_aor_only')->count();
        $open = $incidents->filter(fn (Incident $incident) => in_array($this->statusValue($incident), $this->openStatuses(), true))->count();
        $byType = $this->barsFrom($incidents, fn (Incident $incident) => $incident->incidentType?->name ?? 'Uncategorized');
        $byPlace = $this->barsFrom($incidents, fn (Incident $incident) => $incident->barangay ?: 'No barangay');
        $responseRows = $this->responseRows($incidents);
        $response = [];
        foreach ($responseRows as $row) {
            if (is_numeric($row[1])) {
                $response[] = ['label' => $row[0], 'count' => (int) $row[1]];
            }
        }
        $median = $responseRows[2][1] ?? 'Not enough assigned cases yet';
        $shelterInside = 0;
        $shelterOutside = 0;
        if (Schema::hasTable('evacuees')) {
            $still = Evacuee::query()->whereNull('checked_out_at')->get();
            $shelterOutside = $still->filter(fn (Evacuee $person) => $person->isOutsideMunicipality())->count();
            $shelterInside = $still->count() - $shelterOutside;
        }

        $reading = $incidents->count().' report'.($incidents->count() === 1 ? '' : 's').' inside Pamplona, '.$open.' still open.';
        if ($byType !== []) {
            $reading .= ' '.$byType[0]['label'].' is the most common type.';
        }
        if ($byPlace !== []) {
            $reading .= ' '.$byPlace[0]['label'].' has the most reports.';
        }
        if ($shelterOutside > 0) {
            $reading .= ' '.$shelterOutside.' shelter guest'.($shelterOutside === 1 ? '' : 's').' live outside Pamplona.';
        }

        return [
            'reading' => $reading,
            'reports' => $incidents->count(),
            'open' => $open,
            'outside' => $outside,
            'median' => $median,
            'shelter_inside' => $shelterInside,
            'shelter_outside' => $shelterOutside,
            'by_type' => $byType,
            'by_place' => $byPlace,
            'response' => $response,
        ];
    }

    /**
     * @param  Collection<int, Incident>  $incidents
     * @return list<array{label: string, count: int}>
     */
    private function barsFrom(Collection $incidents, callable $label): array
    {
        return $incidents->groupBy($label)
            ->map(fn (Collection $group, string $name) => ['label' => $name, 'count' => $group->count()])
            ->sortByDesc('count')
            ->take(6)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{columns: list<string>, rows: list<list<string>>, total: int, note: string}
     */
    private function openCases(array $options, string $from, string $to): array
    {
        $query = $this->incidents($from, $to, (string) ($options['aor_scope'] ?? 'aor_only'));
        if (! empty($options['barangay'])) {
            $query->where('barangay', $options['barangay']);
        }
        if (! empty($options['agency_id'])) {
            $agencyId = $options['agency_id'];
            $query->where(function ($inner) use ($agencyId) {
                $inner->where('agency_id', $agencyId)
                    ->orWhereHas('assignments', fn ($assignment) => $assignment->where('agency_id', $agencyId));
            });
        }
        if (! empty($options['incident_type_id'])) {
            $query->where('incident_type_id', $options['incident_type_id']);
        }
        $query->whereIn('status', $this->openStatuses());
        $models = (clone $query)->orderByDesc('reported_at')->get();
        $total = $models->count();
        $rows = $models->take(200)->map(fn (Incident $incident) => [
            (string) $incident->tracking_number,
            (string) ($incident->incidentType?->name ?? '—'),
            (string) ($incident->barangay ?: '—'),
            $this->statusLabel($incident),
            $incident->reported_at?->format('M j, Y g:i a') ?? '—',
        ])->all();

        return [
            'columns' => ['Tracking', 'Type', 'Barangay', 'Status', 'Reported'],
            'rows' => $rows,
            'total' => $total,
            'bars' => $this->barsFrom($models, fn (Incident $incident) => $incident->incidentType?->name ?? 'Uncategorized'),
            'note' => $total.' open case'.($total === 1 ? '' : 's').' reported in these dates.',
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{columns: list<string>, rows: list<list<string>>, total: int, note: string}
     */
    private function response(array $options, string $from, string $to): array
    {
        $mode = (string) ($options['view_mode'] ?? 'weekly');
        $current = $this->responsePeriodRows(
            $this->incidents($from, $to, 'all')->with('assignments')->get(),
            $mode,
            $from,
            $to
        );
        $rows = $current['rows'];
        $bars = $current['bars'];
        $note = 'Grouped by '.str_replace('_', ' ', $mode).'.';
        if (filter_var($options['compare'] ?? false, FILTER_VALIDATE_BOOLEAN) && filled($options['compare_from'] ?? null) && filled($options['compare_to'] ?? null)) {
            $earlier = $this->responsePeriodRows(
                $this->incidents((string) $options['compare_from'], (string) $options['compare_to'], 'all')->with('assignments')->get(),
                $mode,
                (string) $options['compare_from'],
                (string) $options['compare_to'],
                'Earlier: '
            );
            $rows = array_merge($rows, $earlier['rows']);
            $bars = array_merge($bars, $earlier['bars']);
            $note .= ' Compared with '.Carbon::parse($options['compare_from'])->format('M j, Y').' to '.Carbon::parse($options['compare_to'])->format('M j, Y').'.';
        }

        return [
            'columns' => ['Period', 'Reports', 'Still open', 'Median to first assignment'],
            'rows' => $rows,
            'total' => count($rows),
            'bars' => $bars,
            'note' => $note,
        ];
    }

    /**
     * @param  Collection<int, Incident>  $incidents
     * @return array{rows: list<list<string>>, bars: list<array{label: string, count: int}>}
     */
    private function responsePeriodRows(Collection $incidents, string $mode, string $from, string $to, string $prefix = ''): array
    {
        if ($mode === 'periodic') {
            $groups = collect([
                $from => $incidents,
            ]);
        } else {
            $groups = $incidents->groupBy(function (Incident $incident) use ($mode) {
                $date = $incident->reported_at;

                return match ($mode) {
                    'monthly' => $date->copy()->startOfMonth()->toDateString(),
                    'yearly' => $date->copy()->startOfYear()->toDateString(),
                    default => $date->copy()->startOfWeek()->toDateString(),
                };
            })->sortKeys();
        }

        $rows = [];
        $bars = [];
        foreach ($groups as $start => $periodIncidents) {
            $startDate = Carbon::parse((string) $start);
            $label = match ($mode) {
                'monthly' => $startDate->format('F Y'),
                'yearly' => $startDate->format('Y'),
                'periodic' => Carbon::parse($from)->format('M j, Y').' – '.Carbon::parse($to)->format('M j, Y'),
                default => 'Week of '.$startDate->format('M j, Y'),
            };
            $summary = $this->responseRows($periodIncidents);
            $open = (int) $summary[1][1];
            $label = $prefix.$label;
            $rows[] = [$label, $summary[0][1], $summary[1][1], $summary[2][1]];
            $bars[] = ['label' => $label, 'count' => $open];
        }

        return ['rows' => $rows, 'bars' => $bars];
    }

    /** @return array{columns: list<string>, rows: list<list<string>>, total: int, note: string} */
    private function repeats(string $from, string $to): array
    {
        $incidents = $this->incidents($from, $to, 'all')->get();
        $rows = $incidents->groupBy(fn (Incident $incident) => ($incident->barangay ?: 'No barangay')."\n".($incident->incidentType?->name ?? 'Uncategorized'))
            ->map(function (Collection $group, string $key) {
                [$place, $type] = explode("\n", $key, 2);

                return [$place, $type, (string) $group->count()];
            })
            ->filter(fn (array $row) => (int) $row[2] >= 2)
            ->sortByDesc(fn (array $row) => (int) $row[2])
            ->take(8)
            ->values()
            ->all();

        return [
            'columns' => ['Barangay', 'Type', 'Reports'],
            'rows' => $rows,
            'total' => count($rows),
            'bars' => array_map(fn (array $row) => ['label' => $row[0].' · '.$row[1], 'count' => (int) $row[2]], $rows),
            'note' => 'A place is listed when the same incident type was reported there at least twice.',
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{columns: list<string>, rows: list<list<string>>, total: int, note: string}
     */
    private function shelter(array $options, string $from, string $to): array
    {
        if (! Schema::hasTable('evacuees')) {
            return $this->emptyTable(['Name', 'Shelter', 'From', 'Checked in', 'Now'], 'The shelter registry is not on this system.');
        }
        $scope = (string) ($options['guest_scope'] ?? 'still_in');
        $query = Evacuee::query()->with('center');
        if ($scope === 'checked_in') {
            $query->whereBetween('checked_in_at', $this->window($from, $to));
        } elseif ($scope === 'outside') {
            $query->where('origin_scope', 'outside')->where(function ($inner) use ($from, $to) {
                $inner->whereNull('checked_out_at')->orWhereBetween('checked_in_at', $this->window($from, $to));
            });
        } else {
            $query->whereNull('checked_out_at');
        }
        $people = $query->orderBy('full_name')->get();
        if ($scope === 'outside') {
            $people = $people->filter(fn (Evacuee $person) => $person->isOutsideMunicipality())->values();
        }
        $rows = $people->take(200)->map(fn (Evacuee $person) => [
            $person->full_name,
            $person->center?->name ?? '—',
            $person->homeLabel(),
            $person->checked_in_at?->format('M j, Y g:i a') ?? '—',
            $person->checked_out_at ? 'Left '.$person->checked_out_at->format('M j') : 'Inside',
        ])->all();
        $note = match ($scope) {
            'checked_in' => 'People who checked in during these dates.',
            'outside' => 'Guests whose home is outside Pamplona.',
            default => 'People who are still inside a shelter.',
        };

        $still = $people->filter(fn (Evacuee $person) => $person->checked_out_at === null)->count();

        return [
            'columns' => ['Name', 'Shelter', 'From', 'Checked in', 'Now'],
            'rows' => $rows,
            'total' => $people->count(),
            'bars' => [
                ['label' => 'Inside', 'count' => $still],
                ['label' => 'Left', 'count' => $people->count() - $still],
            ],
            'note' => $note,
        ];
    }

    /** @return array{columns: list<string>, rows: list<list<string>>, total: int, note: string} */
    private function map(): array
    {
        $rows = [];
        if (Schema::hasTable('hazard_zones')) {
            foreach (HazardZone::query()->with('type')->orderBy('name')->get() as $zone) {
                $rows[] = [
                    'Hazard area',
                    $zone->name,
                    (string) ($zone->type?->name ?? '—'),
                    $zone->barangay ?: '—',
                    $zone->is_active ? 'Shown to the public' : 'Hidden',
                ];
            }
        }
        if (Schema::hasTable('evacuation_centers')) {
            $centers = EvacuationCenter::query()
                ->withCount(['evacuees as inside_count' => fn ($query) => $query->whereNull('checked_out_at')])
                ->orderBy('name')
                ->get();
            foreach ($centers as $center) {
                $rows[] = [
                    'Shelter',
                    $center->name,
                    $center->is_open ? 'Open' : 'Closed',
                    $center->barangay ?: '—',
                    $center->inside_count.' inside',
                ];
            }
        }

        return [
            'columns' => ['Kind', 'Name', 'Detail', 'Barangay', 'Now'],
            'rows' => array_slice($rows, 0, 200),
            'total' => count($rows),
            'note' => 'As of '.now()->format('M j, Y g:i a').'. The record dates do not filter the map.',
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{columns: list<string>, rows: list<list<string>>, total: int, note: string}
     */
    private function papers(array $options, string $from, string $to): array
    {
        $wanted = (string) ($options['document_type'] ?? 'all');
        $completion = (string) ($options['completion'] ?? 'missing');
        $forms = $wanted === 'all' ? IncidentDocumentType::values() : [$wanted];
        $incidents = Incident::query()
            ->with('incidentDocuments')
            ->whereIn('status', [IncidentStatus::Resolved->value, IncidentStatus::Closed->value])
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('resolved_at', $this->window($from, $to))
                    ->orWhere(function ($inner) use ($from, $to) {
                        $inner->whereNull('resolved_at')->whereBetween('reported_at', $this->window($from, $to));
                    });
            })
            ->orderByDesc('resolved_at')
            ->get();

        $matched = $incidents->filter(function (Incident $incident) use ($forms, $completion) {
            $have = $incident->incidentDocuments->map(function ($paper) {
                return $paper->document_type instanceof IncidentDocumentType ? $paper->document_type->value : (string) $paper->document_type;
            })->unique();
            $missing = array_values(array_diff($forms, $have->all()));

            return match ($completion) {
                'complete' => $missing === [],
                'missing' => $missing !== [],
                default => true,
            };
        })->values();

        $rows = $matched->take(200)->map(function (Incident $incident) use ($forms) {
            $have = $incident->incidentDocuments->map(function ($paper) {
                return $paper->document_type instanceof IncidentDocumentType ? $paper->document_type->value : (string) $paper->document_type;
            })->unique();
            $missing = array_values(array_diff($forms, $have->all()));
            $labels = array_map(fn (string $value) => IncidentDocumentType::tryFrom($value)?->shortLabel() ?? $value, $missing);

            return [
                (string) $incident->tracking_number,
                (string) ($incident->barangay ?: '—'),
                $this->statusLabel($incident),
                $labels === [] ? 'All forms on file' : implode(', ', $labels),
            ];
        })->all();

        return [
            'columns' => ['Tracking', 'Barangay', 'Status', 'Forms'],
            'rows' => $rows,
            'total' => $matched->count(),
            'note' => 'Resolved and closed cases only.',
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{columns: list<string>, rows: list<list<string>>, total: int, note: string}
     */
    private function texts(array $options, string $from, string $to): array
    {
        if (! Schema::hasTable('sms_logs')) {
            return $this->emptyTable(['When', 'To', 'Case', 'Status', 'Message'], '');
        }
        $query = SmsLog::query()->with('incident:id,tracking_number')->whereBetween('created_at', $this->window($from, $to));
        $status = (string) ($options['status'] ?? 'failed');
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $total = (clone $query)->count();
        $rows = $query->latest()->limit(200)->get()->map(function (SmsLog $log) {
            $state = is_object($log->status) && method_exists($log->status, 'label') ? $log->status->label() : (string) $log->status;

            return [
                ($log->sent_at ?? $log->created_at)?->format('M j, Y g:i a') ?? '—',
                (string) $log->recipient_phone,
                (string) ($log->incident?->tracking_number ?? '—'),
                $state,
                $this->clip((string) $log->message, 180),
            ];
        })->all();

        return [
            'columns' => ['When', 'To', 'Case', 'Status', 'Message'],
            'rows' => $rows,
            'total' => $total,
            'note' => $status === 'all' ? 'Every text in these dates.' : 'Texts marked '.str_replace('_', ' ', $status).'.',
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{columns: list<string>, rows: list<list<string>>, total: int, note: string}
     */
    private function support(array $options, string $from, string $to): array
    {
        if (! Schema::hasTable('feedback_submissions')) {
            return $this->emptyTable(['When', 'Topic', 'Subject', 'From', 'Status'], '');
        }
        $query = FeedbackSubmission::query()->whereBetween('created_at', $this->window($from, $to));
        $status = (string) ($options['status'] ?? 'new');
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $category = (string) ($options['category'] ?? 'all');
        if ($category !== 'all') {
            $query->where('category', $category);
        }
        $source = (string) ($options['source'] ?? 'all');
        if ($source !== 'all') {
            $query->where('submitted_via', $source);
        }
        $total = (clone $query)->count();
        $rows = $query->latest()->limit(200)->get()->map(fn (FeedbackSubmission $item) => [
            $item->created_at?->format('M j, Y g:i a') ?? '—',
            $item->categoryLabel(),
            $this->clip((string) $item->subject, 80),
            $this->clip((string) ($item->submitter_name ?: '—'), 60),
            ucfirst((string) $item->status),
        ])->all();

        return [
            'columns' => ['When', 'Topic', 'Subject', 'From', 'Status'],
            'rows' => $rows,
            'total' => $total,
            'note' => 'Support messages in these dates.',
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{columns: list<string>, rows: list<list<string>>, total: int, note: string}
     */
    private function notices(array $options, string $from, string $to): array
    {
        if (! Schema::hasTable('announcements')) {
            return $this->emptyTable(['When', 'Title', 'On the public page', 'Text'], '');
        }
        $query = Announcement::query()->whereBetween('published_at', $this->window($from, $to));
        $published = (string) ($options['published'] ?? 'yes');
        if ($published === 'yes') {
            $query->where('is_published', true);
        } elseif ($published === 'no') {
            $query->where('is_published', false);
        }
        $total = (clone $query)->count();
        $rows = $query->latest('published_at')->limit(200)->get()->map(fn (Announcement $notice) => [
            $notice->published_at?->format('M j, Y g:i a') ?? '—',
            $this->clip((string) $notice->title, 80),
            $notice->is_published ? 'Yes' : 'No',
            $this->clip(trim(preg_replace('/\s+/', ' ', (string) $notice->body) ?? ''), 180),
        ])->all();

        return [
            'columns' => ['When', 'Title', 'On the public page', 'Text'],
            'rows' => $rows,
            'total' => $total,
            'note' => 'Notices whose publish date falls in this record.',
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{columns: list<string>, rows: list<list<string>>, total: int, note: string}
     */
    private function activity(array $options, string $from, string $to): array
    {
        if (! Schema::hasTable('activity_logs')) {
            return $this->emptyTable(['When', 'Who', 'What happened'], '');
        }
        $query = ActivityLog::query()->with('user:id,name')->whereBetween('created_at', $this->window($from, $to));
        $name = (string) ($options['log_name'] ?? 'all');
        if ($name !== 'all') {
            $query->where('log_name', $name);
        }
        $total = (clone $query)->count();
        $rows = $query->latest()->limit(200)->get()->map(fn (ActivityLog $log) => [
            $log->created_at?->format('M j, Y g:i a') ?? '—',
            (string) ($log->user?->name ?? 'System'),
            $this->clip((string) $log->description, 180),
        ])->all();

        return [
            'columns' => ['When', 'Who', 'What happened'],
            'rows' => $rows,
            'total' => $total,
            'note' => $name === 'all' ? 'Every audit entry in these dates.' : 'Log: '.$name.'.',
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{columns: list<string>, rows: list<list<string>>, total: int, note: string}
     */
    private function oneCase(array $options): array
    {
        $tracking = trim((string) ($options['tracking_number'] ?? ''));
        $incident = $tracking === '' ? null : Incident::query()->with('incidentType')->where('tracking_number', $tracking)->first();
        if (! $incident) {
            return $this->emptyTable(['Field', 'Detail'], 'No case uses that tracking number.');
        }
        $priority = is_object($incident->priority) && method_exists($incident->priority, 'label')
            ? $incident->priority->label()
            : ucfirst((string) $incident->priority);

        return [
            'columns' => ['Field', 'Detail'],
            'rows' => [
                ['Tracking', (string) $incident->tracking_number],
                ['Type', (string) ($incident->incidentType?->name ?? '—')],
                ['Barangay', (string) ($incident->barangay ?: '—')],
                ['Status', $this->statusLabel($incident)],
                ['Priority', $priority],
                ['Reported', $incident->reported_at?->format('M j, Y g:i a') ?? '—'],
                ['What happened', $this->clip((string) $incident->description, 400)],
            ],
            'total' => 1,
            'note' => 'This is the case summary. The full paper file stays on the case.',
        ];
    }

    /** @param  Collection<int, Incident>  $incidents */
    private function responseRows(Collection $incidents): array
    {
        $open = $incidents->filter(fn (Incident $incident) => in_array($this->statusValue($incident), $this->openStatuses(), true))->count();
        $minutes = [];
        foreach ($incidents as $incident) {
            $assignedAt = $incident->assignments->filter(fn ($assignment) => $assignment->assigned_at)->min('assigned_at');
            if ($incident->reported_at && $assignedAt) {
                $minutes[] = max(0, (int) $incident->reported_at->diffInMinutes(Carbon::parse($assignedAt)));
            }
        }

        return [
            ['Reports in the period', (string) $incidents->count()],
            ['Still open', (string) $open],
            ['Median time to the first assignment', $this->medianLabel($minutes)],
        ];
    }

    /** @param  list<int>  $minutes */
    private function medianLabel(array $minutes): string
    {
        if ($minutes === []) {
            return 'Not enough assigned cases yet';
        }
        sort($minutes);
        $mid = (int) floor((count($minutes) - 1) / 2);
        $median = count($minutes) % 2 === 0
            ? (int) round(($minutes[$mid] + $minutes[$mid + 1]) / 2)
            : $minutes[$mid];

        return $median < 60 ? $median.' '.($median === 1 ? 'minute' : 'minutes') : round($median / 60, 1).' hours';
    }

    private function incidents(string $from, string $to, string $scope)
    {
        $query = Incident::query()->with('incidentType')->whereBetween('reported_at', $this->window($from, $to));
        $outside = IncidentStatus::OutsideAor->value;
        match ($scope) {
            'outside_aor_only' => $query->where('status', $outside),
            'all' => $query,
            default => $query->where('status', '!=', $outside),
        };

        return $query;
    }

    /** @return list<string> */
    private function openStatuses(): array
    {
        return [
            IncidentStatus::Submitted->value,
            IncidentStatus::Received->value,
            IncidentStatus::Assigned->value,
            IncidentStatus::InProgress->value,
            IncidentStatus::PendingInfo->value,
        ];
    }

    private function statusValue(Incident $incident): string
    {
        return $incident->status instanceof IncidentStatus ? $incident->status->value : (string) $incident->status;
    }

    private function statusLabel(Incident $incident): string
    {
        return $incident->status instanceof IncidentStatus ? $incident->status->label() : ucfirst(str_replace('_', ' ', (string) $incident->status));
    }

    /** @return array{0: string, 1: string} */
    private function window(string $from, string $to): array
    {
        return [$from.' 00:00:00', $to.' 23:59:59'];
    }

    /**
     * @param  list<string>  $columns
     * @return array{columns: list<string>, rows: list<list<string>>, total: int, note: string}
     */
    private function emptyTable(array $columns, string $note): array
    {
        return ['columns' => $columns, 'rows' => [], 'total' => 0, 'note' => $note];
    }

    private function clip(string $value, int $length): string
    {
        $value = trim($value);
        if ($value === '') {
            return '—';
        }
        if (mb_strlen($value) <= $length) {
            return $value;
        }

        return mb_substr($value, 0, $length - 1).'…';
    }
}

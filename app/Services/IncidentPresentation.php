<?php

namespace App\Services;

use App\Enums\IncidentStatus;
use App\Models\Evacuee;
use App\Models\Incident;
use App\Models\IncidentType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class IncidentPresentation
{
    public const OUTSIDE = 'outside';

    /**
     * @return array{
     *     filters: array{dataset: string, q: string, barangay: ?string, incident_type_id: ?int, date_from: ?string, date_to: ?string},
     *     columns: list<string>,
     *     rows: list<array{cells: list<string>, url: ?string}>,
     *     pageRows: list<array{cells: list<string>, url: ?string}>,
     *     page: LengthAwarePaginator,
     *     charts: list<array{title: string, rows: list<array{label: string, count: int}>}>,
     *     metrics: list<array{label: string, value: int}>,
     *     title: string,
     *     period: string,
     *     summary: string,
     *     generated_at: string
     * }
     */
    public function build(Request $request): array
    {
        $filters = $this->filters($request);
        $matched = $filters['dataset'] === 'evacuees'
            ? $this->evacuees($filters)
            : $this->incidents($filters);
        $columns = $matched['columns'];
        $rows = $matched['rows'];
        $perPage = 25;
        $pageNumber = max(1, (int) $request->query('page', 1));
        $pageRows = array_values(array_slice($rows, ($pageNumber - 1) * $perPage, $perPage));

        return [
            'filters' => $filters,
            'columns' => $columns,
            'rows' => $rows,
            'pageRows' => $pageRows,
            'page' => new Paginator($pageRows, count($rows), $perPage, $pageNumber, [
                'path' => route('admin.reports.index'),
                'query' => $request->query(),
            ]),
            'charts' => $matched['charts'],
            'metrics' => $matched['metrics'],
            'title' => $matched['title'],
            'period' => $this->periodLabel($filters['date_from'], $filters['date_to']),
            'summary' => $this->summaryLine($filters, count($rows)),
            'generated_at' => now()->format('M j, Y g:i A'),
        ];
    }

    /**
     * @return array{dataset: string, q: string, barangay: ?string, incident_type_id: ?int, date_from: ?string, date_to: ?string}
     */
    public function filters(Request $request): array
    {
        $request->merge([
            'dataset' => $request->input('dataset') ?: 'incidents',
            'barangay' => $request->input('barangay') ?: null,
            'incident_type_id' => $request->input('incident_type_id') ?: null,
            'date_from' => $request->input('date_from') ?: null,
            'date_to' => $request->input('date_to') ?: null,
        ]);

        $validated = $request->validate([
            'dataset' => ['required', 'string', Rule::in(['incidents', 'evacuees'])],
            'q' => ['nullable', 'string', 'max:100'],
            'barangay' => ['nullable', 'string', Rule::in(array_merge(config('raniag.barangays'), [self::OUTSIDE]))],
            'incident_type_id' => ['nullable', 'integer', 'exists:incident_types,id'],
            'date_from' => ['nullable', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'before_or_equal:today', Rule::when($request->filled('date_from'), 'after_or_equal:date_from')],
        ]);

        return [
            'dataset' => $validated['dataset'],
            'q' => trim((string) ($validated['q'] ?? '')),
            'barangay' => $validated['barangay'] ?? null,
            'incident_type_id' => isset($validated['incident_type_id']) ? (int) $validated['incident_type_id'] : null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ];
    }

    /**
     * @param  array{dataset: string, q: string, barangay: ?string, incident_type_id: ?int, date_from: ?string, date_to: ?string}  $filters
     * @return array{title: string, columns: list<string>, rows: list<array{cells: list<string>, url: ?string}>, charts: list<array{title: string, rows: list<array{label: string, count: int}>}>}
     */
    private function incidents(array $filters): array
    {
        $query = Incident::query()->with('incidentType:id,name')->orderByDesc('reported_at')->orderByDesc('id');
        $this->between($query, 'reported_at', $filters['date_from'], $filters['date_to']);
        $this->incidentPlace($query, $filters['barangay']);

        if ($filters['incident_type_id']) {
            $query->where('incident_type_id', $filters['incident_type_id']);
        }

        if ($filters['q'] !== '') {
            $term = $filters['q'];
            $query->where(function ($inner) use ($term) {
                $inner->where('tracking_number', 'like', '%'.$term.'%')
                    ->orWhere('title', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%')
                    ->orWhere('location_address', 'like', '%'.$term.'%');
            });
        }

        $incidents = $query->get();
        $rows = $incidents->map(function (Incident $incident) {
            $status = $incident->status;
            $priority = $incident->priority;
            $place = $this->incidentPlaceLabel($incident);

            return [
                'cells' => [
                    (string) $incident->tracking_number,
                    $incident->incidentType?->name ?? 'Unspecified',
                    $place,
                    is_object($status) && method_exists($status, 'label') ? $status->label() : (string) $status,
                    is_object($priority) && method_exists($priority, 'label') ? $priority->label() : (string) $priority,
                    $incident->reported_at?->format('M j, Y g:i A') ?? '',
                ],
                'url' => route('admin.incidents.show', $incident),
            ];
        })->values()->all();
        $activeStatuses = [
            IncidentStatus::Submitted->value,
            IncidentStatus::Received->value,
            IncidentStatus::Assigned->value,
            IncidentStatus::InProgress->value,
            IncidentStatus::PendingInfo->value,
        ];
        $activeCount = $incidents->filter(function (Incident $incident) use ($activeStatuses) {
            $status = $incident->status instanceof \BackedEnum ? $incident->status->value : (string) $incident->status;

            return in_array($status, $activeStatuses, true);
        })->count();
        $urgentCount = $incidents->filter(function (Incident $incident) {
            $priority = $incident->priority instanceof \BackedEnum ? $incident->priority->value : (string) $incident->priority;

            return in_array(strtolower($priority), ['high', 'critical'], true);
        })->count();
        $placeCount = $incidents
            ->map(fn (Incident $incident) => $this->incidentPlaceLabel($incident))
            ->filter(fn (string $place) => $place !== '—')
            ->unique()
            ->count();

        return [
            'title' => 'RANIAG Incident Report',
            'columns' => ['Tracking', 'Incident type', 'Barangay', 'Status', 'Priority', 'Reported'],
            'rows' => $rows,
            'metrics' => [
                ['label' => 'Reports in selection', 'value' => $incidents->count()],
                ['label' => 'Open / in progress', 'value' => $activeCount],
                ['label' => 'High or critical', 'value' => $urgentCount],
                ['label' => 'Areas represented', 'value' => $placeCount],
            ],
            'charts' => [
                ['title' => 'Reports by incident type', 'rows' => $this->counts($incidents, fn (Incident $incident) => $incident->incidentType?->name ?? 'Unspecified')],
                ['title' => 'Reports by barangay', 'rows' => $this->counts($incidents, fn (Incident $incident) => $this->incidentPlaceLabel($incident))],
                ['title' => 'Reports by status', 'rows' => $this->counts($incidents, function (Incident $incident) {
                    $status = $incident->status;

                    return is_object($status) && method_exists($status, 'label') ? $status->label() : (string) $status;
                })],
            ],
        ];
    }

    /**
     * @param  array{dataset: string, q: string, barangay: ?string, incident_type_id: ?int, date_from: ?string, date_to: ?string}  $filters
     * @return array{title: string, columns: list<string>, rows: list<array{cells: list<string>, url: ?string}>, charts: list<array{title: string, rows: list<array{label: string, count: int}>}>}
     */
    private function evacuees(array $filters): array
    {
        $query = Evacuee::query()->with('center:id,name')->orderByDesc('checked_in_at')->orderByDesc('id');
        $this->between($query, 'checked_in_at', $filters['date_from'], $filters['date_to']);

        if ($filters['barangay'] === self::OUTSIDE) {
            $query->where('origin_scope', 'outside');
        } elseif ($filters['barangay']) {
            $query->where('origin_scope', 'inside')->where('barangay', $filters['barangay']);
        }

        if ($filters['q'] !== '') {
            $term = $filters['q'];
            $query->where(function ($inner) use ($term) {
                $inner->where('full_name', 'like', '%'.$term.'%')
                    ->orWhere('barangay', 'like', '%'.$term.'%')
                    ->orWhere('origin_place', 'like', '%'.$term.'%')
                    ->orWhereHas('center', fn ($center) => $center->where('name', 'like', '%'.$term.'%'));
            });
        }

        $people = $query->get();
        $rows = $people->map(function (Evacuee $person) {
            $age = $person->age === null ? '—' : (string) $person->age;
            $sex = trim((string) $person->sex);

            return [
                'cells' => [
                    $person->full_name,
                    $person->center?->name ?? '—',
                    $person->homeLabel(),
                    $sex !== '' ? $age.' / '.$sex : $age,
                    $person->checked_in_at?->format('M j, Y g:i A') ?? '—',
                    $person->checked_out_at ? 'Left '.$person->checked_out_at->format('M j, Y') : 'Inside',
                ],
                'url' => null,
            ];
        })->values()->all();
        $insideCount = $people->filter(fn (Evacuee $person) => $person->checked_out_at === null)->count();
        $outsideCount = $people->filter(fn (Evacuee $person) => $person->isOutsideMunicipality())->count();
        $shelterCount = $people->map(fn (Evacuee $person) => $person->center?->name)->filter()->unique()->count();

        return [
            'title' => 'Evacuee registry',
            'columns' => ['Name', 'Shelter', 'From', 'Age / sex', 'Checked in', 'Status'],
            'rows' => $rows,
            'metrics' => [
                ['label' => 'People in selection', 'value' => $people->count()],
                ['label' => 'Still inside', 'value' => $insideCount],
                ['label' => 'From outside Pamplona', 'value' => $outsideCount],
                ['label' => 'Shelters represented', 'value' => $shelterCount],
            ],
            'charts' => [
                ['title' => 'Evacuees by shelter', 'rows' => $this->counts($people, fn (Evacuee $person) => $person->center?->name ?? 'No shelter')],
                ['title' => 'Evacuees by origin', 'rows' => $this->counts($people, fn (Evacuee $person) => $person->isOutsideMunicipality() ? 'Outside Pamplona' : 'Pamplona')],
                ['title' => 'Evacuees by status', 'rows' => $this->counts($people, fn (Evacuee $person) => $person->checked_out_at ? 'Left' : 'Inside')],
            ],
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Incident>  $query
     */
    private function incidentPlace($query, ?string $barangay): void
    {
        if ($barangay === self::OUTSIDE) {
            $known = config('raniag.barangays', []);
            $query->where(function ($inner) use ($known) {
                $inner->where('status', IncidentStatus::OutsideAor->value)
                    ->orWhere('meta->within_jurisdiction', false)
                    ->orWhere(function ($place) use ($known) {
                        $place->whereNotNull('barangay')
                            ->where('barangay', '!=', '')
                            ->whereNotIn('barangay', $known);
                    });
            });

            return;
        }

        if ($barangay) {
            $query->where('barangay', $barangay);
        }
    }

    private function incidentPlaceLabel(Incident $incident): string
    {
        $outside = $incident->status === IncidentStatus::OutsideAor
            || ($incident->meta['within_jurisdiction'] ?? null) === false
            || ($incident->barangay && ! in_array($incident->barangay, config('raniag.barangays', []), true));

        if ($outside) {
            $place = trim((string) ($incident->location_address ?: $incident->barangay));

            return $place !== '' ? 'Outside Pamplona — '.$place : 'Outside Pamplona';
        }

        return $incident->barangay ?: '—';
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     */
    private function between($query, string $column, ?string $from, ?string $to): void
    {
        if ($from && $to) {
            $query->whereBetween($column, [$from.' 00:00:00', $to.' 23:59:59']);
        } elseif ($from) {
            $query->where($column, '>=', $from.' 00:00:00');
        } elseif ($to) {
            $query->where($column, '<=', $to.' 23:59:59');
        }
    }

    public function periodLabel(?string $from, ?string $to): string
    {
        if (! $from && ! $to) {
            return 'All dates';
        }
        if ($from && $to) {
            return \Carbon\Carbon::parse($from)->format('M j, Y').' – '.\Carbon\Carbon::parse($to)->format('M j, Y');
        }
        if ($from) {
            return 'From '.\Carbon\Carbon::parse($from)->format('M j, Y');
        }

        return 'Through '.\Carbon\Carbon::parse($to)->format('M j, Y');
    }

    /**
     * @param  array{dataset: string, q: string, barangay: ?string, incident_type_id: ?int, date_from: ?string, date_to: ?string}  $filters
     */
    public function summaryLine(array $filters, int $count): string
    {
        $place = $filters['barangay'] === self::OUTSIDE
            ? 'Outside Pamplona'
            : ($filters['barangay'] ?: 'All places');
        $records = $count === 1 ? 'record' : 'records';
        $search = $filters['q'] !== '' ? ' · Search: '.$filters['q'] : '';

        if ($filters['dataset'] === 'evacuees') {
            return 'Evacuee registry · '.$place.$search.' · '.$count.' '.$records;
        }

        $type = 'All incident types';
        if ($filters['incident_type_id']) {
            $type = (string) (IncidentType::query()->whereKey($filters['incident_type_id'])->value('name') ?: 'Selected type');
        }

        return 'Incident reports · '.$place.' · '.$type.$search.' · '.$count.' '.$records;
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @return list<array{label: string, count: int}>
     */
    private function counts(Collection $items, callable $label): array
    {
        if ($items->isEmpty()) {
            return [['label' => 'No records', 'count' => 0]];
        }

        return $items
            ->groupBy($label)
            ->map(fn (Collection $group, string $name) => ['label' => $name, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values()
            ->all();
    }
}

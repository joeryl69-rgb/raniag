<?php

namespace App\Repositories;

use App\Models\Incident;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use App\Support\Filters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class IncidentRepository implements IncidentRepositoryInterface
{
    public function findById(int $id): ?Incident
    {
        $incident = Incident::query()
            ->with(['incidentType', 'agency'])
            ->find($id);

        if ($incident) {
            // Load status updates using a concrete datetime value to avoid SQL column comparisons
            $statusUpdates = $incident->statusUpdates()
                ->where('created_at', '>=', $incident->created_at)
                ->orderBy('created_at')
                ->get();

            $incident->setRelation('statusUpdates', $statusUpdates);

            // Also guard evidence to avoid showing orphaned files from prior DB truncation
            $evidence = $incident->evidence()
                ->where('created_at', '>=', $incident->created_at)
                ->orderBy('created_at')
                ->get();

            $incident->setRelation('evidence', $evidence);

            // Same guard as evidence above, applied to attached case documents.
            $documents = $incident->incidentDocuments()
                ->where('created_at', '>=', $incident->created_at)
                ->orderBy('created_at')
                ->get();

            $incident->setRelation('incidentDocuments', $documents);

            // Prevent old records from previous incident IDs from leaking into current incident views.
            $assignments = $incident->assignments()
                ->orderBy('created_at')
                ->get();
            $incident->setRelation('assignments', $assignments);

            $resolutions = $incident->resolutions()
                ->where('created_at', '>=', $incident->created_at)
                ->orderBy('created_at')
                ->get();
            $incident->setRelation('resolutions', $resolutions);

            $documentRequests = $incident->documentRequests()
                ->where('created_at', '>=', $incident->created_at)
                ->orderBy('created_at')
                ->get();
            $incident->setRelation('documentRequests', $documentRequests);

            $incident->loadMissing(['assignments.agency', 'assignments.assignee', 'incidentType']);
        }

        return $incident;
    }

    public function findByTrackingNumber(string $trackingNumber): ?Incident
    {
        return Incident::query()
            ->with([
                'incidentType',
                'agency',
                'statusUpdates' => fn ($query) => $query->where('is_public', true)->latest(),
                'assignments' => fn ($query) => $query->where('is_active', true)->latest('created_at'),
                'assignments.agency',
                'assignments.assignee',
            ])
            ->where('tracking_number', $trackingNumber)
            ->first();
    }

    public function create(array $attributes): Incident
    {
        return Incident::query()->create($attributes);
    }

    public function update(Incident $incident, array $attributes): Incident
    {
        $incident->update($attributes);

        return $incident->fresh();
    }

    public function paginateForAgency(int $agencyId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        // Dispatches are based on assignments (one-to-many), not on incidents.agency_id.
        $query = Incident::query()
            ->with([
                'incidentType',
                'agency',
                'currentAssignments' => fn ($q) => $q
                    ->where('assignments.agency_id', $agencyId)
                    ->where('assignments.is_active', true)
                    ->latest('assignments.created_at'),
            ])
            ->whereHas('assignments', function ($q) use ($agencyId) {
                $q->where('assignments.agency_id', $agencyId);
            });

        // Resolved incidents move to the Document Request workflow and no
        // longer appear in the active incidents list, regardless of filter.
        $query->whereNotIn('status', ['resolved', 'closed']);

        if (! empty($filters['status'] ?? null) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'] ?? null) && $filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['barangay'] ?? null) && $filters['barangay'] !== 'all') {
            $query->where('barangay', $filters['barangay']);
        }

        if (! empty($filters['q'] ?? null)) {
            $q = trim($filters['q']);
            $query->where(function ($w) use ($q) {
                $w->where('tracking_number', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%");
            });
        }

        Filters::dateRange($query, 'reported_at', $filters);

        $this->applyQueueOrdering($query, $filters);

        return $query->paginate($perPage)->withQueryString();
    }

    public function paginateForPersonnel(int $personnelId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        // Dispatches are based on assignments (one-to-many), not on incidents.assigned_to.
        $query = Incident::query()
            ->with([
                'incidentType',
                'agency',
                'currentAssignments' => function ($q) use ($personnelId, $filters) {
                    $q->where('assignments.is_active', true)
                        ->where(function ($w) use ($personnelId, $filters) {
                            $w->where('assignments.assigned_to', $personnelId);
                            if ($agencyId = $filters['viewer_agency_id'] ?? null) {
                                $w->orWhere('assignments.agency_id', $agencyId);
                            }
                        })
                        ->latest('assignments.created_at');
                },
            ])
            ->whereHas('assignments', function ($q) use ($personnelId, $filters) {
                $agencyId = $filters['viewer_agency_id'] ?? null;
                $q->where(function ($w) use ($personnelId, $agencyId) {
                    $w->where('assignments.assigned_to', $personnelId);
                    if ($agencyId) {
                        $w->orWhere('assignments.agency_id', $agencyId);
                    }
                });
            });

        // Resolved incidents move to the Document Request workflow and no
        // longer appear in the active incidents list, regardless of filter.
        $query->whereNotIn('status', ['resolved', 'closed']);

        if (! empty($filters['status'] ?? null) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'] ?? null) && $filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['barangay'] ?? null) && $filters['barangay'] !== 'all') {
            $query->where('barangay', $filters['barangay']);
        }

        if (! empty($filters['q'] ?? null)) {
            $q = trim($filters['q']);
            $query->where(function ($w) use ($q) {
                $w->where('tracking_number', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%");
            });
        }

        Filters::dateRange($query, 'reported_at', $filters);

        $this->applyQueueOrdering($query, $filters);

        return $query->paginate($perPage)->withQueryString();
    }

    public function paginateAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Incident::query()->with(['incidentType', 'agency']);

        if (! empty($filters['q'] ?? null)) {
            $q = trim($filters['q']);
            $query->where(function ($w) use ($q) {
                $w->where('tracking_number', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('reporter_name', 'like', "%{$q}%");
            });
        }

        if (! empty($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['incident_type_id'] ?? null)) {
            $query->where('incident_type_id', (int) $filters['incident_type_id']);
        }

        if (! empty($filters['priority'] ?? null) && $filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['jurisdiction'] ?? null)) {
            if ($filters['jurisdiction'] === 'outside') {
                $query->where('meta->within_jurisdiction', false);
            } elseif ($filters['jurisdiction'] === 'inside') {
                $query->where('meta->within_jurisdiction', true);
            }
        }

        Filters::dateRange($query, 'reported_at', $filters);

        $this->applyQueueOrdering($query, $filters, true);

        return $query->paginate($perPage)
            ->withQueryString();
    }

    private function applyQueueOrdering(Builder $query, array $filters, bool $admin = false): void
    {
        $sort = $filters['sort'] ?? 'reported_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $sortable = ['reported_at', 'priority', 'status', 'barangay'];
        if ($admin) {
            $sortable[] = 'tracking_number';
        }
        if (! in_array($sort, $sortable, true)) {
            $sort = 'reported_at';
        }

        $priorityOrder = "CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END";

        if ($sort === 'priority') {
            $query->orderByRaw($priorityOrder)
                ->orderBy('reported_at', $direction);

            return;
        }

        $query->orderBy($sort, $direction)
            ->orderByRaw($priorityOrder);

        if ($sort !== 'reported_at') {
            $query->orderBy('reported_at', 'desc');
        }
    }
}

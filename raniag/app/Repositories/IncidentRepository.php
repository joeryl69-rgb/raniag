<?php

namespace App\Repositories;

use App\Models\Incident;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

            // Prevent old records from previous incident IDs from leaking into current incident views.
            $assignments = $incident->assignments()
                ->where('created_at', '>=', $incident->created_at)
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

    public function paginateForAgency(int $agencyId, int $perPage = 15): LengthAwarePaginator
    {
        // Dispatches are based on assignments (one-to-many), not on incidents.agency_id.
        return Incident::query()
            ->with(['incidentType', 'agency'])
            ->whereHas('assignments', function ($q) use ($agencyId) {
                $q->where('assignments.agency_id', $agencyId)
                    ->whereColumn('assignments.created_at', '>=', 'incidents.created_at');
            })
            ->latest('reported_at')
            ->paginate($perPage);
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

        return $query->latest('reported_at')->paginate($perPage);
    }
}

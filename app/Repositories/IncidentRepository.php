<?php

namespace App\Repositories;

use App\Models\Incident;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IncidentRepository implements IncidentRepositoryInterface
{
    public function findById(int $id): ?Incident
    {
        return Incident::query()
            ->with(['incidentType', 'agency', 'statusUpdates'])
            ->find($id);
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
        return Incident::query()
            ->with(['incidentType', 'agency'])
            ->where('agency_id', $agencyId)
            ->latest('reported_at')
            ->paginate($perPage);
    }

    public function paginateAll(int $perPage = 15): LengthAwarePaginator
    {
        return Incident::query()
            ->with(['incidentType', 'agency'])
            ->latest('reported_at')
            ->paginate($perPage);
    }
}

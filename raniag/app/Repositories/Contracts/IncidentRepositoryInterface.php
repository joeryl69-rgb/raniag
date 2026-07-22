<?php

namespace App\Repositories\Contracts;

use App\Models\Incident;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IncidentRepositoryInterface
{
    public function findById(int $id): ?Incident;

    public function findByTrackingNumber(string $trackingNumber): ?Incident;

    public function create(array $attributes): Incident;

    public function update(Incident $incident, array $attributes): Incident;

    public function paginateForAgency(int $agencyId, int $perPage = 15): LengthAwarePaginator;

    public function paginateAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
}

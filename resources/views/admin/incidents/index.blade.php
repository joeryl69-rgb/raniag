<x-app-layout>
    <x-slot name="header">
        {{ __('Incident Reports Management') }}
    </x-slot>

    <div class="row">
        <div class="col-12">
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-header raniag-card-header bg-white py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-filter-left me-2 text-primary"></i>All Incident Reports</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="p-3 border-bottom">
                        <form method="GET" class="row g-2">
                            <div class="col-md-4">
                                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search tracking #, title, description, reporter">
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    @foreach(\App\Enums\IncidentStatus::cases() as $s)
                                        <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="incident_type_id" class="form-select">
                                    <option value="">All Categories</option>
                                    @foreach($incidentTypes ?? [] as $it)
                                        <option value="{{ $it->id }}" {{ request('incident_type_id') == $it->id ? 'selected' : '' }}>{{ $it->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-grid">
                                <button class="btn btn-primary">Search</button>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">Tracking #</th>
                                    <th class="py-3">Type</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3">Priority</th>
                                    <th class="py-3">Barangay</th>
                                    <th class="py-3">Reported At</th>
                                    <th class="px-4 py-3 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($incidents as $inc)
                                    <tr class="{{ $inc->status->value === 'submitted' ? 'table-warning bg-opacity-25' : '' }}">
                                        <td class="px-4 py-3 fw-bold text-primary">
                                            {{ $inc->tracking_number }}
                                            @if($inc->status->value === 'submitted')
                                                <span class="badge bg-danger ms-1" style="font-size: 0.65rem;">NEW</span>
                                            @endif
                                        </td>
                                        <td class="py-3">
                                            <span class="badge rounded-pill text-white px-2 py-1 shadow-sm" style="background-color: {{ $inc->incidentType->color ?? '#6c757d' }}">
                                                {{ $inc->incidentType->name }}
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            <x-public.status-badge :status="$inc->status" />
                                        </td>
                                        <td class="py-3">
                                            @php
                                                $priorityData = match($inc->priority->value ?? $inc->priority) {
                                                    'low' => ['class' => 'bg-info text-dark', 'icon' => 'bi-info-circle'],
                                                    'medium' => ['class' => 'bg-warning text-dark', 'icon' => 'bi-exclamation-circle'],
                                                    'high' => ['class' => 'bg-danger text-white', 'icon' => 'bi-exclamation-triangle-fill'],
                                                    'critical' => ['class' => 'bg-dark text-white', 'icon' => 'bi-exclamation-octagon-fill'],
                                                    default => ['class' => 'bg-secondary text-white', 'icon' => 'bi-record-circle']
                                                };
                                            @endphp
                                            <span class="badge {{ $priorityData['class'] }} text-capitalize px-2 py-1 shadow-sm">
                                                <i class="bi {{ $priorityData['icon'] }} me-1"></i>
                                                {{ $inc->priority->label() ?? $inc->priority }}
                                            </span>
                                        </td>
                                        <td class="py-3">{{ $inc->barangay ?? 'N/A' }}</td>
                                        <td class="py-3 text-muted">
                                            <i class="bi bi-clock me-1"></i>
                                            {{ $inc->reported_at->format('M d, Y h:i A') }}
                                            @if($inc->reported_at->isToday())
                                                <span class="text-success ms-1" style="font-size: 0.75rem;">(Today)</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            <a href="{{ route('admin.incidents.show', $inc->id) }}" class="btn btn-sm btn-primary shadow-sm">
                                                <i class="bi bi-eye me-1"></i>View Details
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-folder2-open display-4"></i>
                                            <p class="mt-2 mb-0">No incident reports found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($incidents->hasPages())
                    <div class="card-footer bg-white border-0 py-3">
                        {!! $incidents->links('pagination::bootstrap-5') !!}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

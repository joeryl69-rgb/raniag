<x-app-layout>
    <x-slot name="header">
        {{ __('Assigned Incidents Dispatch') }}
    </x-slot>

    <div class="row">
        <div class="col-12">
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-header raniag-card-header py-3 border-0">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-card-checklist me-2 text-primary"></i>Assigned Emergency Responses</h5>
                            <p class="small text-muted mb-0 mt-1">Dispatches currently assigned to your agency</p>
                        </div>
                    </div>
                    <form method="GET" action="{{ route('agency.incidents.index') }}" class="d-flex flex-wrap gap-2 align-items-center">
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" style="width: 180px;" placeholder="Search tracking # or title">
                        <select name="status" class="form-select form-select-sm" style="width: 160px;">
                            <option value="all">All Statuses</option>
                            @foreach (\App\Enums\IncidentStatus::cases() as $s)
                                @continue(in_array($s->value, ['resolved', 'closed']))
                                <option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>
                            @endforeach
                        </select>
                        <select name="priority" class="form-select form-select-sm" style="width: 140px;">
                            <option value="all">All Priorities</option>
                            @foreach (\App\Enums\IncidentPriority::cases() as $p)
                                <option value="{{ $p->value }}" @selected(($filters['priority'] ?? '') === $p->value)>{{ $p->label() }}</option>
                            @endforeach
                        </select>
                        <select name="barangay" class="form-select form-select-sm" style="width: 160px;">
                            <option value="all">All Barangays</option>
                            @foreach ($barangays as $b)
                                <option value="{{ $b }}" @selected(($filters['barangay'] ?? '') === $b)>{{ $b }}</option>
                            @endforeach
                        </select>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" max="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm" style="width: 150px;" title="From date">
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" max="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm" style="width: 150px;" title="To date">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                        @if (array_filter($filters ?? []))
                            <a href="{{ route('agency.incidents.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                        @endif
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light text-muted">
                                @php
                                    $sortLink = function (string $col, string $label) use ($filters) {
                                        $dir = (($filters['sort'] ?? '') === $col && ($filters['direction'] ?? 'desc') === 'asc') ? 'desc' : 'asc';
                                        $icon = ($filters['sort'] ?? '') === $col ? ($dir === 'asc' ? 'bi-arrow-down' : 'bi-arrow-up') : 'bi-arrow-down-up text-muted';
                                        $qs = array_merge($filters ?? [], ['sort' => $col, 'direction' => $dir]);
                                        return '<a href="'.route('agency.incidents.index', $qs).'" class="text-dark text-decoration-none">'.$label.' <i class="bi '.$icon.'" style="font-size:0.7rem;"></i></a>';
                                    };
                                @endphp
                                <tr>
                                    <th class="px-4 py-3">Tracking #</th>
                                    <th class="py-3">Type</th>
                                    <th class="py-3">{!! $sortLink('status', 'Status') !!}</th>
                                    <th class="py-3">{!! $sortLink('priority', 'Priority') !!}</th>
                                    <th class="py-3">{!! $sortLink('barangay', 'Barangay') !!}</th>
                                    <th class="py-3">{!! $sortLink('reported_at', 'Assigned At') !!}</th>
                                    <th class="px-4 py-3 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($incidents as $inc)
                                    @php
                                        $assignment = $inc->currentAssignments()->where('agency_id', auth()->user()->agency_id)->where('is_active', true)->first();
                                    @endphp
                                    <tr class="{{ $inc->status->value === 'assigned' ? 'table-light' : '' }}">
                                        <td class="px-4 py-3 fw-bold text-primary">
                                            {{ $inc->tracking_number }}
                                            @if($inc->status->value === 'assigned')
                                                <span class="badge bg-primary-subtle text-primary ms-1" style="font-size: 0.65rem;">NEW</span>
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
                                            {{ $assignment ? $assignment->created_at->format('M d, Y h:i A') : 'N/A' }}
                                            @if($assignment && $assignment->created_at->isToday())
                                                <span class="text-success ms-1" style="font-size: 0.75rem;">(Today)</span>
                                            @endif
                                            @if($assignment && in_array($inc->status->value, ['assigned', 'in_progress', 'pending_info']) && $assignment->created_at->diffInHours(now()) >= 4)
                                                <span class="badge bg-danger-subtle text-danger ms-1" style="font-size: 0.65rem;" title="No update in {{ $assignment->created_at->diffForHumans(null, true) }}">
                                                    <i class="bi bi-alarm"></i> Stale
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            <a href="{{ route('agency.incidents.show', $inc->id) }}" class="btn btn-sm btn-outline-primary shadow-sm">
                                                <i class="bi bi-folder2-open me-1"></i>Process Case
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-shield-slash display-4"></i>
                                            <p class="mt-2 mb-0">No active incidents assigned to your branch.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($incidents->hasPages())
                    <div class="card-footer bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-3">
                            <small class="text-muted">Showing {{ $incidents->firstItem() }} to {{ $incidents->lastItem() }} of {{ $incidents->total() }} results</small>
                            <div>
                                {!! $incidents->links('pagination::bootstrap-5') !!}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        {{ __('Assigned Incidents Dispatch') }}
    </x-slot>

    <div class="row">
        <div class="col-12">
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-header raniag-card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-card-checklist me-2 text-primary"></i>Assigned Emergency Responses</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">Tracking #</th>
                                    <th class="py-3">Type</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3">Priority</th>
                                    <th class="py-3">Barangay</th>
                                    <th class="py-3">Assigned At</th>
                                    <th class="px-4 py-3 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($incidents as $inc)
                                    @php
                                        // Get assignment timestamps
                                        $assignment = $inc->currentAssignments()->where('agency_id', auth()->user()->agency_id)->where('is_active', true)->first();
                                    @endphp                                    <tr class="{{ $inc->status->value === 'assigned' ? 'table-warning bg-opacity-25' : '' }}">
                                        <td class="px-4 py-3 fw-bold text-primary">
                                            {{ $inc->tracking_number }}
                                            @if($inc->status->value === 'assigned')
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
                                            {{ $assignment ? $assignment->created_at->format('M d, Y h:i A') : 'N/A' }}
                                            @if($assignment && $assignment->created_at->isToday())
                                                <span class="text-success ms-1" style="font-size: 0.75rem;">(Today)</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            <a href="{{ route('agency.incidents.show', $inc->id) }}" class="btn btn-sm btn-primary shadow-sm">
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
                        {!! $incidents->links('pagination::bootstrap-5') !!}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

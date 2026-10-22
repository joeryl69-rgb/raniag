@props([
    'incident',
    'detailUrl',
    'actionLabel' => 'Review case',
    'assignedAt' => null,
    'showJurisdiction' => false,
])

@php
    $priority = $incident->priority?->value ?? 'medium';
    $priorityLabel = $incident->priority?->label() ?? 'Medium';
    $queueTitle = filled($incident->title)
        ? $incident->title
        : (($incident->incidentType?->name ?? 'Incident').' report');
    $isNew = in_array($incident->status?->value, ['submitted', 'assigned'], true);
    $isStale = $assignedAt
        && in_array($incident->status?->value, ['assigned', 'in_progress', 'pending_info'], true)
        && $assignedAt->diffInHours(now()) >= 4;
@endphp

<li class="rg-docket-item rg-docket-{{ $priority }} {{ $isNew ? 'is-new' : '' }}">
    <div class="rg-docket-urgency">
        <span class="rg-docket-priority-mark" aria-hidden="true"><i class="bi {{ $priority === 'critical' ? 'bi-exclamation-triangle-fill' : ($priority === 'high' ? 'bi-exclamation-circle-fill' : 'bi-dot') }}"></i></span>
        <span class="rg-docket-priority">{{ $priorityLabel }}</span>
    </div>

    <div class="rg-docket-main">
        <div class="rg-docket-overline">
            <span class="rg-docket-category" style="--case-type-color: {{ $incident->incidentType?->color ?? '#64748b' }}">
                <span aria-hidden="true"></span>{{ $incident->incidentType?->name ?? 'Incident' }}
            </span>
            <span class="rg-docket-ref">{{ $incident->tracking_number }}</span>
            @if ($isNew)<span class="rg-queue-new">NEW</span>@endif
            @if ($showJurisdiction && ($incident->meta['within_jurisdiction'] ?? null) === false)
                <span class="rg-docket-outside"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> Outside jurisdiction</span>
            @endif
        </div>
        <h3 class="rg-docket-title"><a href="{{ $detailUrl }}">{{ $queueTitle }}</a></h3>
        @if ($incident->description)
            <p class="rg-docket-summary">{{ \Illuminate\Support\Str::limit(strip_tags($incident->description), 150) }}</p>
        @endif
        <div class="rg-docket-facts">
            <span><i class="bi bi-geo-alt" aria-hidden="true"></i>@if ($incident->barangay)<x-place-label :barangay="$incident->barangay" />@else Location not provided @endif</span>
            <span>
                <i class="bi bi-clock" aria-hidden="true"></i>
                <time datetime="{{ $incident->reported_at?->toIso8601String() }}">Reported {{ $incident->reported_at?->diffForHumans() ?? 'time unknown' }}</time>
            </span>
            @if ($assignedAt)
                <span><i class="bi bi-send-check" aria-hidden="true"></i><time datetime="{{ $assignedAt->toIso8601String() }}">Assigned {{ $assignedAt->diffForHumans() }}</time></span>
            @endif
            @if ($isStale)
                <span class="rg-docket-stale"><i class="bi bi-alarm" aria-hidden="true"></i>No update for {{ $assignedAt->diffForHumans(null, true) }}</span>
            @endif
        </div>
    </div>

    <div class="rg-docket-state">
        <span class="rg-docket-state-caption">Response</span>
        <x-public.status-badge :status="$incident->status" />
    </div>
    <a class="rg-docket-action" href="{{ $detailUrl }}" aria-label="{{ $actionLabel }} {{ $incident->tracking_number }}">
        <span>{{ $actionLabel }}</span><i class="bi bi-arrow-up-right" aria-hidden="true"></i>
    </a>
</li>

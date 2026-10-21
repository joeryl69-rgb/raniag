@props([
    'incident',
    'backUrl',
    'backLabel' => 'Back to incidents',
    'priorityLabel' => 'Reported priority',
])

<section class="rg-case-hero" id="rg-case-hero" aria-labelledby="rg-case-title" data-live-refresh data-live-refresh-target="#rg-case-hero" data-live-refresh-interval="4000">
    <div class="rg-case-commandline">
        <a href="{{ $backUrl }}" class="rg-case-back">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ $backLabel }}</span>
        </a>
        <span class="rg-case-command-label"><span aria-hidden="true"></span> INCIDENT COMMAND / CASE FILE</span>
    </div>

    <div class="rg-case-hero-main">
        <div class="rg-case-identity">
            <span class="rg-case-tracking"><i class="bi bi-upc-scan me-1" aria-hidden="true"></i>{{ $incident->tracking_number }}</span>
            <h1 id="rg-case-title">{{ $incident->title ?: ($incident->incidentType?->name ?? 'Incident report') }}</h1>
            <p class="rg-case-hero-location">
                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                {{ $incident->barangay ? 'Barangay '.$incident->barangay : ($incident->location_address ?: 'Location not provided') }}
                @if ($incident->location_address && $incident->barangay)
                    <span>· {{ $incident->location_address }}</span>
                @endif
            </p>
        </div>
        <div class="rg-case-signal-stack" aria-label="Case status and priority">
            <span class="rg-case-type-mark" style="--case-type-color: {{ $incident->incidentType?->color ?? '#64748b' }}">
                <i class="bi bi-broadcast-pin" aria-hidden="true"></i>
                {{ $incident->incidentType?->name ?? 'Incident' }}
            </span>
            <div class="rg-case-signals">
                <span class="rg-case-signal-label">Current state</span>
                <x-public.status-badge :status="$incident->status" />
                <span class="rg-case-signal-label mt-2">{{ $priorityLabel }}</span>
                <x-priority-badge :priority="$incident->priority" />
            </div>
        </div>
    </div>

    <div class="rg-case-meta">
        <span><i class="bi bi-clock-history" aria-hidden="true"></i><span>Reported</span><time datetime="{{ $incident->reported_at?->toIso8601String() }}">{{ $incident->reported_at?->format('M d, Y · h:i A') ?? '—' }}</time></span>
        <span><i class="bi bi-journal-text" aria-hidden="true"></i><span>Case reference</span><strong>{{ $incident->tracking_number }}</strong></span>
    </div>

    <nav class="rg-case-index" aria-label="Case dossier sections">
        <span>CASE DOSSIER</span>
        <a href="#case-situation"><i class="bi bi-journal-text" aria-hidden="true"></i> Report</a>
        <a href="#case-location"><i class="bi bi-geo-alt" aria-hidden="true"></i> Scene</a>
        <a href="#case-evidence"><i class="bi bi-images" aria-hidden="true"></i> Evidence</a>
        <a href="#case-history"><i class="bi bi-clock-history" aria-hidden="true"></i> Activity</a>
        <a href="#case-actions"><i class="bi bi-lightning-charge" aria-hidden="true"></i> Next action</a>
    </nav>
</section>

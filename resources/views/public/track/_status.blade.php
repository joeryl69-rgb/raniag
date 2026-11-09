@php
    $statusValue = $incident->status->value;
    $trackingStatusValue = $statusValue === 'pending_info' ? 'in_progress' : $statusValue;

    $plainLanguage = [
        'submitted' => 'Your report has been submitted and is waiting to be reviewed by our team.',
        'received' => 'Good news — your report has been received and confirmed.',
        'assigned' => 'Your report has been assigned to a responding agency.',
        'in_progress' => 'A responding agency is currently working on your report.',
        'resolved' => 'Your report has been resolved. Thank you for helping keep our community safe.',
        'closed' => 'This report has been resolved and officially closed.',
        'rejected' => 'This report could not be accepted. Please see the notes below for details.',
        'outside_aor' => 'This location falls outside Pamplona\'s area of responsibility. It has been referred to the appropriate agency or municipality; MDRRMO Pamplona will not be processing it further.',
    ];

    $steps = [
        'submitted' => ['label' => 'Submitted', 'icon' => 'bi-send'],
        'received' => ['label' => 'Received', 'icon' => 'bi-inbox'],
        'assigned' => ['label' => 'Assigned', 'icon' => 'bi-person-check'],
        'in_progress' => ['label' => 'In Progress', 'icon' => 'bi-arrow-repeat'],
        'resolved' => ['label' => 'Resolved', 'icon' => 'bi-check-circle'],
    ];
    $stepOrder = array_keys($steps);

    $effectiveStatus = match ($trackingStatusValue) {
        'closed' => 'resolved',
        default => $trackingStatusValue,
    };
    $currentIndex = array_search($effectiveStatus, $stepOrder, true);
    $isRejected = $statusValue === 'rejected';
    $isOutsideAor = $statusValue === 'outside_aor';
@endphp

<div id="rg-track-status">
    <section class="rg-track-detail-banner" aria-live="polite" data-rg-reveal>
        <div class="rg-track-detail-banner-copy">
            <span class="rg-track-detail-kicker">CASE MOVEMENT / {{ strtoupper(str_replace('_', ' ', $trackingStatusValue)) }}</span>
            <strong>{{ $plainLanguage[$trackingStatusValue] ?? 'Your report status is available below.' }}</strong>
            <p>Updates are private to this tracking reference. The map and details on this page are not shown on the public community dashboard.</p>
        </div>
        <img src="/images/guide/{{ $isRejected || $isOutsideAor ? 'jo-alert' : ($effectiveStatus === 'resolved' ? 'jo-resolved' : 'jo-map') }}.png?v=1" alt="" width="132" height="176">
    </section>
    <div class="card raniag-card rg-app-card mb-4">
        <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
            <span class="raniag-step-badge"><i class="bi bi-signpost-2"></i></span>
            <span>Progress</span>
        </div>
        <div class="card-body p-4">
            @if($isRejected)
                <div class="d-flex align-items-center gap-3">
                    <span class="rg-icon-tile is-alert" style="width: 54px; height: 54px; font-size: 1.4rem;">
                        <i class="bi bi-x-lg"></i>
                    </span>
                    <div>
                        <div class="fw-bold fs-5">Report Not Accepted</div>
                        <div class="text-muted">{{ $plainLanguage['rejected'] }}</div>
                    </div>
                </div>
            @elseif($isOutsideAor)
                <div class="d-flex align-items-center gap-3">
                    <span class="rg-icon-tile" style="width: 54px; height: 54px; font-size: 1.4rem;">
                        <i class="bi bi-signpost-split-fill"></i>
                    </span>
                    <div>
                        <div class="fw-bold fs-5">Referred to Another Agency/Municipality</div>
                        <div class="text-muted">{{ $plainLanguage['outside_aor'] }}</div>
                    </div>
                </div>
            @else
                <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                    <x-public.status-badge :status="$trackingStatusValue" class="fs-6 px-3 py-2" />
                    <div class="text-muted">{{ $plainLanguage[$trackingStatusValue] ?? '' }}</div>
                </div>

                <div class="raniag-progress-tracker mx-auto" style="max-width: 100%;">
                    <div class="progress-container">
                        <div class="progress-track"></div>
                        <div class="progress-fill" data-current-status="{{ $effectiveStatus }}" style="width: {{ $currentIndex >= 0 ? (($currentIndex + 1) / count($stepOrder)) * 100 : 0 }}%;"></div>
                        <div class="progress-steps">
                            @foreach($stepOrder as $i => $key)
                                <div class="progress-step {{ $i <= $currentIndex ? 'completed' : '' }}" data-status="{{ $key }}" data-rg-pop>
                                    <div class="step-dot">
                                        <i class="bi {{ $steps[$key]['icon'] }}"></i>
                                    </div>
                                    <div class="step-name">{{ $steps[$key]['label'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

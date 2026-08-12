@extends('layouts.public')

@section('title', 'Report Status')

@section('content')
<div class="container">
    <div class="mb-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Incident Status</h1>
            <p class="text-muted mb-0">Tracking number: <span class="raniag-tracking-number">{{ $incident->tracking_number }}</span></p>
        </div>
        <a href="{{ route('public.track') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Track Another
        </a>
    </div>

    @php
        $statusValue = $incident->status->value;

        $plainLanguage = [
            'submitted' => 'Your report has been submitted and is waiting to be reviewed by our team.',
            'received' => 'Good news — your report has been received and confirmed.',
            'assigned' => 'Your report has been assigned to a responding agency.',
            'in_progress' => 'A responding agency is currently working on your report.',
            'pending_info' => 'We need a bit more information from you before we can continue. Please wait for our team to reach out.',
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

        // pending_info and closed visually sit on top of in_progress / resolved respectively
        $effectiveStatus = match ($statusValue) {
            'pending_info' => 'in_progress',
            'closed' => 'resolved',
            default => $statusValue,
        };
        $currentIndex = array_search($effectiveStatus, $stepOrder, true);
        $isRejected = $statusValue === 'rejected';
        $isOutsideAor = $statusValue === 'outside_aor';
    @endphp

    <div class="card raniag-card mb-4">
        <div class="card-body">
            @if($isRejected)
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-x-circle-fill text-danger fs-1"></i>
                    <div>
                        <div class="fw-bold fs-5">Report Not Accepted</div>
                        <div class="text-muted">{{ $plainLanguage['rejected'] }}</div>
                    </div>
                </div>
            @elseif($isOutsideAor)
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-signpost-split-fill text-info fs-1"></i>
                    <div>
                        <div class="fw-bold fs-5">Referred to Another Agency/Municipality</div>
                        <div class="text-muted">{{ $plainLanguage['outside_aor'] }}</div>
                    </div>
                </div>
            @else
                <div class="d-flex align-items-center gap-3 mb-4">
                    <x-public.status-badge :status="$incident->status" class="fs-6 px-3 py-2" />
                    <div class="text-muted">{{ $plainLanguage[$statusValue] ?? '' }}</div>
                </div>

                <style>
                    .raniag-stepper .step-circle { width: 36px; height: 36px; }
                    .raniag-stepper .step-label { font-size: 0.8rem; white-space: nowrap; }
                    @media (max-width: 480px) {
                        .raniag-stepper .step-circle { width: 26px; height: 26px; font-size: 0.75rem; }
                        .raniag-stepper .step-label { font-size: 0.62rem; }
                    }
                </style>
                <div class="d-flex justify-content-between position-relative raniag-stepper" style="max-width: 640px; overflow-x: auto;">
                    <div class="position-absolute top-50 start-0 end-0 translate-middle-y" style="height: 3px; background: #e2e8f0; z-index: 0;"></div>
                    <div class="position-absolute top-50 start-0 translate-middle-y bg-success" style="height: 3px; width: {{ $currentIndex >= 0 ? ($currentIndex / (count($stepOrder) - 1)) * 100 : 0 }}%; z-index: 1; transition: width .3s;"></div>
                    @foreach($stepOrder as $i => $key)
                        <div class="text-center position-relative" style="z-index: 2; width: {{ 100 / count($stepOrder) }}%;">
                            <div class="step-circle rounded-circle d-inline-flex align-items-center justify-content-center {{ $i <= $currentIndex ? 'bg-success text-white' : 'bg-light text-muted border' }}">
                                <i class="bi {{ $steps[$key]['icon'] }}"></i>
                            </div>
                            <div class="step-label mt-1 {{ $i <= $currentIndex ? 'fw-semibold' : 'text-muted' }}">{{ $steps[$key]['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card raniag-card h-100">
                <div class="card-header raniag-card-header">Incident Details</div>
                <div class="card-body">
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-tag text-primary"></i>
                        <div><div class="text-muted small">Type</div><div class="fw-semibold">{{ $incident->incidentType->name }}</div></div>
                    </div>
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-flag text-primary"></i>
                        <div><div class="text-muted small">Priority</div><div class="fw-semibold text-capitalize">{{ $incident->priority->label() }}</div></div>
                    </div>
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-clock-history text-primary"></i>
                        <div><div class="text-muted small">Reported</div><div class="fw-semibold">{{ $incident->reported_at->format('M d, Y h:i A') }}</div></div>
                    </div>
                    @if ($incident->barangay || $incident->location_address)
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-geo-alt text-primary"></i>
                            <div>
                                <div class="text-muted small">Location</div>
                                @if ($incident->barangay)<div class="fw-semibold">{{ $incident->barangay }}</div>@endif
                                @if ($incident->location_address)<div class="text-muted small">{{ $incident->location_address }}</div>@endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card raniag-card mb-4">
                <div class="card-header raniag-card-header">Description</div>
                <div class="card-body">
                    @if ($incident->title)
                        <h2 class="h6 fw-bold">{{ $incident->title }}</h2>
                    @endif
                    <p class="mb-0">{{ $incident->description }}</p>
                </div>
            </div>

            <div class="card raniag-card">
                <div class="card-header raniag-card-header">Full History</div>
                <div class="card-body">
                    @if ($incident->statusTimeline->isEmpty())
                        <p class="text-muted mb-0">No public updates yet. Please check back later.</p>
                    @else
                        <div class="raniag-timeline">
                            @foreach ($incident->statusTimeline as $update)
                                <div class="raniag-timeline-item">
                                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-1">
                                        <x-public.status-badge :status="$update->to_status" />
                                        <small class="text-muted">{{ $update->created_at->format('M d, Y h:i A') }}</small>
                                    </div>
                                    @if ($update->comment)
                                        <p class="mb-0 text-muted">{{ $update->comment }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

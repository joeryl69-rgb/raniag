@extends('layouts.public')

@section('title', 'Report Status')

@section('content')
<div class="container">

    <div class="rg-page-head d-flex flex-wrap justify-content-between align-items-start gap-3" data-rg-reveal>
        <div>
            <span class="rg-eyebrow"><i class="bi bi-activity"></i>Status</span>
            <h1 class="rg-page-title">Incident Status</h1>
            <p class="rg-page-sub mb-0">
                Tracking number: <span class="raniag-tracking-number" style="font-size: 1rem;">{{ $incident->tracking_number }}</span>
            </p>
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

    <div class="card raniag-card mb-4" data-rg-reveal>
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
                    <x-public.status-badge :status="$incident->status" class="fs-6 px-3 py-2" />
                    <div class="text-muted">{{ $plainLanguage[$statusValue] ?? '' }}</div>
                </div>

                <div class="raniag-progress-tracker mx-auto" style="max-width: 100%;">
                    <div class="progress-container">
                        <div class="progress-track"></div>
                        <div class="progress-fill" data-current-status="{{ $effectiveStatus }}" style="width: {{ $currentIndex >= 0 ? (($currentIndex + 1) / count($stepOrder)) * 100 : 0 }}%;"></div>
                        
                        <div class="progress-steps">
                            @foreach($stepOrder as $i => $key)
                                <div class="progress-step {{ $i <= $currentIndex ? 'completed' : '' }}" data-status="{{ $key }}">
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

    <div class="row g-4" data-rg-reveal>
        <div class="col-lg-4">
            <div class="card raniag-card mb-4">
                <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                    <span class="raniag-step-badge"><i class="bi bi-info-lg"></i></span>
                    <span>Incident Details</span>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-tag" style="color: var(--rg-brand);"></i>
                        <div><div class="text-muted small">Type</div><div class="fw-semibold">{{ $incident->incidentType->name }}</div></div>
                    </div>
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-flag" style="color: var(--rg-brand);"></i>
                        <div><div class="text-muted small">Priority</div><div class="fw-semibold text-capitalize">{{ $incident->priority->label() }}</div></div>
                    </div>
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-clock-history" style="color: var(--rg-brand);"></i>
                        <div><div class="text-muted small">Reported</div><div class="fw-semibold">{{ $incident->reported_at->format('M d, Y h:i A') }}</div></div>
                    </div>
                    @if ($incident->barangay || $incident->location_address)
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-geo-alt" style="color: var(--rg-brand);"></i>
                            <div>
                                <div class="text-muted small">Location</div>
                                @if ($incident->barangay)<div class="fw-semibold">{{ $incident->barangay }}</div>@endif
                                @if ($incident->location_address)<div class="text-muted small">{{ $incident->location_address }}</div>@endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($incident->assignments->isNotEmpty())
                <div class="card raniag-card">
                    <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                        <span class="raniag-step-badge"><i class="bi bi-people"></i></span>
                        <span>Responding Agencies</span>
                    </div>
                    <div class="card-body p-4">
                        @foreach ($incident->assignments as $assignment)
                            <div class="rg-agency-row {{ !$loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
                                <div class="fw-semibold small">
                                    {{ $assignment->agency?->name ?? $assignment->assignee?->display_title ?? 'Personnel' }}
                                </div>
                                @if ($assignment->isAcknowledged())
                                    <span class="badge rg-badge-accepted"><i class="bi bi-check2-circle me-1"></i>Accepted</span>
                                @else
                                    <span class="badge rg-badge-pending"><i class="bi bi-hourglass-split me-1"></i>Awaiting acceptance</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card raniag-card mb-4">
                <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                    <span class="raniag-step-badge"><i class="bi bi-card-text"></i></span>
                    <span>Description</span>
                </div>
                <div class="card-body p-4">
                    @if ($incident->title)
                        <h2 class="h6 fw-bold">{{ $incident->title }}</h2>
                    @endif
                    <p class="mb-0">{{ $incident->description }}</p>
                </div>
            </div>

            @php
                // Reporter view: only the reporter's own submission evidence.
                // uploaded_by is null for anonymous/reporter uploads and set to a
                // user id for agency/personnel evidence added later — exclude those.
                $reporterEvidence = $incident->evidence->whereNull('uploaded_by');
            @endphp
            @if ($reporterEvidence->isNotEmpty())
                <div class="card raniag-card">
                    <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                        <span class="raniag-step-badge"><i class="bi bi-image"></i></span>
                        <span>Evidence ({{ $reporterEvidence->count() }})</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-2">
                            @foreach ($reporterEvidence as $ev)
                                <div class="col-6 col-md-4">
                                    <img src="{{ asset('storage/' . $ev->file_path) }}" class="img-fluid rounded shadow-sm rg-evidence-thumb" style="cursor: zoom-in;" alt="{{ $ev->original_filename }}" loading="lazy">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="rg-lightbox" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content bg-dark">
                            <div class="modal-header border-0">
                                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body pt-0 text-center">
                                <img id="rg-lightbox-img" class="img-fluid rounded" alt="Full-size evidence">
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    // Re-parent onto <body> so this modal escapes the
                    // .rg-shell stacking context — otherwise Bootstrap's
                    // backdrop (which it always appends to <body>) ends up
                    // rendered above the modal itself, blocking every click.
                    (function () {
                        const el = document.getElementById('rg-lightbox');
                        if (!el) return;
                        let home = null;
                        el.addEventListener('hidden.bs.modal', () => {
                            if (home) { home.parent.insertBefore(el, home.next); home = null; }
                        });
                        document.querySelectorAll('.rg-evidence-thumb').forEach((img) => {
                            img.addEventListener('click', () => {
                                document.getElementById('rg-lightbox-img').src = img.src;
                                if (el.parentElement !== document.body) {
                                    home = { parent: el.parentElement, next: el.nextSibling };
                                    document.body.appendChild(el);
                                }
                                bootstrap.Modal.getOrCreateInstance(el).show();
                            });
                        });
                    })();
                </script>
            @endif
        </div>
    </div>

    <div class="card raniag-card mt-4" data-rg-reveal>
        <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
            <span class="raniag-step-badge"><i class="bi bi-list-ul"></i></span>
            <span>Full History</span>
        </div>
        <div class="card-body p-4">
            @if ($incident->statusTimeline->isEmpty())
                <p class="text-muted mb-0">No public updates yet. Please check back later.</p>
            @else
                <div class="raniag-timeline raniag-timeline-wide">
                    @foreach ($incident->statusTimeline as $update)
                        @php $tKey = $update->to_status->value ?? $update->to_status; @endphp
                        <div class="raniag-timeline-item" data-status="{{ $tKey }}">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
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
@endsection

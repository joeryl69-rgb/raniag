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

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card raniag-card h-100">
                <div class="card-header raniag-card-header">Summary</div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="text-muted small">Status</dt>
                        <dd class="mb-3">
                            <x-public.status-badge :status="$incident->status" />
                        </dd>

                        <dt class="text-muted small">Type</dt>
                        <dd class="mb-3">{{ $incident->incidentType->name }}</dd>

                        <dt class="text-muted small">Priority</dt>
                        <dd class="mb-3 text-capitalize">{{ $incident->priority->label() }}</dd>

                        <dt class="text-muted small">Reported</dt>
                        <dd class="mb-3">{{ $incident->reported_at->format('M d, Y h:i A') }}</dd>

                        @if ($incident->barangay || $incident->location_address)
                            <dt class="text-muted small">Location</dt>
                            <dd class="mb-0">
                                @if ($incident->barangay)
                                    <div>{{ $incident->barangay }}</div>
                                @endif
                                @if ($incident->location_address)
                                    <div class="text-muted small">{{ $incident->location_address }}</div>
                                @endif
                            </dd>
                        @endif
                    </dl>
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
                <div class="card-header raniag-card-header">Status Timeline</div>
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


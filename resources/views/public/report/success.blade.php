@extends('layouts.public')

@section('title', 'Report Submitted')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card raniag-card text-center p-4 p-lg-5" data-rg-reveal>
                <div class="text-success display-4 mb-3" data-rg-pop><i class="bi bi-check-circle-fill"></i></div>
                <h1 class="h3 fw-bold mb-2">Report Submitted Successfully</h1>
                <p class="text-muted mb-4">
                    Your incident has been received by {{ config('raniag.organization') }}.
                    Save both your tracking number and access code to check status updates.
                </p>

                <div class="bg-light rounded-3 p-4 mb-3">
                    <p class="text-muted small mb-2 text-uppercase fw-semibold">Your Tracking Number</p>
                    <p class="raniag-tracking-number mb-0" id="tracking-number">{{ $trackingNumber }}</p>
                </div>

                @if($trackingPin)
                <div class="bg-light rounded-3 p-4 mb-4 border border-warning">
                    <p class="text-muted small mb-2 text-uppercase fw-semibold">Access Code</p>
                    <p class="raniag-tracking-number mb-2" id="access-code" style="letter-spacing: .2em;">{{ $trackingPin }}</p>
                    <p class="text-muted small mb-0">Shown once — you will need this (or the last 4 digits of your phone) to track this report.</p>
                </div>
                @else
                <div class="alert alert-warning text-start mb-4">
                    Your access code was shown only on the first visit after submission. Use the last 4 digits of the phone you provided, or contact {{ config('raniag.organization') }}.
                </div>
                @endif

                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-primary" id="copy-tracking"
                            data-tracking="{{ $trackingNumber }}">
                        <i class="bi bi-clipboard me-2"></i>Copy Number
                    </button>
                    @if($trackingPin)
                    <button type="button" class="btn btn-outline-secondary" id="copy-pin"
                            data-pin="{{ $trackingPin }}">
                        <i class="bi bi-key me-2"></i>Copy Access Code
                    </button>
                    @endif
                    <a href="{{ route('public.track', ['tracking_number' => $trackingNumber]) }}"
                       class="btn btn-primary">
                        <i class="bi bi-search me-2"></i>Track This Report
                    </a>
                </div>

                <hr class="my-4">

                <div class="text-start mx-auto mb-4" style="max-width: 480px;" data-rg-stagger>
                    <p class="text-muted small text-uppercase fw-semibold mb-3 text-center">What happens next</p>
                    <div class="raniag-timeline">
                        <div class="raniag-timeline-item">
                            <div class="fw-semibold small">Reviewed by {{ config('raniag.organization') }}</div>
                            <div class="text-muted small">Your report enters the queue for staff review.</div>
                        </div>
                        <div class="raniag-timeline-item">
                            <div class="fw-semibold small">Assigned to a responder</div>
                            <div class="text-muted small">It's routed to the agency or personnel best suited to handle it.</div>
                        </div>
                        <div class="raniag-timeline-item">
                            <div class="fw-semibold small">Status updates as it progresses</div>
                            <div class="text-muted small">Check back anytime with your tracking number and access code.</div>
                        </div>
                    </div>
                </div>

                <p class="text-muted small mb-3">
                    You can return anytime to track progress. If you provided contact details, the LGU may reach out for clarification.
                </p>
                <a href="{{ route('public.report.create') }}" class="btn btn-link">Submit another report</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('copy-tracking')?.addEventListener('click', function () {
        const value = this.dataset.tracking || '';
        navigator.clipboard.writeText(value).then(() => {
            this.innerHTML = '<i class="bi bi-check2 me-2"></i>Copied!';
            setTimeout(() => {
                this.innerHTML = '<i class="bi bi-clipboard me-2"></i>Copy Number';
            }, 2000);
        });
    });
    document.getElementById('copy-pin')?.addEventListener('click', function () {
        const value = this.dataset.pin || '';
        navigator.clipboard.writeText(value).then(() => {
            this.innerHTML = '<i class="bi bi-check2 me-2"></i>Copied!';
            setTimeout(() => {
                this.innerHTML = '<i class="bi bi-key me-2"></i>Copy Access Code';
            }, 2000);
        });
    });
</script>
@endpush

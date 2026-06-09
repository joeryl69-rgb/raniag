@extends('layouts.public')

@section('title', 'Report Submitted')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card raniag-card text-center p-4 p-lg-5">
                <div class="text-success display-4 mb-3"><i class="bi bi-check-circle-fill"></i></div>
                <h1 class="h3 fw-bold mb-2">Report Submitted Successfully</h1>
                <p class="text-muted mb-4">
                    Your incident has been received by {{ config('raniag.organization') }}.
                    Save your tracking number to check status updates.
                </p>

                <div class="bg-light rounded-3 p-4 mb-4">
                    <p class="text-muted small mb-2 text-uppercase fw-semibold">Your Tracking Number</p>
                    <p class="raniag-tracking-number mb-0" id="tracking-number">{{ $trackingNumber }}</p>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-primary" id="copy-tracking"
                            data-tracking="{{ $trackingNumber }}">
                        <i class="bi bi-clipboard me-2"></i>Copy Number
                    </button>
                    <a href="{{ route('public.track', ['tracking_number' => $trackingNumber]) }}"
                       class="btn btn-primary">
                        <i class="bi bi-search me-2"></i>Track This Report
                    </a>
                </div>

                <hr class="my-4">

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
</script>
@endpush


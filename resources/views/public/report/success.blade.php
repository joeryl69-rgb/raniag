@extends('layouts.public')

@section('title', 'Report Submitted')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card raniag-card text-center p-4 p-lg-5" data-rg-reveal>
                <div class="rg-success-jo mx-auto mb-3" data-rg-pop>
                    <img src="/images/guide/jo-resolved.svg" alt="JO" width="96" height="120">
                    <div class="rg-stepper-jo-name mt-1">JO</div>
                </div>
                <h1 class="h3 fw-bold mb-2">Report Submitted Successfully</h1>
                <p class="text-muted mb-4">
                    Your incident has been received by {{ config('raniag.organization') }}.
                    Save your tracking number to check status updates.
                </p>

                <div class="bg-light rounded-3 p-4 mb-4" id="tracking-card">
                    <p class="text-muted small mb-2 text-uppercase fw-semibold">Your Tracking Number</p>
                    <p class="raniag-tracking-number mb-0" id="tracking-number">{{ $trackingNumber }}</p>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-primary" id="copy-tracking"
                            data-tracking="{{ $trackingNumber }}">
                        <i class="bi bi-clipboard me-2"></i>Copy Number
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="download-tracking"
                            data-tracking="{{ $trackingNumber }}"
                            data-org="{{ config('raniag.organization') }}">
                        <i class="bi bi-download me-2"></i>Download Image
                    </button>
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
                            <div class="text-muted small">Check back anytime with your tracking number.</div>
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

    document.getElementById('download-tracking')?.addEventListener('click', function () {
        const tracking = this.dataset.tracking || '';
        const org = this.dataset.org || 'RANIAG';
        const canvas = document.createElement('canvas');
        canvas.width = 720;
        canvas.height = 420;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        ctx.fillStyle = '#0f2744';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#f4c430';
        ctx.fillRect(0, 0, canvas.width, 8);

        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 28px system-ui, sans-serif';
        ctx.fillText('RANIAG', 48, 72);
        ctx.font = '18px system-ui, sans-serif';
        ctx.fillStyle = '#c8d4e4';
        ctx.fillText(org, 48, 104);

        ctx.fillStyle = '#9eb0c7';
        ctx.font = '14px system-ui, sans-serif';
        ctx.fillText('YOUR TRACKING NUMBER', 48, 190);

        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 42px ui-monospace, Menlo, monospace';
        ctx.fillText(tracking, 48, 250);

        ctx.fillStyle = '#9eb0c7';
        ctx.font = '16px system-ui, sans-serif';
        ctx.fillText('Save this image to track your report status.', 48, 320);
        ctx.fillText('Keep it private — anyone with this number can view updates.', 48, 348);

        const link = document.createElement('a');
        link.download = tracking + '-raniag.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });
</script>
@endpush

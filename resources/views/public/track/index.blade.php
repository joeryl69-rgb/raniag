@extends('layouts.public')

@section('title', 'Track Report')

@section('content')
<div class="container">
    <div class="rg-track">
        <div class="rg-track-intro">
            <img class="jo-mascot rg-track-jo" src="/images/guide/jo-clipboard.jpg?v=4" alt="JO" width="140" height="176">
            <div>
                <span class="rg-eyebrow"><i class="bi bi-search"></i>Status lookup</span>
                <h1 class="rg-page-title">Track Your Report</h1>
                <p class="rg-page-sub">Enter the tracking number from your report. The page shows where it stands, without calling the office.</p>
                <ol class="rg-track-states" aria-label="Status a report can show">
                    <li>Submitted</li>
                    <li>Assigned</li>
                    <li>In progress</li>
                    <li>Resolved</li>
                </ol>
            </div>
        </div>

        <div class="card raniag-card rg-track-card">
            <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                <span class="raniag-step-badge"><i class="bi bi-upc-scan"></i></span>
                <span>Tracking Number</span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('public.track.lookup') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="tracking_number" class="form-label">Tracking Number</label>
                        <input type="text"
                               class="form-control form-control-lg @error('tracking_number') is-invalid @enderror"
                               id="tracking_number"
                               name="tracking_number"
                               value="{{ old('tracking_number', $prefillTrackingNumber ?? '') }}"
                               placeholder="e.g. RAN-XXXXXX"
                               required
                               autocomplete="off"
                               spellcheck="false"
                               style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: .08em;">
                        @error('tracking_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Case-insensitive. Include the dashes exactly as shown.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="bi bi-search me-2"></i>Look Up Report
                    </button>
                </form>
                <div class="d-flex flex-wrap gap-3 mt-3 small text-muted">
                    <span><i class="bi bi-shield-lock me-1"></i>No account needed</span>
                    <span><i class="bi bi-clock-history me-1"></i>Updates in plain language</span>
                </div>
                <p class="text-muted small mt-3 mb-0">
                    Lost your number? Contact {{ config('raniag.organization') }} for assistance, or
                    <a href="{{ route('public.report.create') }}" class="btn btn-link p-0 align-baseline">file a new report</a>.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.rg-track {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(280px, 460px);
    gap: 36px;
    align-items: center;
}
.rg-track-intro { animation: rg-track-in .55s ease both; }
.rg-track-jo { height: 168px; max-width: 150px; margin-bottom: 8px; }
.rg-track-card { animation: rg-track-in .7s ease .12s both; }
.rg-track-states {
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 16px 0 0;
    padding: 0;
}
.rg-track-states li {
    border-radius: 999px;
    padding: .35rem .75rem;
    background: #fff;
    border: 1px solid rgba(11, 18, 32, .08);
    color: #5b6780;
    font-size: .82rem;
    font-weight: 700;
}
.rg-track-states li.is-on {
    color: #fff;
    background: var(--raniag-primary, #0b5ed7);
    border-color: transparent;
    animation: rg-track-pulse 1.6s ease-in-out infinite;
}
@keyframes rg-track-in {
    from { opacity: 0; transform: translateY(14px); }
    to { opacity: 1; transform: none; }
}
@keyframes rg-track-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(11, 94, 215, .28); }
    50% { box-shadow: 0 0 0 7px rgba(11, 94, 215, 0); }
}
@media (max-width: 991.98px) {
    .rg-track { grid-template-columns: 1fr; gap: 18px; }
}
@media (prefers-reduced-motion: reduce) {
    .rg-track-intro, .rg-track-card, .rg-track-states li.is-on { animation: none; }
}
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const items = document.querySelectorAll('.rg-track-states li');
        if (!items.length || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            items[0]?.classList.add('is-on');
            return;
        }
        let index = 0;
        const tick = () => {
            items.forEach((item, i) => item.classList.toggle('is-on', i === index));
            index = (index + 1) % items.length;
        };
        tick();
        window.setInterval(tick, 1600);
    })();
</script>
@endpush

@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">QR report posters</h1>
            <p class="text-muted mb-0">Select one or more barangays, generate posters, then print. Scanning opens the public report form with that barangay prefilled.</p>
        </div>
        @if ($posters->isNotEmpty())
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>Print selected
            </button>
        @endif
    </div>

    <div class="card mb-4 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.qr_posters.index') }}">
                <div class="mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <label class="form-label mb-0 fw-semibold">Barangays</label>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" id="qr-select-all">Select all</button>
                            <button type="button" class="btn btn-outline-secondary" id="qr-clear-all">Clear</button>
                        </div>
                    </div>
                    <div class="row g-2">
                        @foreach ($barangays as $barangay)
                            <div class="col-sm-6 col-md-4 col-lg-3">
                                <div class="form-check">
                                    <input class="form-check-input qr-barangay-check" type="checkbox" name="barangays[]" value="{{ $barangay }}" id="brgy-{{ $loop->index }}"
                                           @checked(in_array($barangay, $selected, true))>
                                    <label class="form-check-label" for="brgy-{{ $loop->index }}">{{ $barangay }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-qr-code me-1"></i>Generate posters
                </button>
            </form>
        </div>
    </div>

    @if ($posters->isEmpty())
        <div class="alert alert-light border no-print">Select barangays above and click Generate posters.</div>
    @else
        <div class="row g-4">
            @foreach ($posters as $poster)
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="card h-100 text-center p-3 qr-poster-card">
                        <div class="fw-semibold mb-2">Brgy. {{ $poster['barangay'] }}</div>
                        <img src="{{ $poster['qr'] }}" alt="QR for {{ $poster['barangay'] }}" class="img-fluid mx-auto mb-2" width="180" height="180">
                        <div class="small text-muted">Scan to report an incident</div>
                        <div class="small text-break mt-2">{{ $poster['url'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('styles')
<style>
@media print {
    .sidebar, .navbar, .btn, .nav-section-label, .no-print { display: none !important; }
    .qr-poster-card { break-inside: avoid; border: 1px solid #ccc; }
}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('qr-select-all')?.addEventListener('click', () => {
    document.querySelectorAll('.qr-barangay-check').forEach((el) => { el.checked = true; });
});
document.getElementById('qr-clear-all')?.addEventListener('click', () => {
    document.querySelectorAll('.qr-barangay-check').forEach((el) => { el.checked = false; });
});
</script>
@endpush
@endsection

@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">QR report posters</h1>
            <p class="text-muted mb-0">Print one poster per barangay. Scanning opens the public report form with that barangay prefilled.</p>
        </div>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Print
        </button>
    </div>

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
</div>

@push('styles')
<style>
@media print {
    .sidebar, .navbar, .btn, .nav-section-label { display: none !important; }
    .qr-poster-card { break-inside: avoid; border: 1px solid #ccc; }
}
</style>
@endpush
@endsection

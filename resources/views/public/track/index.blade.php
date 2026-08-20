@extends('layouts.public')

@section('title', 'Track Report')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div class="rg-page-head text-center" data-rg-reveal>
                <span class="rg-eyebrow"><i class="bi bi-search"></i>Status lookup</span>
                <h1 class="rg-page-title">Track Your Report</h1>
                <p class="rg-page-sub mx-auto">
                    Enter the tracking number you received when you submitted your incident report.
                </p>
            </div>

            <div class="card raniag-card" data-rg-reveal>
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
                                   value="{{ old('tracking_number') }}"
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
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-center gap-3 mt-4 small text-muted">
                <span><i class="bi bi-shield-lock me-1"></i>No account needed</span>
                <span><i class="bi bi-clock-history me-1"></i>Updates in plain language</span>
            </div>

            <p class="text-center text-muted small mt-3 mb-0">
                Lost your number? Contact {{ config('raniag.organization') }} for assistance, or
                <a href="{{ route('public.report.create') }}" class="btn btn-link p-0 align-baseline">file a new report</a>.
            </p>
        </div>
    </div>
</div>
@endsection

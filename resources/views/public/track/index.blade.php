@extends('layouts.public')

@section('title', 'Track Report')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="mb-4 text-center">
                <h1 class="h3 fw-bold mb-1">Track Your Report</h1>
                <p class="text-muted mb-0">Enter the tracking number you received when you submitted your incident report.</p>
            </div>

            <div class="card raniag-card">
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
                                   placeholder="e.g. RAN-20260518-A1B2"
                                   required
                                   autocomplete="off"
                                   spellcheck="false">
                            @error('tracking_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100 btn-lg">
                            <i class="bi bi-search me-2"></i>Look Up Report
                        </button>
                    </form>
                
            </div>

            <p class="text-center text-muted small mt-4 mb-0">
                Lost your number? Contact {{ config('raniag.organization') }} for assistance.
            </p>
        </div>
    </div>
</div>
@endsection


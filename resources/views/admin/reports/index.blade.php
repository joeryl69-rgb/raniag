<x-app-layout>
    <x-slot name="header">
        {{ __('Generate Reports') }}
    </x-slot>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <h5 class="mb-1 fw-bold text-primary">
                                <i class="bi bi-file-earmark-text me-2"></i>Generate Incident Report
                            </h5>
                            <p class="text-muted small mb-0">Select your filters and generate a downloadable PDF report for the requested period.</p>
                        </div>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm" data-loading-link data-loading-message="Returning to the dashboard...">
                            <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                            <div>{{ session('warning') }}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    <form action="{{ route('admin.reports.generate') }}" method="POST" class="row g-3" data-loading-message="Generating your PDF report...">
                        @csrf
                        <input type="hidden" name="download_token" id="download_token">

                        <div class="col-md-6">
                            <label for="date_from" class="form-label fw-semibold">Date From</label>
                            <input type="date" class="form-control @error('date_from') is-invalid @enderror" id="date_from" name="date_from" value="{{ old('date_from', now()->subDays(30)->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                            @error('date_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="date_to" class="form-label fw-semibold">Date To</label>
                            <input type="date" class="form-control @error('date_to') is-invalid @enderror" id="date_to" name="date_to" value="{{ old('date_to', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                            @error('date_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="barangay" class="form-label fw-semibold">Barangay (Optional)</label>
                            <select class="form-select @error('barangay') is-invalid @enderror" id="barangay" name="barangay">
                                <option value="">All Barangays</option>
                                @foreach($barangays as $barangay)
                                    <option value="{{ $barangay }}" {{ old('barangay') == $barangay ? 'selected' : '' }}>{{ $barangay }}</option>
                                @endforeach
                            </select>
                            @error('barangay')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="agency_id" class="form-label fw-semibold">Agency (Optional)</label>
                            <select class="form-select @error('agency_id') is-invalid @enderror" id="agency_id" name="agency_id">
                                <option value="">All Agencies</option>
                                @foreach($agencies as $agency)
                                    <option value="{{ $agency->id }}" {{ old('agency_id') == $agency->id ? 'selected' : '' }}>{{ $agency->name }} ({{ $agency->code }})</option>
                                @endforeach
                            </select>
                            @error('agency_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="incident_type_id" class="form-label fw-semibold">Incident Type (Optional)</label>
                            <select class="form-select @error('incident_type_id') is-invalid @enderror" id="incident_type_id" name="incident_type_id">
                                <option value="">All Types</option>
                                @foreach($incidentTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('incident_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @error('incident_type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary" data-loading-message="Generating your PDF report...">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Generate PDF Report
                            </button>
                            <button type="submit" formaction="{{ route('admin.reports.generate_excel') }}" class="btn btn-success" data-loading-message="Generating your Excel report...">
                                <i class="bi bi-file-earmark-excel me-2"></i>Generate Excel Report
                            </button>
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary ms-2" data-loading-link data-loading-message="Returning to the dashboard...">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>

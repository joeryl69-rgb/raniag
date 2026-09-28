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
                            <p class="text-muted small mb-0">Set the period first. Then choose one report. The three downloads answer different questions, so only the button you press is generated.</p>
                        </div>
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
                        <div class="col-12">
                            <h6 class="fw-bold mb-0">1. Set the period</h6>
                            <p class="small text-muted mb-0">These filters apply to whichever report you download below.</p>
                        </div>

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

                        <div class="col-md-6">
                            <label for="aor_scope" class="form-label fw-semibold">Which area</label>
                            <select class="form-select @error('aor_scope') is-invalid @enderror" id="aor_scope" name="aor_scope">
                                <option value="aor_only" {{ old('aor_scope', 'aor_only') == 'aor_only' ? 'selected' : '' }}>Inside Pamplona only</option>
                                <option value="outside_aor_only" {{ old('aor_scope') == 'outside_aor_only' ? 'selected' : '' }}>Outside Pamplona only</option>
                                <option value="all" {{ old('aor_scope') == 'all' ? 'selected' : '' }}>Inside and outside Pamplona</option>
                            </select>
                            @error('aor_scope')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Inside Pamplona is the default. Outside means the report was referred to another municipality.</div>
                        </div>

                        <div class="col-12">
                            <h6 class="fw-bold mb-1">2. Choose one report</h6>
                            <p class="small text-muted mb-0">Each card is a different file. Read the question it answers, then press only that button.</p>
                        </div>

                        <div class="col-lg-4">
                            <div class="border rounded-3 p-3 h-100 d-flex flex-column">
                                <div class="small text-primary fw-semibold">For the file</div>
                                <div class="fw-semibold mb-1">Incident register</div>
                                <p class="small text-muted">One row per report: tracking number, type, barangay, office, status, and time. Use this when you need the official list.</p>
                                <button type="submit" class="btn btn-primary w-100 mt-auto" data-loading-message="Building the incident register...">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>Download register
                                </button>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="border rounded-3 p-3 h-100 d-flex flex-column">
                                <div class="small text-success fw-semibold">For sorting</div>
                                <div class="fw-semibold mb-1">Working spreadsheet</div>
                                <p class="small text-muted">The same cases in Excel, with the open workload, response times, places to send people, and office load above the rows.</p>
                                <button type="submit" formaction="{{ route('admin.reports.generate_excel') }}" class="btn btn-success w-100 mt-auto" data-loading-message="Building the spreadsheet...">
                                    <i class="bi bi-file-earmark-excel me-2"></i>Download spreadsheet
                                </button>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="border rounded-3 p-3 h-100 d-flex flex-column border-primary">
                                <div class="small text-primary fw-semibold">For the morning briefing</div>
                                <div class="fw-semibold mb-1">Operations brief</div>
                                <p class="small text-muted mb-2">What is still open by priority, how long the first assignment took, which barangay needs which kind of team, which office is holding the open cases, and when reports arrived.</p>
                                <label for="view_mode" class="form-label small fw-semibold mb-1">Group arrivals by</label>
                                <select class="form-select form-select-sm mb-3 @error('view_mode') is-invalid @enderror" id="view_mode" name="view_mode">
                                    <option value="weekly" {{ old('view_mode', 'weekly') == 'weekly' ? 'selected' : '' }}>Week</option>
                                    <option value="monthly" {{ old('view_mode') == 'monthly' ? 'selected' : '' }}>Month</option>
                                    <option value="periodic" {{ old('view_mode') == 'periodic' ? 'selected' : '' }}>The whole date range</option>
                                </select>
                                <button type="submit" formaction="{{ route('admin.reports.generate_chart_summary') }}" class="btn btn-primary w-100 mt-auto" data-loading-message="Building the operations brief...">
                                    <i class="bi bi-clipboard-data me-2"></i>Download operations brief
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>

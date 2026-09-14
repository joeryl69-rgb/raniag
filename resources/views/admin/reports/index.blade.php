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

                        <div class="col-md-6">
                            <label for="aor_scope" class="form-label fw-semibold">AOR Scope</label>
                            <select class="form-select @error('aor_scope') is-invalid @enderror" id="aor_scope" name="aor_scope">
                                <option value="aor_only" {{ old('aor_scope', 'aor_only') == 'aor_only' ? 'selected' : '' }}>MDRRMO Pamplona AOR Only (Default)</option>
                                <option value="outside_aor_only" {{ old('aor_scope') == 'outside_aor_only' ? 'selected' : '' }}>Outside-AOR Only (referred to another jurisdiction)</option>
                                <option value="all" {{ old('aor_scope') == 'all' ? 'selected' : '' }}>All (AOR + Outside-AOR, clearly labeled)</option>
                            </select>
                            @error('aor_scope')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Replaces the old "include outside-AOR" checkbox with an explicit choice, so AOR vs. Outside-AOR is a real distinction on every report type below.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="view_mode" class="form-label fw-semibold">Report View <span class="text-muted fw-normal">(Chart Summary only)</span></label>
                            <select class="form-select @error('view_mode') is-invalid @enderror" id="view_mode" name="view_mode">
                                <option value="periodic" {{ old('view_mode', 'periodic') == 'periodic' ? 'selected' : '' }}>Periodic (single total for the whole date range)</option>
                                <option value="weekly" {{ old('view_mode') == 'weekly' ? 'selected' : '' }}>Weekly (grouped by calendar week)</option>
                                <option value="monthly" {{ old('view_mode') == 'monthly' ? 'selected' : '' }}>Monthly (grouped by calendar month)</option>
                            </select>
                            @error('view_mode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Controls how the Trend chart and period-over-period comparison in the Chart Summary are bucketed. PDF/Excel reports ignore this.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Charts to Include <span class="text-muted fw-normal">(Chart Summary only)</span></label>
                            <div class="border rounded-3 p-3 bg-white">
                                <div class="row g-2">
                                    @foreach($chartKeys as $chartKey)
                                        <div class="col-sm-6 col-lg-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="charts[]" value="{{ $chartKey }}" id="chart_{{ $chartKey }}" {{ old('charts') ? (in_array($chartKey, old('charts')) ? 'checked' : '') : 'checked' }}>
                                                <label class="form-check-label" for="chart_{{ $chartKey }}">
                                                    {{ $chartLabels[$chartKey] }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="form-text">Pick which charts to generate — the Chart Summary PDF renders exactly the ones checked, in this order, every time.</div>
                        </div>

                        <div class="col-12 mt-4">
                            {{-- flex-column on mobile: Bootstrap's default align-items:stretch on a
                                 column flex container makes each button fill the row's width, so
                                 the three actions stack cleanly instead of squeezing side-by-side.
                                 flex-sm-row + gap-2 restores the normal inline row on wider screens. --}}
                            <div class="d-flex flex-column flex-sm-row gap-2">
                                <button type="submit" class="btn btn-primary" data-loading-message="Generating your PDF report...">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>Generate PDF Report
                                </button>
                                <button type="submit" formaction="{{ route('admin.reports.generate_excel') }}" class="btn btn-success" data-loading-message="Generating your Excel report...">
                                    <i class="bi bi-file-earmark-excel me-2"></i>Generate Excel Report
                                </button>
                                <button type="submit" formaction="{{ route('admin.reports.generate_chart_summary') }}" class="btn btn-info text-white" data-loading-message="Generating your Chart Summary...">
                                    <i class="bi bi-bar-chart-line me-2"></i>Generate Chart Summary
                                </button>
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary" data-loading-link data-loading-message="Returning to the dashboard...">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>

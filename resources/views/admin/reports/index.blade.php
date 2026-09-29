<x-app-layout>
    <x-slot name="header">
        {{ __('Reports') }}
    </x-slot>

@php
    $registerUrl = route('admin.reports.generate');
    $excelUrl = route('admin.reports.generate_excel');
    $briefUrl = route('admin.reports.generate_chart_summary');
@endphp

<p class="small text-muted mb-3">Pick one file, set the dates, then download. The filters on the right apply only to that file.</p>

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
        <div>{{ session('warning') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ $registerUrl }}" method="POST" id="report-form" data-loading-message="Preparing the file...">
    @csrf
    <input type="hidden" name="download_token" id="download_token">

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="d-flex flex-column gap-2" role="radiogroup" aria-label="Which file">
                <label class="report-choice">
                    <input type="radio" name="report_kind" value="register" @checked(old('report_kind', 'register') === 'register') data-action="{{ $registerUrl }}" data-loading="Building the incident register...">
                    <span>
                        <span class="d-block fw-semibold">Incident register</span>
                        <span class="small text-muted">The official list: one row per report, with tracking number, type, barangay, office, status, and time. PDF.</span>
                    </span>
                </label>
                <label class="report-choice">
                    <input type="radio" name="report_kind" value="sheet" @checked(old('report_kind') === 'sheet') data-action="{{ $excelUrl }}" data-loading="Building the spreadsheet...">
                    <span>
                        <span class="d-block fw-semibold">Working spreadsheet</span>
                        <span class="small text-muted">The same cases in Excel, so you can sort them. Open workload, response times, and office load sit above the rows.</span>
                    </span>
                </label>
                <label class="report-choice">
                    <input type="radio" name="report_kind" value="brief" @checked(old('report_kind') === 'brief') data-action="{{ $briefUrl }}" data-loading="Building the operations brief...">
                    <span>
                        <span class="d-block fw-semibold">Operations brief</span>
                        <span class="small text-muted">For the morning briefing: what is still open, how long the first assignment took, which barangay needs which team, and when reports arrived. PDF.</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Download</h2>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="date_from" class="form-label">From</label>
                            <input type="date" class="form-control @error('date_from') is-invalid @enderror" id="date_from" name="date_from" value="{{ old('date_from', now()->subDays(30)->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                            @error('date_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="date_to" class="form-label">To</label>
                            <input type="date" class="form-control @error('date_to') is-invalid @enderror" id="date_to" name="date_to" value="{{ old('date_to', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                            @error('date_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-3 {{ old('report_kind') === 'brief' ? '' : 'd-none' }}" id="brief-grouping">
                        <label for="view_mode" class="form-label">Group arrivals by</label>
                        <select class="form-select @error('view_mode') is-invalid @enderror" id="view_mode" name="view_mode">
                            <option value="weekly" {{ old('view_mode', 'weekly') == 'weekly' ? 'selected' : '' }}>Week</option>
                            <option value="monthly" {{ old('view_mode') == 'monthly' ? 'selected' : '' }}>Month</option>
                            <option value="periodic" {{ old('view_mode') == 'periodic' ? 'selected' : '' }}>The whole date range</option>
                        </select>
                    </div>

                    <details class="mt-3" @if(old('barangay') || old('agency_id') || old('incident_type_id') || (old('aor_scope') && old('aor_scope') !== 'aor_only')) open @endif>
                        <summary class="small fw-semibold">Narrow the file</summary>
                        <div class="row g-3 mt-1">
                            <div class="col-12">
                                <label for="barangay" class="form-label">Barangay</label>
                                <select class="form-select @error('barangay') is-invalid @enderror" id="barangay" name="barangay">
                                    <option value="">All barangays</option>
                                    @foreach($barangays as $barangay)
                                        <option value="{{ $barangay }}" {{ old('barangay') == $barangay ? 'selected' : '' }}>{{ $barangay }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="agency_id" class="form-label">Office</label>
                                <select class="form-select @error('agency_id') is-invalid @enderror" id="agency_id" name="agency_id">
                                    <option value="">All offices</option>
                                    @foreach($agencies as $agency)
                                        <option value="{{ $agency->id }}" {{ old('agency_id') == $agency->id ? 'selected' : '' }}>{{ $agency->name }} ({{ $agency->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="incident_type_id" class="form-label">Incident type</label>
                                <select class="form-select @error('incident_type_id') is-invalid @enderror" id="incident_type_id" name="incident_type_id">
                                    <option value="">All types</option>
                                    @foreach($incidentTypes as $type)
                                        <option value="{{ $type->id }}" {{ old('incident_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="aor_scope" class="form-label">Area</label>
                                <select class="form-select @error('aor_scope') is-invalid @enderror" id="aor_scope" name="aor_scope">
                                    <option value="aor_only" {{ old('aor_scope', 'aor_only') == 'aor_only' ? 'selected' : '' }}>Inside Pamplona</option>
                                    <option value="outside_aor_only" {{ old('aor_scope') == 'outside_aor_only' ? 'selected' : '' }}>Referred outside Pamplona</option>
                                    <option value="all" {{ old('aor_scope') == 'all' ? 'selected' : '' }}>Inside and outside</option>
                                </select>
                            </div>
                        </div>
                    </details>

                    <button type="submit" class="btn btn-primary w-100 mt-3" id="report-download">
                        <i class="bi bi-download me-1"></i>Download incident register
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@push('styles')
<style>
.report-choice {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 14px 16px;
    background: #fff;
    cursor: pointer;
}
.report-choice:has(input:checked) {
    border-color: var(--raniag-primary, #0d6efd);
    box-shadow: 0 0 0 1px var(--raniag-primary, #0d6efd);
}
.report-choice input { margin-top: 4px; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const form = document.getElementById('report-form');
    const button = document.getElementById('report-download');
    const grouping = document.getElementById('brief-grouping');
    const labels = {
        register: 'Download incident register',
        sheet: 'Download spreadsheet',
        brief: 'Download operations brief',
    };
    function sync() {
        const picked = form.querySelector('input[name="report_kind"]:checked');
        if (!picked) return;
        form.action = picked.dataset.action;
        form.dataset.loadingMessage = picked.dataset.loading || 'Preparing the file...';
        button.dataset.loadingMessage = form.dataset.loadingMessage;
        button.innerHTML = '<i class="bi bi-download me-1"></i>' + (labels[picked.value] || 'Download');
        grouping.classList.toggle('d-none', picked.value !== 'brief');
    }
    form.querySelectorAll('input[name="report_kind"]').forEach((input) => input.addEventListener('change', sync));
    sync();
})();
</script>
@endpush
</x-app-layout>

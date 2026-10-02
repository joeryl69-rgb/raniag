<x-app-layout>
    <x-slot name="header">
        {{ __('Reports') }}
    </x-slot>

@php
    $registerUrl = route('admin.reports.generate');
    $excelUrl = route('admin.reports.generate_excel');
    $briefUrl = route('admin.reports.generate_chart_summary');
    $decisionUrl = route('admin.reports.decision');
    $decisionSections = [
        'summary' => 'Summary',
        'status' => 'Incident status',
        'priority' => 'Priority of open incidents',
        'types' => 'Open incidents by type',
        'places' => 'Open reports by barangay',
        'arrivals' => 'When reports arrived',
        'comparison' => 'Compare with another date range',
        'projection' => 'Projection for the next period',
        'repeats' => 'Repeat areas',
        'shelters' => 'People in shelters, including guests from outside Pamplona',
    ];
@endphp

<x-kpi-strip :items="[
    ['label' => 'Incident types', 'value' => $incidentTypes->count(), 'icon' => 'bi-tags', 'tone' => 'primary', 'sub' => 'Available in a report'],
    ['label' => 'Agencies', 'value' => $agencies->count(), 'icon' => 'bi-building', 'tone' => 'warning'],
    ['label' => 'Barangays', 'value' => count($barangays), 'icon' => 'bi-geo-alt', 'tone' => 'success', 'sub' => 'Places a report can cover'],
]" />

<p class="small text-muted mb-3">Choose a file, a period, and who it covers. Month, quarter, and year match the dashboards. Outside Pamplona reports and shelter guests are included when you ask for them.</p>

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
                        <span class="small text-muted">One row per report, plus shelter guests for the same dates. PDF.</span>
                    </span>
                </label>
                <label class="report-choice">
                    <input type="radio" name="report_kind" value="sheet" @checked(old('report_kind') === 'sheet') data-action="{{ $excelUrl }}" data-loading="Building the spreadsheet...">
                    <span>
                        <span class="d-block fw-semibold">Working spreadsheet</span>
                        <span class="small text-muted">Sortable rows, plus who is in a shelter and whether they live outside Pamplona.</span>
                    </span>
                </label>
                <label class="report-choice">
                    <input type="radio" name="report_kind" value="brief" @checked(old('report_kind') === 'brief') data-action="{{ $briefUrl }}" data-loading="Building the operations brief...">
                    <span>
                        <span class="d-block fw-semibold">Operations brief</span>
                        <span class="small text-muted">What is still open, how fast the first team was sent, and where to send people. PDF.</span>
                    </span>
                </label>
                <label class="report-choice">
                    <input type="radio" name="report_kind" value="decision" @checked(old('report_kind') === 'decision') data-action="{{ $decisionUrl }}" data-loading="Building the decision report...">
                    <span>
                        <span class="d-block fw-semibold">Decision report</span>
                        <span class="small text-muted">Pick the tables you need: comparison, repeat areas, and shelter guests. PDF.</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Download</h2>
                    <div class="d-flex flex-wrap gap-2 mb-3" role="group" aria-label="Period">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-report-period="month">This month</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-report-period="quarter">This quarter</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-report-period="year">This year</button>
                    </div>
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

                    <div class="mt-3 {{ in_array(old('report_kind'), ['brief', 'decision'], true) ? '' : 'd-none' }}" id="brief-grouping">
                        <label for="view_mode" class="form-label">Group arrivals by</label>
                        <select class="form-select @error('view_mode') is-invalid @enderror" id="view_mode" name="view_mode">
                            <option value="weekly" {{ old('view_mode', 'weekly') == 'weekly' ? 'selected' : '' }}>Week</option>
                            <option value="monthly" {{ old('view_mode') == 'monthly' ? 'selected' : '' }}>Month</option>
                            <option value="quarterly" {{ old('view_mode') == 'quarterly' ? 'selected' : '' }}>Quarter</option>
                            <option value="yearly" {{ old('view_mode') == 'yearly' ? 'selected' : '' }}>Year</option>
                            <option value="periodic" {{ old('view_mode') == 'periodic' ? 'selected' : '' }}>The whole date range</option>
                        </select>
                    </div>

                    <div class="mt-3">
                        <label for="aor_scope" class="form-label">Which reports</label>
                        <select class="form-select @error('aor_scope') is-invalid @enderror" id="aor_scope" name="aor_scope">
                            <option value="aor_only" {{ old('aor_scope', 'aor_only') == 'aor_only' ? 'selected' : '' }}>Inside Pamplona</option>
                            <option value="outside_aor_only" {{ old('aor_scope') == 'outside_aor_only' ? 'selected' : '' }}>Outside Pamplona only</option>
                            <option value="all" {{ old('aor_scope') == 'all' ? 'selected' : '' }}>Inside and outside</option>
                        </select>
                    </div>

                    <details class="mt-3" @if(old('barangay') || old('agency_id') || old('incident_type_id')) open @endif>
                        <summary class="small fw-semibold">Narrow further</summary>
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
                        </div>
                    </details>

                    <div class="mt-3 {{ old('report_kind') === 'decision' ? '' : 'd-none' }}" id="decision-options">
                        <div class="small fw-semibold mb-2">Include in the decision report</div>
                        @foreach($decisionSections as $key => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="sections[]" value="{{ $key }}" id="section-{{ $key }}" @checked(in_array($key, old('sections', array_keys($decisionSections)), true))>
                                <label class="form-check-label small" for="section-{{ $key }}">{{ $label }}</label>
                            </div>
                        @endforeach
                        <div class="row g-3 mt-1" id="compare-dates">
                            <div class="col-sm-6">
                                <label for="compare_from" class="form-label">Compare from</label>
                                <input type="date" class="form-control" id="compare_from" name="compare_from" value="{{ old('compare_from') }}" max="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-sm-6">
                                <label for="compare_to" class="form-label">Compare to</label>
                                <input type="date" class="form-control" id="compare_to" name="compare_to" value="{{ old('compare_to') }}" max="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-12">
                                <p class="small text-muted mb-0">Leave the comparison dates empty to use the period of the same length immediately before this range.</p>
                            </div>
                        </div>
                    </div>

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
[data-theme="dark"] .report-choice { background: #16213a; color: inherit; border-color: #2a3a5c; }
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
        decision: 'Download decision report',
    };
    const decisionOptions = document.getElementById('decision-options');
    const compareDates = document.getElementById('compare-dates');
    const comparisonBox = document.getElementById('section-comparison');
    function syncCompare() {
        const on = comparisonBox && comparisonBox.checked && !decisionOptions.classList.contains('d-none');
        compareDates.classList.toggle('d-none', !on);
        compareDates.querySelectorAll('input').forEach((input) => { input.disabled = !on; });
    }
    function sync() {
        const picked = form.querySelector('input[name="report_kind"]:checked');
        if (!picked) return;
        form.action = picked.dataset.action;
        form.dataset.loadingMessage = picked.dataset.loading || 'Preparing the file...';
        button.dataset.loadingMessage = form.dataset.loadingMessage;
        button.innerHTML = '<i class="bi bi-download me-1"></i>' + (labels[picked.value] || 'Download');
        grouping.classList.toggle('d-none', picked.value !== 'brief' && picked.value !== 'decision');
        decisionOptions.classList.toggle('d-none', picked.value !== 'decision');
        syncCompare();
    }
    form.querySelectorAll('input[name="report_kind"]').forEach((input) => input.addEventListener('change', sync));
    comparisonBox?.addEventListener('change', syncCompare);
    sync();

    function localDate(date) {
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return date.getFullYear() + '-' + month + '-' + day;
    }
    document.querySelectorAll('[data-report-period]').forEach((button) => {
        button.addEventListener('click', () => {
            const today = new Date();
            const key = button.dataset.reportPeriod;
            let start = new Date(today.getFullYear(), today.getMonth(), 1);
            let mode = 'monthly';
            if (key === 'quarter') {
                start = new Date(today.getFullYear(), Math.floor(today.getMonth() / 3) * 3, 1);
                mode = 'quarterly';
            } else if (key === 'year') {
                start = new Date(today.getFullYear(), 0, 1);
                mode = 'yearly';
            }
            document.getElementById('date_from').value = localDate(start);
            document.getElementById('date_to').value = localDate(today);
            const viewMode = document.getElementById('view_mode');
            if (viewMode) viewMode.value = mode;
            document.querySelectorAll('[data-report-period]').forEach((item) => {
                item.classList.toggle('btn-primary', item === button);
                item.classList.toggle('btn-outline-primary', item !== button);
            });
        });
    });
})();
</script>
@endpush
</x-app-layout>

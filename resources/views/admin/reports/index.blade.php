<x-app-layout>
    <x-slot name="header">
        {{ __('Reports') }}
    </x-slot>
<x-workspace-intro
    eyebrow="OVERSIGHT / DECISION SUPPORT"
    title="Operational reports"
    description="Choose a useful report, set its period, and keep a filed snapshot for later reference."
    icon="bi-file-earmark-bar-graph"
        variant="reports-studio"
/>

@php
    $registerUrl = route('admin.reports.generate');
    $excelUrl = route('admin.reports.generate_excel');
    $briefUrl = route('admin.reports.generate_chart_summary');
    $decisionUrl = route('admin.reports.decision');
    $operationsUrl = route('admin.reports.operations');
    $files = [
        'Incidents' => [
            ['kind' => 'daily', 'title' => 'Operations file', 'format' => 'PDF', 'copy' => 'Incidents, shelter guests, texts, papers, and support messages in one file.', 'action' => $operationsUrl, 'loading' => 'Building the operations file...', 'start' => true],
            ['kind' => 'register', 'title' => 'Case list', 'format' => 'PDF', 'copy' => 'One row for every report in the dates, plus shelter guests.', 'action' => $registerUrl, 'loading' => 'Building the case list...'],
            ['kind' => 'sheet', 'title' => 'Spreadsheet', 'format' => 'Excel', 'copy' => 'The same rows in a sheet you can sort.', 'action' => $excelUrl, 'loading' => 'Building the spreadsheet...'],
            ['kind' => 'brief', 'title' => 'Open work', 'format' => 'PDF', 'copy' => 'What is still open, how fast the first team was sent, and where to send people.', 'action' => $briefUrl, 'loading' => 'Building the open-work file...'],
            ['kind' => 'decision', 'title' => 'Meeting pack', 'format' => 'PDF', 'copy' => 'The tables for a meeting. Every table is included unless you remove some.', 'action' => $decisionUrl, 'loading' => 'Building the meeting pack...'],
        ],
        'Shelters and the map' => [
            ['kind' => 'shelter', 'title' => 'Shelter guests', 'format' => 'PDF', 'copy' => 'Who checked in during these dates, and which shelters are open now.', 'action' => $operationsUrl, 'loading' => 'Building the shelter file...'],
            ['kind' => 'places', 'title' => 'Hazard areas and shelters', 'format' => 'PDF', 'copy' => 'The map as it stands when you make the file.', 'action' => $operationsUrl, 'loading' => 'Building the map file...'],
        ],
        'Records to keep' => [
            ['kind' => 'messages', 'title' => 'Texts sent', 'format' => 'PDF', 'copy' => 'Messages the office sent in these dates.', 'action' => $operationsUrl, 'loading' => 'Building the message file...'],
            ['kind' => 'papers', 'title' => 'Papers filed', 'format' => 'PDF', 'copy' => 'Papers uploaded onto a case in these dates.', 'action' => $operationsUrl, 'loading' => 'Building the papers file...'],
            ['kind' => 'support', 'title' => 'Support messages', 'format' => 'PDF', 'copy' => 'What the support desk received in these dates.', 'action' => $operationsUrl, 'loading' => 'Building the support file...'],
            ['kind' => 'notices', 'title' => 'Public notices', 'format' => 'PDF', 'copy' => 'Notices published on the public page in these dates.', 'action' => $operationsUrl, 'loading' => 'Building the notices file...'],
            ['kind' => 'activity', 'title' => 'Office activity', 'format' => 'PDF', 'copy' => 'What staff did in the system in these dates.', 'action' => $operationsUrl, 'loading' => 'Building the activity file...'],
        ],
    ];
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
    $picked = old('report_kind', 'daily');
@endphp

<p class="reports-lead">Choose the file, then the dates. A copy stays under Saved files, so you can open it again without setting it up a second time.</p>

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
        <div>{{ session('warning') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ $operationsUrl }}" method="POST" id="report-form" data-loading-message="Preparing the file...">
    @csrf
    <input type="hidden" name="download_token" id="download_token">

    <div class="reports-step">
        <div class="reports-step-label">1. What do you need?</div>
        @foreach($files as $group => $cards)
            <h2 class="reports-group">{{ $group }}</h2>
            <div class="reports-grid" role="radiogroup" aria-label="{{ $group }}">
                @foreach($cards as $card)
                    <label class="report-card">
                        <input type="radio" name="report_kind" value="{{ $card['kind'] }}" @checked($picked === $card['kind']) data-action="{{ $card['action'] }}" data-loading="{{ $card['loading'] }}">
                        <span class="report-card-top">
                            <span class="report-card-title">{{ $card['title'] }}</span>
                            <span class="report-card-format">{{ $card['format'] }}</span>
                        </span>
                        @if(! empty($card['start']))
                            <span class="report-card-tag">Start here</span>
                        @endif
                        <span class="report-card-copy">{{ $card['copy'] }}</span>
                    </label>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="reports-step">
        <div class="reports-step-label">2. Which dates?</div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3" role="group" aria-label="Period">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-report-period="week">This week</button>
                    <button type="button" class="btn btn-sm btn-primary" data-report-period="month">This month</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-report-period="year">This year</button>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-3">
                        <label for="date_from" class="form-label">From</label>
                        <input type="date" class="form-control @error('date_from') is-invalid @enderror" id="date_from" name="date_from" value="{{ old('date_from', now()->startOfMonth()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                        @error('date_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label for="date_to" class="form-label">To</label>
                        <input type="date" class="form-control @error('date_to') is-invalid @enderror" id="date_to" name="date_to" value="{{ old('date_to', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                        @error('date_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <p class="small text-muted mb-0 mt-3">Week, month, and year use the same dates as the dashboards. You can still type a different start and end.</p>

                <div id="incident-only" class="{{ in_array($picked, ['daily', 'register', 'sheet', 'brief', 'decision'], true) ? '' : 'd-none' }}">
                    <div class="mt-3 {{ in_array($picked, ['brief', 'decision'], true) ? '' : 'd-none' }}" id="brief-grouping">
                        <label for="view_mode" class="form-label">Group arrivals by</label>
                        <select class="form-select @error('view_mode') is-invalid @enderror" id="view_mode" name="view_mode">
                            <option value="weekly" {{ old('view_mode', 'weekly') == 'weekly' ? 'selected' : '' }}>Week</option>
                            <option value="monthly" {{ old('view_mode') == 'monthly' ? 'selected' : '' }}>Month</option>
                            <option value="yearly" {{ old('view_mode') == 'yearly' ? 'selected' : '' }}>Year</option>
                            <option value="periodic" {{ old('view_mode') == 'periodic' ? 'selected' : '' }}>The whole date range</option>
                        </select>
                    </div>

                    <details class="mt-3" @if(old('barangay') || old('agency_id') || old('incident_type_id') || old('aor_scope', 'aor_only') !== 'aor_only') open @endif>
                        <summary class="small fw-semibold">Limit the incident rows</summary>
                        <p class="small text-muted mt-2 mb-0">Leave these as they are to include every report, office, and incident type.</p>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6 col-xl-3">
                                <label for="aor_scope" class="form-label">Which reports</label>
                                <select class="form-select @error('aor_scope') is-invalid @enderror" id="aor_scope" name="aor_scope">
                                    <option value="all" {{ old('aor_scope') == 'all' ? 'selected' : '' }}>All barangays</option>
                                    <option value="outside_aor_only" {{ old('aor_scope') == 'outside_aor_only' ? 'selected' : '' }}>All outside Pamplona</option>
                                    <option value="aor_only" {{ old('aor_scope', 'aor_only') == 'aor_only' ? 'selected' : '' }}>All inside Pamplona</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <label for="barangay" class="form-label">Barangay</label>
                                <select class="form-select @error('barangay') is-invalid @enderror" id="barangay" name="barangay">
                                    <x-place-filter-options :selected="old('barangay', '')" all-value="" />
                                </select>
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <label for="agency_id" class="form-label">Office</label>
                                <select class="form-select @error('agency_id') is-invalid @enderror" id="agency_id" name="agency_id">
                                    <option value="">All offices</option>
                                    @foreach($agencies as $agency)
                                        <option value="{{ $agency->id }}" {{ old('agency_id') == $agency->id ? 'selected' : '' }}>{{ $agency->name }} ({{ $agency->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-xl-3">
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

                    <div class="mt-3 {{ $picked === 'decision' ? '' : 'd-none' }}" id="decision-options">
                        <details @if(old('sections')) open @endif>
                            <summary class="small fw-semibold">Tables in the meeting pack</summary>
                            <p class="small text-muted mt-2 mb-2">Every table is already included. Uncheck one only if the meeting does not need it.</p>
                            <div class="row">
                                @foreach($decisionSections as $key => $label)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="sections[]" value="{{ $key }}" id="section-{{ $key }}" @checked(in_array($key, old('sections', array_keys($decisionSections)), true))>
                                            <label class="form-check-label small" for="section-{{ $key }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="row g-3 mt-1" id="compare-dates">
                                <div class="col-sm-6 col-lg-3">
                                    <label for="compare_from" class="form-label">Compare from</label>
                                    <input type="date" class="form-control" id="compare_from" name="compare_from" value="{{ old('compare_from') }}" max="{{ now()->format('Y-m-d') }}">
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <label for="compare_to" class="form-label">Compare to</label>
                                    <input type="date" class="form-control" id="compare_to" name="compare_to" value="{{ old('compare_to') }}" max="{{ now()->format('Y-m-d') }}">
                                </div>
                                <div class="col-12">
                                    <p class="small text-muted mb-0">Leave the comparison dates empty to use the period of the same length just before this range.</p>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3" id="report-download">
                    <i class="bi bi-download me-1"></i>Make this file
                </button>
            </div>
        </div>
    </div>
</form>

<div class="reports-step">
    <div class="reports-step-label">Saved files</div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if($archives->isEmpty())
                <p class="mb-0 text-muted">Files you make from this page are listed here. Open one again without choosing the dates a second time.</p>
            @else
                <div class="table-responsive rg-data-table-wrap">
                    <table class="table table-sm align-middle mb-0 rg-data-table">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Dates</th>
                                <th>Made</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($archives as $archive)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $archive->title }}</div>
                                        <div class="small text-muted">{{ strtoupper($archive->format) }}</div>
                                    </td>
                                    <td class="small">
                                        @if($archive->date_from && $archive->date_to)
                                            {{ $archive->date_from->format('M j, Y') }} – {{ $archive->date_to->format('M j, Y') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="small">
                                        {{ $archive->created_at?->format('M j, Y g:i a') }}
                                        @if($archive->user)
                                            <div class="text-muted">{{ $archive->user->name }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.reports.copy', $archive) }}">Open</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
.reports-lead { max-width: 46rem; color: var(--raniag-muted, #5c6b7a); margin-bottom: 1.25rem; }
.reports-step { margin-bottom: 1.5rem; }
.reports-step-label { font-size: .75rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--raniag-muted, #5c6b7a); margin-bottom: .75rem; }
.reports-group { font-size: .95rem; font-weight: 700; margin: .25rem 0 .6rem; }
.reports-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: .75rem; margin-bottom: 1.1rem; }
.report-card {
    display: flex; flex-direction: column; gap: .35rem;
    border: 1px solid var(--raniag-border, #e5e7eb);
    border-radius: 12px; padding: 14px 14px 12px;
    background: var(--raniag-card, #fff); color: inherit; cursor: pointer; margin: 0;
}
.report-card input { position: absolute; opacity: 0; pointer-events: none; }
.report-card-top { display: flex; justify-content: space-between; gap: .5rem; align-items: baseline; }
.report-card-title { font-weight: 700; }
.report-card-format, .report-card-tag { font-size: .7rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--raniag-primary, #0d6efd); }
.report-card-copy { font-size: .82rem; color: var(--raniag-muted, #5c6b7a); }
.report-card:has(input:checked) { border-color: var(--raniag-primary, #0d6efd); box-shadow: 0 0 0 1px var(--raniag-primary, #0d6efd); }
.report-card:focus-within { outline: 2px solid var(--raniag-primary, #0d6efd); outline-offset: 2px; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const form = document.getElementById('report-form');
    const button = document.getElementById('report-download');
    const grouping = document.getElementById('brief-grouping');
    const incidentOnly = document.getElementById('incident-only');
    const decisionOptions = document.getElementById('decision-options');
    const compareDates = document.getElementById('compare-dates');
    const comparisonBox = document.getElementById('section-comparison');
    const incidentKinds = ['daily', 'register', 'sheet', 'brief', 'decision'];

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
        incidentOnly.classList.toggle('d-none', !incidentKinds.includes(picked.value));
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
    function markPeriod(active) {
        document.querySelectorAll('[data-report-period]').forEach((item) => {
            const on = item === active;
            item.classList.toggle('btn-primary', on);
            item.classList.toggle('btn-outline-primary', !on);
        });
    }
    document.querySelectorAll('[data-report-period]').forEach((button) => {
        button.addEventListener('click', () => {
            const today = new Date();
            const key = button.dataset.reportPeriod;
            let start = new Date(today.getFullYear(), today.getMonth(), 1);
            let mode = 'monthly';
            if (key === 'week') {
                const day = today.getDay();
                const back = day === 0 ? 6 : day - 1;
                start = new Date(today.getFullYear(), today.getMonth(), today.getDate() - back);
                mode = 'weekly';
            } else if (key === 'year') {
                start = new Date(today.getFullYear(), 0, 1);
                mode = 'yearly';
            }
            document.getElementById('date_from').value = localDate(start);
            document.getElementById('date_to').value = localDate(today);
            const viewMode = document.getElementById('view_mode');
            if (viewMode) viewMode.value = mode;
            markPeriod(button);
        });
    });
    ['date_from', 'date_to'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => markPeriod(null));
    });
})();
</script>
@endpush
</x-app-layout>

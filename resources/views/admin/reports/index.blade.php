<x-app-layout>
    <x-slot name="header">
        {{ __('Reports') }}
    </x-slot>

@php
    $registerUrl = route('admin.reports.generate');
    $excelUrl = route('admin.reports.generate_excel');
    $briefUrl = route('admin.reports.generate_chart_summary');
@endphp

<p class="small text-muted mb-3">The picture below uses the same filters as the file you download. Change the dates or narrow the file and the columns update before you download.</p>

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
                        <span class="small text-muted">The official list for these filters. The first pages show incident status, priority of open incidents, and open reports by barangay, then one row per report. PDF.</span>
                    </span>
                </label>
                <label class="report-choice">
                    <input type="radio" name="report_kind" value="sheet" @checked(old('report_kind') === 'sheet') data-action="{{ $excelUrl }}" data-loading="Building the spreadsheet...">
                    <span>
                        <span class="d-block fw-semibold">Working spreadsheet</span>
                        <span class="small text-muted">The same cases in Excel, so you can sort them. Status, open incidents by type, response time, barangay, and office load sit above the rows.</span>
                    </span>
                </label>
                <label class="report-choice">
                    <input type="radio" name="report_kind" value="brief" @checked(old('report_kind') === 'brief') data-action="{{ $briefUrl }}" data-loading="Building the operations brief...">
                    <span>
                        <span class="d-block fw-semibold">Operations brief</span>
                        <span class="small text-muted">For the morning briefing: column charts of open priority and when reports arrived, plus which barangay needs which team and how long the first assignment took. PDF.</span>
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

<div class="card border-0 shadow-sm mt-4" id="report-picture">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h2 class="h6 fw-bold mb-1">Incident picture</h2>
                <p class="small text-muted mb-0" id="picture-lines">Loading the reports in this date range…</p>
            </div>
            <div class="d-flex gap-3 text-end">
                <div><div class="small text-muted">Reports</div><div class="fs-5 fw-bold" id="picture-total">—</div></div>
                <div><div class="small text-muted">Still open</div><div class="fs-5 fw-bold" id="picture-open">—</div></div>
                <div><div class="small text-muted">First assignment</div><div class="fs-6 fw-bold" id="picture-median">—</div></div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="small text-uppercase text-muted fw-semibold mb-2">When reports arrived</div>
                <div class="report-chart-wrap"><canvas id="picture-arrivals"></canvas></div>
            </div>
            <div class="col-lg-5">
                <div class="small text-uppercase text-muted fw-semibold mb-2">Incident status</div>
                <div id="picture-status" class="rg-cols"></div>
            </div>
            <div class="col-md-6">
                <div class="small text-uppercase text-muted fw-semibold mb-2">Priority of open incidents</div>
                <div id="picture-priority" class="rg-cols"></div>
            </div>
            <div class="col-md-6">
                <div class="small text-uppercase text-muted fw-semibold mb-2">Open incidents by type</div>
                <div id="picture-types" class="rg-cols"></div>
            </div>
            <div class="col-12">
                <div class="small text-uppercase text-muted fw-semibold mb-2">Open reports by barangay</div>
                <div id="picture-places" class="small"></div>
            </div>
        </div>
    </div>
</div>

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
.report-chart-wrap { height: 220px; }
.rg-cols { display: flex; align-items: flex-end; gap: 8px; min-height: 150px; }
.rg-col { flex: 1; min-width: 0; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; }
.rg-col-bar { width: 100%; max-width: 28px; border-radius: 6px 6px 2px 2px; background: #1a365d; }
.rg-col-n { font-size: 12px; font-weight: 700; }
.rg-col-l { font-size: 10px; text-align: center; color: #64748b; line-height: 1.2; margin-top: 4px; }
.rg-place { display: flex; justify-content: space-between; gap: 12px; padding: 6px 0; border-bottom: 1px solid #e5e7eb; }
[data-theme="dark"] .report-choice,
[data-theme="dark"] #report-picture { background: #16213a; color: inherit; }
[data-theme="dark"] .report-choice { border-color: #2a3a5c; }
[data-theme="dark"] .rg-place { border-color: #2a3a5c; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
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

    const pictureUrl = @json(route('admin.reports.picture'));
    let arrivalChart = null;
    let pictureTimer = null;

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    function columns(el, rows, color) {
        const max = Math.max(1, ...rows.map((row) => Number(row.count) || 0));
        el.innerHTML = rows.length ? rows.map((row) => {
            const height = Math.max(4, Math.round((Number(row.count) || 0) / max * 110));
            return `<div class="rg-col"><div class="rg-col-n">${esc(row.count)}</div><div class="rg-col-bar" style="height:${height}px;background:${color}"></div><div class="rg-col-l">${esc(row.label)}</div></div>`;
        }).join('') : '<div class="small text-muted">None in this range.</div>';
    }

    function renderPicture(data) {
        document.getElementById('picture-total').textContent = data.total ?? 0;
        document.getElementById('picture-open').textContent = data.open ?? 0;
        document.getElementById('picture-median').textContent = data.median_assignment || '—';
        document.getElementById('picture-lines').textContent = (data.lines && data.lines[0]) ? data.lines.join(' ') : 'No incidents in this date range.';
        columns(document.getElementById('picture-status'), data.status || [], '#1a365d');
        columns(document.getElementById('picture-priority'), data.priority || [], '#b45309');
        columns(document.getElementById('picture-types'), data.types || [], '#0f766e');
        const places = document.getElementById('picture-places');
        const placeRows = data.places || [];
        places.innerHTML = placeRows.length ? placeRows.map((row) => (
            `<div class="rg-place"><span>${esc(row.label)} · ${esc(row.type || '—')}</span><strong>${esc(row.open || 0)} open / ${esc(row.count)}</strong></div>`
        )).join('') : '<div class="text-muted">No barangay recorded for these reports.</div>';

        const arrivals = data.arrivals || [];
        const canvas = document.getElementById('picture-arrivals');
        if (arrivalChart) arrivalChart.destroy();
        if (!window.Chart) return;
        arrivalChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: arrivals.map((row) => row.label),
                datasets: [{
                    label: 'Reports',
                    data: arrivals.map((row) => row.count),
                    borderColor: '#1a365d',
                    backgroundColor: 'rgba(26, 54, 93, 0.15)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    }

    async function loadPicture() {
        try {
            const res = await fetch(pictureUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            renderPicture(await res.json());
        } catch (e) { /* leave the last picture */ }
    }

    form.querySelectorAll('input, select').forEach((field) => {
        field.addEventListener('change', () => {
            clearTimeout(pictureTimer);
            pictureTimer = setTimeout(loadPicture, 250);
        });
    });
    loadPicture();
})();
</script>
@endpush
</x-app-layout>

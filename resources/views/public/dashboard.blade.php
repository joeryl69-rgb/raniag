@extends('layouts.public')

@section('title', 'Community Dashboard')

@section('content')
<div class="container">
    <div class="text-center mb-4">
        <p class="text-uppercase small fw-semibold text-primary mb-1">Transparency</p>
        <h1 class="h3 fw-bold mb-2">Community Situational Dashboard</h1>
        <p class="text-muted mb-0" style="max-width:640px; margin:0 auto;">
            Aggregated, anonymized incident statistics for MDRRMO Pamplona. No personal information, exact addresses, or reporter details are shown here — only counts and trends to keep the community informed.
        </p>
    </div>

    <div id="pd-loading" class="text-center text-muted py-5">
        <div class="spinner-border text-primary mb-2" role="status"></div>
        <div>Loading community statistics…</div>
    </div>

    <div id="pd-content" class="d-none">
        {{-- KPI strip --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card raniag-card h-100 p-3 text-center">
                    <div class="fs-3 fw-bold text-primary" id="pd-total-month">—</div>
                    <div class="small text-muted">Reports This Month</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card raniag-card h-100 p-3 text-center">
                    <div class="fs-3 fw-bold text-success" id="pd-resolved-month">—</div>
                    <div class="small text-muted">Resolved This Month</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card raniag-card h-100 p-3 text-center">
                    <div class="fs-3 fw-bold" id="pd-total-all">—</div>
                    <div class="small text-muted">Total Reports (All Time)</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card raniag-card h-100 p-3 text-center">
                    <div class="fs-3 fw-bold text-warning" id="pd-active">—</div>
                    <div class="small text-muted">Currently Active</div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card raniag-card h-100 p-3">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-bar-chart-line me-2 text-primary"></i>6-Month Trend</h2>
                    <div style="height:260px;"><canvas id="pd-trend-chart"></canvas></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card raniag-card h-100 p-3">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-pie-chart me-2 text-primary"></i>By Incident Type</h2>
                    <div style="height:260px;"><canvas id="pd-type-chart"></canvas></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card raniag-card h-100 p-3">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-geo-alt me-2 text-primary"></i>Top Barangays (Report Count)</h2>
                    <div id="pd-barangay-list" class="small"></div>
                    <p class="small text-muted mt-2 mb-0"><i class="bi bi-shield-lock me-1"></i>Shown by barangay only — exact addresses are never made public.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card raniag-card h-100 p-3">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Activity</h2>
                    <div id="pd-activity-list" class="small"></div>
                </div>
            </div>
        </div>

        <p class="text-center text-muted small" id="pd-updated"></p>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const DATA_URL = @json(route('public.dashboard.data'));

    const statusLabels = {
        submitted: 'Submitted', received: 'Received', assigned: 'Assigned',
        in_progress: 'In Progress', pending_info: 'Pending Info',
        resolved: 'Resolved', closed: 'Closed', rejected: 'Rejected', outside_aor: 'Outside AOR',
    };

    fetch(DATA_URL, { headers: { Accept: 'application/json' } })
        .then(r => r.json())
        .then(render)
        .catch(() => {
            document.getElementById('pd-loading').innerHTML =
                '<div class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Unable to load statistics right now. Please try again later.</div>';
        });

    function render(data) {
        document.getElementById('pd-loading').classList.add('d-none');
        document.getElementById('pd-content').classList.remove('d-none');

        document.getElementById('pd-total-month').textContent = data.total_this_month;
        document.getElementById('pd-resolved-month').textContent = data.resolved_this_month;
        document.getElementById('pd-total-all').textContent = data.total_all_time;

        const active = (data.status_counts.submitted || 0) + (data.status_counts.received || 0)
            + (data.status_counts.assigned || 0) + (data.status_counts.in_progress || 0)
            + (data.status_counts.pending_info || 0);
        document.getElementById('pd-active').textContent = active;

        new Chart(document.getElementById('pd-trend-chart'), {
            type: 'bar',
            data: {
                labels: data.monthly_trend.map(m => m.label),
                datasets: [
                    { label: 'Total Reports', data: data.monthly_trend.map(m => m.total), backgroundColor: '#0e4a6b' },
                    { label: 'Resolved', data: data.monthly_trend.map(m => m.resolved), backgroundColor: '#16a34a' },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });

        new Chart(document.getElementById('pd-type-chart'), {
            type: 'doughnut',
            data: {
                labels: data.type_counts.map(t => t.name),
                datasets: [{
                    data: data.type_counts.map(t => t.count),
                    backgroundColor: data.type_counts.map(t => t.color || '#64748b'),
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } },
        });

        const brgyEl = document.getElementById('pd-barangay-list');
        if (!data.barangay_counts.length) {
            brgyEl.innerHTML = '<div class="text-muted">No data yet.</div>';
        } else {
            const max = Math.max(...data.barangay_counts.map(b => b.count));
            brgyEl.innerHTML = data.barangay_counts.map(b => `
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="flex-shrink-0" style="width:130px;">${b.barangay}</div>
                    <div class="flex-grow-1 bg-light rounded" style="height:10px;">
                        <div class="bg-primary rounded" style="height:10px; width:${Math.max(6, (b.count / max) * 100)}%;"></div>
                    </div>
                    <div class="flex-shrink-0 fw-semibold" style="width:24px; text-align:right;">${b.count}</div>
                </div>
            `).join('');
        }

        const actEl = document.getElementById('pd-activity-list');
        if (!data.recent_activity.length) {
            actEl.innerHTML = '<div class="text-muted">No recent activity.</div>';
        } else {
            actEl.innerHTML = data.recent_activity.map(a => `
                <div class="d-flex align-items-start gap-2 py-2 border-bottom">
                    <i class="bi ${a.icon || 'bi-bell'} mt-1" style="color:${a.color || '#64748b'};"></i>
                    <div class="flex-grow-1">
                        <div><strong>${a.type || 'Incident'}</strong> reported in ${a.barangay || 'Pamplona'}</div>
                        <div class="text-muted" style="font-size:.75rem;">${statusLabels[a.status] || a.status} · ${a.reported_at}</div>
                    </div>
                </div>
            `).join('');
        }

        document.getElementById('pd-updated').textContent = 'Last updated ' + new Date(data.generated_at).toLocaleString();
    }
})();
</script>
@endpush

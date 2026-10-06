@extends('layouts.public')

@section('title', 'Community Dashboard')

@section('content')
<div class="container rg-community" data-dashboard-url="{{ route('public.dashboard.data') }}">
    <header class="rg-community-head" data-rg-reveal>
        <div class="rg-community-copy">
            <span class="rg-eyebrow"><i class="bi bi-broadcast-pin"></i>Pamplona situation</span>
            <h1>What the community is reporting</h1>
            <p>Current incident patterns inside Pamplona. Counts are anonymous and never show a home, reporter, or exact address.</p>
        </div>
        <figure class="rg-community-guide" aria-hidden="true">
            <div class="rg-community-guide-map"></div>
            <img src="/images/guide/jo-map.png?v=1" alt="" width="168" height="224">
            <figcaption><span>FIELD GUIDE / PAMPLONA</span><strong>One community. Shared awareness.</strong></figcaption>
        </figure>
        <a href="{{ route('public.report.create') }}" class="btn btn-primary">
            <i class="bi bi-megaphone me-2"></i>Report an incident
        </a>
    </header>

    <div id="pd-loading" class="text-center text-muted py-5">
        <div class="spinner-border text-primary mb-2" role="status"></div>
        <div>Reading the Pamplona situation…</div>
    </div>

    <div id="pd-content" class="d-none">
        <div class="rg-period-bar">
            <span class="rg-community-label" id="pd-period-label">This month</span>
            <div class="rg-period-switch" role="group" aria-label="Chart period">
                <button type="button" class="is-on" data-period="month">Month</button>
                <button type="button" data-period="quarter">Quarter</button>
                <button type="button" data-period="year">Year</button>
            </div>
        </div>
        <section class="rg-kpi-row" aria-label="Situation totals">
            <article class="rg-kpi rg-kpi-open">
                <span class="rg-community-label">Open now</span>
                <strong id="pd-active">—</strong>
                <p id="pd-active-note">Reports still moving through review, assignment, or response.</p>
            </article>
            <article class="rg-kpi">
                <span class="rg-community-label" id="pd-received-label">This month</span>
                <strong id="pd-total-month">—</strong>
                <small id="pd-received-change">reports received</small>
            </article>
            <article class="rg-kpi">
                <span class="rg-community-label" id="pd-completed-label">Completed this month</span>
                <strong id="pd-resolved-month">—</strong>
                <small id="pd-resolution-note">of this month's reports</small>
            </article>
            <article class="rg-kpi">
                <span class="rg-community-label">Pamplona record</span>
                <strong id="pd-total-all">—</strong>
                <small>reports recorded</small>
            </article>
        </section>

        <section class="rg-active-callout" id="pd-most-active" hidden>
            <div>
                <span class="rg-community-label">Most active right now</span>
                <strong id="pd-most-active-title">—</strong>
                <p id="pd-most-active-copy"></p>
            </div>
            <span class="rg-risk-pill" id="pd-most-active-band">Watch</span>
        </section>

        <div class="rg-community-grid">
            <section class="rg-situation-card rg-trend-card">
                <div class="rg-card-head">
                    <div>
                        <span class="rg-community-label" id="pd-trend-caption">Six-month movement</span>
                        <h2>Reports and completed cases</h2>
                    </div>
                    <span class="rg-privacy-note"><i class="bi bi-shield-check"></i> Pamplona only</span>
                </div>
                <div class="rg-chart-stage"><canvas id="pd-trend-chart"></canvas></div>
            </section>

            <section class="rg-situation-card">
                <div class="rg-card-head">
                    <div>
                        <span class="rg-community-label">Current mix</span>
                        <h2>What people report</h2>
                    </div>
                </div>
                <div id="pd-type-list" class="rg-rank-list"></div>
            </section>

            <section class="rg-situation-card">
                <div class="rg-card-head">
                    <div>
                        <span class="rg-community-label">Place signal</span>
                        <h2>Reports by barangay</h2>
                    </div>
                </div>
                <div id="pd-barangay-list" class="rg-place-list"></div>
                <p class="rg-privacy-copy"><i class="bi bi-shield-lock me-1"></i>Barangay totals only. Exact locations remain private.</p>
            </section>

            <section class="rg-situation-card rg-risk-card">
                <div class="rg-card-head">
                    <div>
                        <span class="rg-community-label">Repeat areas</span>
                        <h2>Where the same incident keeps returning</h2>
                    </div>
                </div>
                <div class="rg-risk-scale" aria-hidden="true">
                    <span class="band-low">Low</span>
                    <span class="band-watch">Watch</span>
                    <span class="band-elevated">Elevated</span>
                    <span class="band-high">High</span>
                </div>
                <div id="pd-repeat-list" class="rg-repeat-list"></div>
            </section>

            <section class="rg-situation-card">
                <div class="rg-card-head">
                    <div>
                        <span class="rg-community-label">Latest movement</span>
                        <h2>Recent case activity</h2>
                    </div>
                </div>
                <div id="pd-activity-list" class="rg-activity-list"></div>
            </section>
        </div>

        <p class="rg-community-updated" id="pd-updated"></p>
    </div>
</div>
@endsection

@push('styles')
<style>
.rg-community { max-width: 1120px; }
.rg-community-head { position:relative;display:grid;grid-template-columns:minmax(0,1fr) minmax(220px,.48fr) auto;align-items:center;gap:24px;margin-bottom:26px;padding:20px 24px 20px 0; }
.rg-community-copy { position:relative;z-index:1; }
.rg-community-head h1 { max-width:17ch;margin:.45rem 0 .4rem;font-size:clamp(2rem,4vw,3.5rem);letter-spacing:-.045em;line-height:1; }
.rg-community-head p { max-width:700px;margin:0;color:#64748b; }
.rg-community-guide { position:relative;isolation:isolate;display:grid;place-items:center;min-height:230px;overflow:hidden;margin:0;border:1px solid #31505d;border-radius:6px;background:linear-gradient(145deg,#0d2430,#133845); }
.rg-community-guide-map { position:absolute;inset:0;z-index:-1;opacity:.65;background:radial-gradient(ellipse at 50% 60%,rgba(35,149,137,.3),transparent 55%),linear-gradient(rgba(144,194,198,.11) 1px,transparent 1px),linear-gradient(90deg,rgba(144,194,198,.11) 1px,transparent 1px);background-size:auto,24px 24px,24px 24px; }
.rg-community-guide img { position:absolute;right:3px;bottom:-13px;width:auto;height:214px;max-width:78%;object-fit:contain;filter:drop-shadow(0 8px 14px rgba(0,0,0,.3));animation:rg-community-jo-float 5s ease-in-out infinite; }
.rg-community-guide figcaption { position:absolute;z-index:1;left:12px;top:12px;display:grid;gap:5px;max-width:112px; }
.rg-community-guide figcaption span { color:#72d9c3;font:700 .52rem/1.2 ui-monospace,monospace;letter-spacing:.11em; }
.rg-community-guide figcaption strong { color:#e9f3f1;font-size:.73rem;line-height:1.25; }
@keyframes rg-community-jo-float { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-6px); } }
.rg-kpi-row { display:grid; grid-template-columns:1.2fr repeat(3,1fr); gap:14px; margin-bottom:18px; }
.rg-kpi { background:#fff; border:1px solid rgba(15,28,51,.08); border-radius:18px; padding:18px 18px 16px; min-width:0; box-shadow:0 18px 40px -34px rgba(15,28,51,.55); }
.rg-kpi strong { display:block; margin:.35rem 0 .45rem; color:#0f1c33; font-size:2.35rem; line-height:1; letter-spacing:-.04em; }
.rg-kpi small, .rg-kpi p { display:block; margin:0; color:#64748b; font-size:.78rem; line-height:1.35; }
.rg-kpi-open { background:linear-gradient(160deg,#0f766e,#115e59); border:0; color:#fff; }
.rg-kpi-open strong { color:#fff; font-size:3rem; }
.rg-kpi-open p { color:rgba(255,255,255,.78); }
.rg-community-label { text-transform:uppercase; letter-spacing:.12em; font-size:.68rem; font-weight:800; color:#0f766e; }
.rg-kpi-open .rg-community-label { color:#99f6e4; }
.rg-community-grid { display:grid; grid-template-columns:1.35fr .85fr; gap:18px; }
.rg-situation-card { background:#fff; border:1px solid rgba(15,28,51,.09); border-radius:20px; padding:22px; min-width:0; box-shadow:0 16px 40px -35px rgba(15,28,51,.45); }
.rg-card-head { display:flex; justify-content:space-between; align-items:start; gap:12px; margin-bottom:18px; }
.rg-card-head h2 { font-size:1.05rem; margin:.22rem 0 0; }
.rg-privacy-note { font-size:.72rem; color:#0f766e; background:#ecfdf5; border-radius:999px; padding:5px 9px; white-space:nowrap; }
.rg-chart-stage { height:285px; }
.rg-rank-row { display:grid; grid-template-columns:minmax(100px,1fr) 2fr 28px; align-items:center; gap:10px; margin-bottom:12px; }
.rg-rank-name { font-size:.82rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.rg-rank-track { height:8px; border-radius:999px; background:#eef2f7; overflow:hidden; }
.rg-rank-fill { height:100%; border-radius:inherit; }
.rg-rank-count { text-align:right; font-weight:800; font-size:.82rem; }
.rg-place-row { display:grid; grid-template-columns:110px 1fr 28px; gap:10px; align-items:center; padding:7px 0; }
.rg-place-name { font-size:.82rem; }
.rg-privacy-copy { margin:14px 0 0; color:#64748b; font-size:.75rem; }
.rg-activity-item { display:grid; grid-template-columns:32px 1fr; gap:10px; padding:10px 0; border-bottom:1px solid #edf0f4; }
.rg-activity-item:last-child { border-bottom:0; }
.rg-activity-icon { width:32px; height:32px; display:grid; place-items:center; border-radius:10px; background:#f1f5f9; }
.rg-activity-item strong { font-size:.83rem; }
.rg-activity-item small { display:block; color:#64748b; margin-top:2px; }
.rg-community-updated { text-align:right; margin:14px 3px 0; color:#64748b; font-size:.75rem; }
.rg-period-bar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px; }
.rg-period-switch { display:inline-flex; background:#fff; border:1px solid rgba(15,28,51,.12); border-radius:999px; padding:3px; }
.rg-period-switch button { border:0; background:transparent; border-radius:999px; padding:6px 14px; font-size:.78rem; font-weight:700; color:#475569; }
.rg-period-switch button.is-on { background:#0f766e; color:#fff; }
.rg-trend { display:inline-flex; align-items:center; gap:2px; font-weight:800; }
.rg-trend.up { color:#b45309; }
.rg-trend.down { color:#0f766e; }
.rg-trend.flat { color:#64748b; }
.rg-active-callout { display:flex; justify-content:space-between; align-items:center; gap:16px; background:#fff; border:1px solid rgba(15,28,51,.09); border-radius:20px; padding:18px 22px; margin-bottom:18px; }
.rg-active-callout strong { display:block; font-size:1.15rem; margin:.2rem 0; }
.rg-active-callout p { margin:0; color:#64748b; font-size:.84rem; }
.rg-risk-pill, .rg-repeat-band { font-size:.72rem; font-weight:800; border-radius:999px; padding:4px 10px; text-transform:uppercase; letter-spacing:.04em; }
.band-low, .rg-risk-pill.low { background:#dcfce7; color:#166534; }
.band-watch, .rg-risk-pill.watch { background:#fef9c3; color:#854d0e; }
.band-elevated, .rg-risk-pill.elevated { background:#ffedd5; color:#9a3412; }
.band-high, .rg-risk-pill.high { background:#fee2e2; color:#991b1b; }
.rg-risk-scale { display:grid; grid-template-columns:repeat(4,1fr); gap:6px; margin-bottom:14px; }
.rg-risk-scale span { text-align:center; font-size:.68rem; font-weight:800; border-radius:8px; padding:4px 0; }
.rg-repeat-row { display:grid; grid-template-columns:1fr auto auto; gap:10px; align-items:center; padding:8px 0; border-bottom:1px solid #edf0f4; }
.rg-repeat-row:last-child { border-bottom:0; }
.rg-repeat-row strong { font-size:.86rem; }
.rg-repeat-row small { display:block; color:#64748b; }
[data-theme="dark"] .rg-kpi { background:#142630; border-color:#304955; }
[data-theme="dark"] .rg-kpi strong { color:#f0f6f5; }
[data-theme="dark"] .rg-kpi-open strong, [data-theme="dark"] .rg-kpi-open { color:#fff; }
[data-theme="dark"] .rg-situation-card { background:#142630; border-color:#304955; }
[data-theme="dark"] .rg-rank-track,[data-theme="dark"] .rg-activity-icon { background:#203640; }
[data-theme="dark"] .rg-activity-item,[data-theme="dark"] .rg-repeat-row { border-color:#304955; }
[data-theme="dark"] .rg-community-head p,[data-theme="dark"] .rg-privacy-copy,[data-theme="dark"] .rg-active-callout p { color:#adc0c5; }
[data-theme="dark"] .rg-community-head h1,[data-theme="dark"] .rg-situation-card h2,[data-theme="dark"] .rg-active-callout strong { color:#e8f0f0; }
[data-theme="dark"] .rg-period-switch { background:#203640;border-color:#405862; }
[data-theme="dark"] .rg-period-switch button { color:#ccdcdf; }
[data-theme="dark"] .rg-period-switch button.is-on { color:#fff; }
@media(max-width:767.98px) {
  .rg-community-head { grid-template-columns:minmax(0,1fr) 120px;align-items:start;gap:12px;padding:10px 0 0; }
  .rg-community-copy { grid-column:1 / -1; }
  .rg-community-guide { grid-column:1 / -1;min-height:190px; }
  .rg-community-guide img { right:8%;height:184px;max-width:55%; }
  .rg-community-guide figcaption { left:14px;top:14px;max-width:140px; }
  .rg-community-guide figcaption strong { max-width:13ch;font-size:.78rem; }
  .rg-community-head>.btn { grid-column:1 / -1;justify-self:start; }
  .rg-kpi-row { grid-template-columns:1fr 1fr; }
  .rg-kpi-open { grid-column:1 / -1; }
  .rg-kpi-open strong { font-size:2.4rem; }
  .rg-community-grid { grid-template-columns:1fr; }
  .rg-period-bar, .rg-active-callout { align-items:flex-start; flex-direction:column; }
  .rg-chart-stage { height:235px; }
}
@media(prefers-reduced-motion:reduce) { .rg-community-guide img { animation:none; } }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const DATA_URL = document.querySelector('.rg-community')?.dataset.dashboardUrl;
    let trendChart = null;
    let currentPeriod = 'month';

    const statusLabels = {
        submitted: 'Submitted', received: 'Received', assigned: 'Assigned',
        in_progress: 'In Progress', pending_info: 'Pending Info',
        resolved: 'Resolved', closed: 'Closed', rejected: 'Rejected', outside_aor: 'Outside AOR',
    };

    document.querySelectorAll('.rg-period-switch button').forEach((button) => {
        button.addEventListener('click', () => {
            currentPeriod = button.dataset.period || 'month';
            document.querySelectorAll('.rg-period-switch button').forEach((item) => {
                item.classList.toggle('is-on', item === button);
            });
            load();
        });
    });

    load();

    function load() {
        const glue = DATA_URL.includes('?') ? '&' : '?';
        fetch(DATA_URL + glue + 'period=' + encodeURIComponent(currentPeriod), { headers: { Accept: 'application/json' } })
            .then(r => r.json())
            .then(render)
            .catch(() => {
                document.getElementById('pd-loading').classList.remove('d-none');
                document.getElementById('pd-loading').innerHTML =
                    '<div class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Unable to load statistics right now. Please try again later.</div>';
            });
    }

    function trendMarkup(change) {
        if (!change) return '';
        const arrow = change.direction === 'up' ? 'bi-arrow-up-short' : (change.direction === 'down' ? 'bi-arrow-down-short' : 'bi-dash');
        const word = change.direction === 'up' ? 'up' : (change.direction === 'down' ? 'down' : 'flat');
        return `<span class="rg-trend ${word}"><i class="bi ${arrow}"></i>${Math.abs(change.percent)}% vs previous</span>`;
    }

    function render(data) {
        document.getElementById('pd-loading').classList.add('d-none');
        document.getElementById('pd-content').classList.remove('d-none');
        // Content was hidden (d-none) when GSAP/ScrollTrigger first scanned
        // the page, so it never got a reveal animation. Trigger it manually
        // now that the container is actually visible.
        window.rgRevealNow && window.rgRevealNow('#pd-content');

        const period = data.period || { label: 'This month', caption: 'Last 6 months' };
        document.getElementById('pd-period-label').textContent = period.label;
        document.getElementById('pd-received-label').textContent = period.label;
        document.getElementById('pd-completed-label').textContent = 'Completed';
        document.getElementById('pd-trend-caption').textContent = period.caption;
        document.getElementById('pd-total-month').textContent = data.total_this_month;
        document.getElementById('pd-resolved-month').textContent = data.resolved_this_month;
        document.getElementById('pd-total-all').textContent = data.total_all_time;
        document.getElementById('pd-received-change').innerHTML = data.total_this_month
            ? `${trendMarkup(data.reports_change)}`
            : 'No reports in this period';
        const doneShare = data.total_this_month
            ? Math.round((data.resolved_this_month / data.total_this_month) * 100)
            : 0;
        document.getElementById('pd-resolution-note').innerHTML = data.total_this_month
            ? `${doneShare}% completed · ${trendMarkup(data.resolved_change)}`
            : 'No reports in this period';

        const active = (data.status_counts.submitted || 0) + (data.status_counts.received || 0)
            + (data.status_counts.assigned || 0) + (data.status_counts.in_progress || 0)
            + (data.status_counts.pending_info || 0);
        document.getElementById('pd-active').textContent = active;

        const most = data.most_active;
        const mostBox = document.getElementById('pd-most-active');
        if (most) {
            mostBox.hidden = false;
            document.getElementById('pd-most-active-title').textContent = `${most.type} in ${most.barangay}`;
            document.getElementById('pd-most-active-copy').textContent = `${most.count} report${most.count === 1 ? '' : 's'} in ${period.label}. Same window as the chart and the downloaded reports.`;
            const band = document.getElementById('pd-most-active-band');
            band.textContent = most.band;
            band.className = 'rg-risk-pill ' + String(most.band || '').toLowerCase();
        } else {
            mostBox.hidden = true;
        }

        const repeats = document.getElementById('pd-repeat-list');
        repeats.innerHTML = (data.repeat_areas || []).length
            ? data.repeat_areas.map((row) => `
                <div class="rg-repeat-row">
                    <div><strong>${esc(row.barangay)}</strong><small>${esc(row.type)}</small></div>
                    <span class="rg-rank-count">${Number(row.count) || 0}</span>
                    <span class="rg-repeat-band ${esc(String(row.band || '').toLowerCase())}">${esc(row.band)}</span>
                </div>
            `).join('')
            : '<div class="text-muted small">No barangay has the same incident more than once in this period.</div>';

        if (trendChart) trendChart.destroy();
        trendChart = new Chart(document.getElementById('pd-trend-chart'), {
            type: 'line',
            data: {
                labels: data.monthly_trend.map(m => m.label),
                datasets: [
                    { label: 'Reports', data: data.monthly_trend.map(m => m.total), borderColor: '#0e4a6b', backgroundColor: 'rgba(14,74,107,.12)', fill: true, tension: .35, pointRadius: 3 },
                    { label: 'Completed', data: data.monthly_trend.map(m => m.resolved), borderColor: '#0f766e', tension: .35, pointRadius: 3 },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' }, plugins: { legend: { position: 'bottom', align: 'start' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        });

        const typeEl = document.getElementById('pd-type-list');
        const typeMax = Math.max(1, ...data.type_counts.map(t => t.count));
        typeEl.innerHTML = data.type_counts.slice(0, 7).map(t => `
            <div class="rg-rank-row">
                <span class="rg-rank-name">${esc(t.name)}</span>
                <span class="rg-rank-track"><span class="rg-rank-fill" style="display:block;width:${Math.max(5, t.count / typeMax * 100)}%;background:${safeColor(t.color)}"></span></span>
                <span class="rg-rank-count">${Number(t.count) || 0}</span>
            </div>
        `).join('') || '<div class="text-muted small">No reports yet.</div>';

        const brgyEl = document.getElementById('pd-barangay-list');
        if (!data.barangay_counts.length) {
            brgyEl.innerHTML = '<div class="text-muted">No data yet.</div>';
        } else {
            const max = Math.max(...data.barangay_counts.map(b => b.count));
            brgyEl.innerHTML = data.barangay_counts.map(b => `
                <div class="rg-place-row">
                    <span class="rg-place-name">${esc(b.barangay)}</span>
                    <span class="rg-rank-track"><span class="rg-rank-fill" style="display:block;width:${Math.max(5, b.count / max * 100)}%;background:#2563eb"></span></span>
                    <span class="rg-rank-count">${Number(b.count) || 0}</span>
                </div>
            `).join('');
        }

        const actEl = document.getElementById('pd-activity-list');
        if (!data.recent_activity.length) {
            actEl.innerHTML = '<div class="text-muted">No recent activity.</div>';
        } else {
            actEl.innerHTML = data.recent_activity.map(a => `
                <div class="rg-activity-item">
                    <span class="rg-activity-icon"><i class="bi ${esc(a.icon || 'bi-bell')}" style="color:${safeColor(a.color)}"></i></span>
                    <div>
                        <strong>${esc(a.type || 'Incident')} · ${esc(a.barangay || 'Pamplona')}</strong>
                        <small>${esc(statusLabels[a.status] || a.status)} · ${esc(a.reported_at)}</small>
                    </div>
                </div>
            `).join('');
        }

        document.getElementById('pd-updated').textContent = 'Last updated ' + new Date(data.generated_at).toLocaleString();
    }

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function safeColor(value) {
        return /^#[0-9a-f]{6}$/i.test(String(value || '')) ? value : '#64748b';
    }
})();
</script>
@endpush

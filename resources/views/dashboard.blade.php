<x-app-layout>
    @php
        $role = auth()->user()->role->value;
        $isAdmin = auth()->user()->isAdministrator();
        $isAgency = auth()->user()->isAgency();
        $isPersonnel = auth()->user()->isPersonnel();
        // Route names use short prefixes (admin/agency/personnel) while the
        // role enum value is 'administrator' — map explicitly rather than
        // assuming they match.
        $routePrefix = $isAdmin ? 'admin' : ($isAgency ? 'agency' : 'personnel');
    @endphp

    <x-slot name="header">
        <div class="d-flex flex-column">
            <span>
                @if($isAdmin) Command Center
                @elseif($isAgency) Agency Operations
                @else Field Dashboard
                @endif
            </span>
            <small class="text-muted fw-normal">
                @if($isAdmin) Live decision-support overview &middot; MDRRMO Pamplona
                @elseif($isAgency) Live dispatches for {{ auth()->user()->agency->name ?? 'your agency' }}
                @else Your assigned response tasks &middot; MDRRMO Pamplona
                @endif
            </small>
        </div>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <style>
            :root {
                --raniag-danger: #dc3545;
                --raniag-warning: #f59e0b;
                --raniag-success: #16a34a;
                --raniag-ink: #0f172a;
                --raniag-muted: #64748b;
            }

            .dash-card {
                border: 1px solid var(--raniag-border);
                border-radius: 1rem;
                background: #fff;
                box-shadow: var(--raniag-card-shadow);
            }

            /* ---- KPI Cards ---- */
            .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
            @media (max-width: 1199.98px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
            @media (max-width: 575.98px) { .kpi-grid { grid-template-columns: 1fr; } }

            .kpi-card {
                position: relative;
                padding: 1.1rem 1.25rem;
                display: flex;
                align-items: center;
                gap: .9rem;
                text-decoration: none;
                color: inherit;
                border-left: 3px solid transparent;
                transition: border-color .15s ease, background .15s ease;
            }
            .kpi-card:hover { background: #fafbfc; color: inherit; }
            .kpi-card.tone-primary { border-left-color: var(--raniag-primary); }
            .kpi-card.tone-warning { border-left-color: #b45309; }
            .kpi-card.tone-success { border-left-color: var(--raniag-success); }
            .kpi-card.tone-danger  { border-left-color: var(--raniag-danger); }
            .kpi-icon { width: 34px; height: 34px; border-radius: .5rem; display: inline-flex; align-items: center; justify-content: center; font-size: .92rem; flex-shrink: 0; }
            .kpi-icon.tone-primary { background: var(--raniag-primary-light); color: var(--raniag-primary); }
            .kpi-icon.tone-warning { background: #fff4e0; color: #b45309; }
            .kpi-icon.tone-success { background: #e7f8ee; color: var(--raniag-success); }
            .kpi-icon.tone-danger  { background: #fde8ea; color: var(--raniag-danger); }
            .kpi-body { min-width: 0; }
            .kpi-card .kpi-label { font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--raniag-muted); }
            .kpi-card .kpi-value { font-size: 1.65rem; font-weight: 700; color: var(--raniag-ink); line-height: 1.25; }
            .kpi-card .kpi-sub { font-size: .74rem; color: var(--raniag-muted); }

            /* ---- Section headers ---- */
            .section-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .85rem; flex-wrap: wrap; }
            .section-head h5 { font-weight: 800; font-size: 1rem; margin: 0; color: var(--raniag-ink); display: flex; align-items: center; gap: .5rem; }
            .section-head .section-sub { font-size: .78rem; color: var(--raniag-muted); margin: 0; }
            .live-pulse { display: inline-flex; align-items: center; gap: .4rem; font-size: .7rem; font-weight: 700; color: var(--raniag-success); text-transform: uppercase; letter-spacing: .04em; }
            .live-pulse .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--raniag-success); box-shadow: 0 0 0 0 rgba(22,163,74,.6); animation: pulse-dot 1.8s infinite; }
            @keyframes pulse-dot { 0% { box-shadow: 0 0 0 0 rgba(22,163,74,.55);} 70% { box-shadow: 0 0 0 8px rgba(22,163,74,0);} 100% { box-shadow: 0 0 0 0 rgba(22,163,74,0);} }

            /* ---- Map card ---- */
            .map-card { overflow: hidden; }
            .map-card .map-toolbar {
                display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap;
                padding: .85rem 1.1rem; border-bottom: 1px solid var(--raniag-border);
            }
            .map-mode-switch { display: inline-flex; background: var(--raniag-surface); border-radius: .65rem; padding: .2rem; border: 1px solid var(--raniag-border); }
            .map-mode-switch button {
                border: 0; background: transparent; padding: .4rem .9rem; font-size: .8rem; font-weight: 700;
                color: var(--raniag-muted); border-radius: .5rem; display: inline-flex; align-items: center; gap: .4rem;
                transition: all .15s ease;
            }
            .map-mode-switch button.active { background: #fff; color: var(--raniag-primary); box-shadow: 0 1px 4px rgba(15,23,42,.12); }
            .map-legend { display: flex; align-items: center; gap: .9rem; font-size: .74rem; color: var(--raniag-muted); flex-wrap: wrap; }
            .map-legend .dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; margin-right: .3rem; }

            #dashboard-map { width: 100%; z-index: 1; }
            #dashboard-map.h-admin { height: 460px; }
            #dashboard-map.h-role { height: 420px; }
            @media (max-width: 767.98px) { #dashboard-map.h-admin, #dashboard-map.h-role { height: 320px; } }

            .layer-panel {
                position: absolute; top: 12px; right: 12px; z-index: 500; background: #fff; border-radius: .75rem;
                border: 1px solid var(--raniag-border); box-shadow: 0 .5rem 1.25rem rgba(15,23,42,.14);
                padding: .65rem; width: 208px; font-size: .8rem; display: none;
            }
            .layer-panel.show { display: block; }
            .layer-panel .layer-panel-title { font-weight: 700; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--raniag-muted); margin-bottom: .4rem; }
            .layer-panel .form-check { margin-bottom: .3rem; }
            .layer-panel .form-check-label { font-size: .82rem; }

            .map-nav { position: absolute; right: 12px; bottom: 34px; z-index: 500; display: flex; flex-direction: column; gap: 8px; }
            .map-nav-btn { width: 36px; height: 36px; border-radius: .6rem; background: #fff; border: 1px solid var(--raniag-border); box-shadow: 0 .3rem .8rem rgba(15,23,42,.12); display: flex; align-items: center; justify-content: center; font-size: 1rem; color: var(--raniag-ink); cursor: pointer; }
            .map-nav-btn:hover { background: #f8fafc; }
            .map-nav-group { background: #fff; border-radius: .6rem; border: 1px solid var(--raniag-border); box-shadow: 0 .3rem .8rem rgba(15,23,42,.12); overflow: hidden; }
            .map-nav-group .map-nav-btn { box-shadow: none; border: 0; border-radius: 0; }
            .map-nav-group .map-nav-btn:first-child { border-bottom: 1px solid var(--raniag-border); }
            .popup-view-btn { display: inline-flex; align-items: center; gap: .3rem; margin-top: .4rem; font-size: .74rem; font-weight: 700; color: var(--raniag-primary); text-decoration: none; }
            .popup-view-btn:hover { text-decoration: underline; }

            .map-wrap { position: relative; }
            .map-card:fullscreen {
                background: #fff; display: flex; flex-direction: column;
                margin: 0; padding: 0; border: 0; border-radius: 0; box-shadow: none;
                width: 100%; height: 100%;
            }
            .map-card:fullscreen .map-toolbar { flex-shrink: 0; }
            .map-card:fullscreen .map-wrap { flex: 1 1 auto; min-height: 0; }
            .map-card:fullscreen #dashboard-map { height: 100% !important; }
            .map-card:fullscreen .map-legend { flex-shrink: 0; }
            /* Safari/older WebKit fullscreen pseudo-class */
            .map-card:-webkit-full-screen {
                background: #fff; display: flex; flex-direction: column;
                margin: 0; padding: 0; border: 0; border-radius: 0; box-shadow: none;
                width: 100%; height: 100%;
            }
            .map-card:-webkit-full-screen .map-toolbar { flex-shrink: 0; }
            .map-card:-webkit-full-screen .map-wrap { flex: 1 1 auto; min-height: 0; }
            .map-card:-webkit-full-screen #dashboard-map { height: 100% !important; }

            /* Marker pin styling now lives in the shared .raniag-marker-pin
               class (public/css/public.css) so every map in the system uses
               the exact same pin shape/rules — see public/js/incident-map-icons.js. */

            /* ---- Analytics grid ---- */
            .analytics-card { padding: 1.1rem 1.15rem; }
            .analytics-card .chart-wrap { position: relative; height: 240px; }
            .analytics-card .chart-wrap.tall { height: 300px; }
            .mini-stat-row { display: flex; align-items: center; justify-content: space-between; padding: .5rem 0; border-bottom: 1px dashed var(--raniag-border); font-size: .84rem; }
            .mini-stat-row:last-child { border-bottom: 0; }
            .hotspot-row { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .55rem .1rem; border-bottom: 1px solid var(--raniag-border); font-size: .82rem; }
            .hotspot-row:last-child { border-bottom: 0; }
            .hotspot-count { font-weight: 800; color: var(--raniag-primary); background: var(--raniag-primary-light); padding: .1rem .55rem; border-radius: 999px; font-size: .76rem; }

            .badge-priority-low { background: #e7f0fd; color: #0d6efd; }
            .badge-priority-medium { background: #fff4e0; color: #b45309; }
            .badge-priority-high { background: #fde8ea; color: #dc3545; }
            .badge-priority-critical { background: #b91c1c; color: #fff; }

            .empty-note { color: var(--raniag-muted); font-size: .82rem; text-align: center; padding: 1.5rem .5rem; }
            .skeleton { background: linear-gradient(90deg, #eef1f5 25%, #f6f8fa 37%, #eef1f5 63%); background-size: 400% 100%; animation: sk 1.4s ease infinite; border-radius: .4rem; }
            @keyframes sk { 0% { background-position: 100% 50%; } 100% { background-position: 0 50%; } }

            .feed-item { display: flex; gap: .65rem; padding: .6rem 0; border-bottom: 1px solid var(--raniag-border); }
            .feed-item:last-child { border-bottom: 0; }
            .feed-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--raniag-primary); margin-top: .4rem; flex-shrink: 0; }
        </style>
    @endpush

    {{-- ===================== KPI STRIP ===================== --}}
    <div class="kpi-grid mb-4" id="kpi-grid">
        @if($isAdmin)
            <a href="{{ route('admin.incidents.index') }}" class="dash-card kpi-card tone-primary">
                <span class="kpi-icon tone-primary"><i class="bi bi-clipboard2-pulse"></i></span>
                <div class="kpi-body">
                    <div class="kpi-label">Total Incidents</div>
                    <div class="kpi-value" id="kpi-total">—</div>
                    <div class="kpi-sub" id="kpi-total-sub">All recorded cases</div>
                </div>
            </a>
            <a href="{{ route('admin.incidents.index', ['status' => 'in_progress']) }}" class="dash-card kpi-card tone-warning">
                <span class="kpi-icon tone-warning"><i class="bi bi-hourglass-split"></i></span>
                <div class="kpi-body">
                    <div class="kpi-label">In Progress</div>
                    <div class="kpi-value" id="kpi-in-progress">—</div>
                    <div class="kpi-sub" id="kpi-active-assignments">Active assignments —</div>
                </div>
            </a>
            <a href="{{ route('admin.incidents.index', ['status' => 'resolved']) }}" class="dash-card kpi-card tone-success">
                <span class="kpi-icon tone-success"><i class="bi bi-check-circle"></i></span>
                <div class="kpi-body">
                    <div class="kpi-label">Resolved</div>
                    <div class="kpi-value" id="kpi-resolved">—</div>
                    <div class="kpi-sub" id="kpi-resolved-week">Completed this week —</div>
                </div>
            </a>
            <a href="{{ route('admin.agencies.index') }}" class="dash-card kpi-card tone-danger">
                <span class="kpi-icon tone-danger"><i class="bi bi-diagram-3"></i></span>
                <div class="kpi-body">
                    <div class="kpi-label">Active Agencies</div>
                    <div class="kpi-value" id="kpi-agencies">—</div>
                    <div class="kpi-sub" id="kpi-avg-resolution">Avg. resolution —</div>
                </div>
            </a>
        @else
            <div class="dash-card kpi-card tone-primary">
                <span class="kpi-icon tone-primary"><i class="bi bi-clipboard2-pulse"></i></span>
                <div class="kpi-body">
                    <div class="kpi-label">{{ $isAgency ? 'Assigned Incidents' : 'My Assignments' }}</div>
                    <div class="kpi-value" id="kpi-assigned">—</div>
                    <div class="kpi-sub">Currently active</div>
                </div>
            </div>
            <div class="dash-card kpi-card tone-warning">
                <span class="kpi-icon tone-warning"><i class="bi bi-hourglass-split"></i></span>
                <div class="kpi-body">
                    <div class="kpi-label">Pending Resolution</div>
                    <div class="kpi-value" id="kpi-pending">—</div>
                    <div class="kpi-sub">In progress / awaiting info</div>
                </div>
            </div>
            <div class="dash-card kpi-card tone-success">
                <span class="kpi-icon tone-success"><i class="bi bi-activity"></i></span>
                <div class="kpi-body">
                    <div class="kpi-label">In Progress</div>
                    <div class="kpi-value" id="kpi-progress-count">—</div>
                    <div class="kpi-sub">Being worked right now</div>
                </div>
            </div>
            <div class="dash-card kpi-card tone-danger">
                <span class="kpi-icon tone-danger"><i class="bi bi-chat-left-text"></i></span>
                <div class="kpi-body">
                    <div class="kpi-label">SMS Alerts (7d)</div>
                    <div class="kpi-value" id="kpi-sms">—</div>
                    <div class="kpi-sub">Sent this week</div>
                </div>
            </div>
        @endif
    </div>

    {{-- ===================== LIVE MAP ===================== --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="dash-card map-card">
                <div class="map-toolbar">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-geo-alt-fill text-primary"></i> Situational Map</h5>
                        <span class="live-pulse"><span class="dot"></span> Live</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="map-mode-switch" role="group">
                            <button type="button" id="mode-live" class="active"><i class="bi bi-broadcast"></i> Live Map</button>
                            <button type="button" id="mode-layers"><i class="bi bi-layers"></i> Layers</button>
                        </div>
                        <button type="button" id="map-refresh-btn" class="btn btn-sm btn-outline-secondary" title="Refresh now">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <button type="button" id="map-fullscreen-btn" class="btn btn-sm btn-outline-secondary" title="Fullscreen">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                    </div>
                </div>
                <div class="map-wrap">
                    <div id="dashboard-map" class="{{ $isAdmin ? 'h-admin' : 'h-role' }}" data-role="{{ $role }}"></div>
                    <div class="map-nav">
                        <div class="map-nav-group">
                            <button type="button" class="map-nav-btn" id="map-zoom-in" title="Zoom in"><i class="bi bi-plus-lg"></i></button>
                            <button type="button" class="map-nav-btn" id="map-zoom-out" title="Zoom out"><i class="bi bi-dash-lg"></i></button>
                        </div>
                        <button type="button" class="map-nav-btn" id="map-recenter" title="Recenter on jurisdiction"><i class="bi bi-crosshair"></i></button>
                    </div>
                    <div class="layer-panel" id="layer-panel">
                        <div class="layer-panel-title">Base Map</div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="basemap" id="base-street" checked>
                            <label class="form-check-label" for="base-street">Street</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="basemap" id="base-satellite">
                            <label class="form-check-label" for="base-satellite">Satellite</label>
                        </div>
                        <hr class="my-2">
                        <div class="layer-panel-title">Overlays</div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="layer-boundary" checked>
                            <label class="form-check-label" for="layer-boundary">Municipal Boundary</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="layer-barangays">
                            <label class="form-check-label" for="layer-barangays">Barangay Borders</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="layer-markers" checked>
                            <label class="form-check-label" for="layer-markers">Incident Markers</label>
                        </div>
                    </div>
                </div>
                <div class="map-legend px-3 py-2 border-top">
                    <span><span class="dot" style="background:#b91c1c"></span>Critical</span>
                    <span><span class="dot" style="background:#dc3545"></span>High</span>
                    <span><span class="dot" style="background:#f59e0b"></span>Medium</span>
                    <span><span class="dot" style="background:#0d6efd"></span>Low</span>
                    <span class="ms-auto small" id="map-count-label">— points plotted</span>
                </div>
            </div>
        </div>
    </div>

    @if($isAdmin)
        {{-- ===================== ADMIN ANALYTICS ===================== --}}
        <div class="section-head">
            <h5><i class="bi bi-graph-up-arrow text-primary"></i> Analytics</h5>
            <p class="section-sub">Full jurisdiction insight, refreshed with the map</p>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-7">
                <div class="dash-card analytics-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="small text-uppercase text-muted">6-Week Volume Trend</strong>
                    </div>
                    <div class="chart-wrap"><canvas id="chart-trend"></canvas></div>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div class="dash-card analytics-card h-100">
                    <strong class="small text-uppercase text-muted d-block mb-2">Status Breakdown</strong>
                    <div class="chart-wrap"><canvas id="chart-status"></canvas></div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="dash-card analytics-card h-100">
                    <strong class="small text-uppercase text-muted d-block mb-2">Incidents by Category</strong>
                    <div class="chart-wrap"><canvas id="chart-category"></canvas></div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="dash-card analytics-card h-100">
                    <strong class="small text-uppercase text-muted d-block mb-2">Top Barangays</strong>
                    <div class="chart-wrap"><canvas id="chart-barangay"></canvas></div>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div class="dash-card analytics-card h-100">
                    <strong class="small text-uppercase text-muted d-block mb-2">Incidents by Priority</strong>
                    <div class="chart-wrap"><canvas id="chart-priority"></canvas></div>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div class="dash-card analytics-card h-100">
                    <strong class="small text-uppercase text-muted d-block mb-2">Agency Avg. Response Time (hrs)</strong>
                    <div class="chart-wrap"><canvas id="chart-agency-response"></canvas></div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="dash-card analytics-card h-100">
                    <strong class="small text-uppercase text-muted d-block mb-2"><i class="bi bi-fire text-danger"></i> Redundancy Hotspots</strong>
                    <div id="hotspot-list"><div class="empty-note">Loading hotspot data…</div></div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="dash-card analytics-card h-100">
                    <strong class="small text-uppercase text-muted d-block mb-2">Signal Health</strong>
                    <div id="signal-health">
                        <div class="mini-stat-row"><span><i class="bi bi-send text-success"></i> SMS Sent</span><strong id="sms-sent">—</strong></div>
                        <div class="mini-stat-row"><span><i class="bi bi-exclamation-triangle text-danger"></i> SMS Failed</span><strong id="sms-failed">—</strong></div>
                        <div class="mini-stat-row"><span><i class="bi bi-clock-history text-warning"></i> SMS Pending</span><strong id="sms-pending">—</strong></div>
                        <div class="mini-stat-row"><span><i class="bi bi-signpost-split text-primary"></i> Outside Jurisdiction</span><strong id="out-of-jurisdiction">—</strong></div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- ===================== AGENCY / PERSONNEL: DISPATCHES + FEED ===================== --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-7">
                <div class="dash-card analytics-card h-100">
                    <div class="section-head mb-2">
                        <h5 class="mb-0"><i class="bi bi-truck text-primary"></i> Active Dispatches</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr class="text-muted small text-uppercase">
                                    <th>Tracking #</th>
                                    <th>Type</th>
                                    <th>Barangay</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="dispatch-table-body">
                                <tr><td colspan="5" class="empty-note">Loading dispatches…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div class="dash-card analytics-card h-100">
                    <strong class="small text-uppercase text-muted d-block mb-2"><i class="bi bi-activity text-primary"></i> Recent Status Updates</strong>
                    <div id="status-feed"><div class="empty-note">Loading updates…</div></div>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="{{ asset('js/incident-map-icons.js') }}?v={{ @filemtime(public_path('js/incident-map-icons.js')) }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script>
        (function () {
            const ROLE = @json($role);
            const IS_ADMIN = @json($isAdmin);
            const API_URL = "{{ route($routePrefix . '.dashboard.api') }}";
            const BOUNDARY_URL = "{{ route($routePrefix . '.dashboard.boundary') }}";
            const BARANGAYS_URL = "{{ route($routePrefix . '.dashboard.barangays') }}";
            const INCIDENT_URL_BASE = "{{ route($routePrefix . '.incidents.show', ['incident' => '__ID__']) }}".replace('/__ID__', '');
            const CENTER = [12.5661, 121.3306]; // Pamplona, Camarines Norte fallback view
            const REFRESH_MS = 30000;

            let map, streetLayer, satLayer, boundaryLayer, barangayLayer, markerLayer, jurisdictionBounds;
            let charts = {};
            let currentPoints = [];

            document.addEventListener('DOMContentLoaded', function () {
                initMap();
                loadData();
                setInterval(loadData, REFRESH_MS);
                bindToolbar();
            });

            function bindToolbar() {
                const liveBtn = document.getElementById('mode-live');
                const layersBtn = document.getElementById('mode-layers');
                const panel = document.getElementById('layer-panel');

                liveBtn.addEventListener('click', function () {
                    liveBtn.classList.add('active');
                    layersBtn.classList.remove('active');
                    panel.classList.remove('show');
                });
                layersBtn.addEventListener('click', function () {
                    layersBtn.classList.add('active');
                    liveBtn.classList.remove('active');
                    panel.classList.toggle('show');
                });

                document.getElementById('map-refresh-btn').addEventListener('click', loadData);

                document.getElementById('map-fullscreen-btn').addEventListener('click', function () {
                    const wrap = document.querySelector('.map-card');
                    if (!document.fullscreenElement) {
                        (wrap.requestFullscreen || wrap.webkitRequestFullscreen)?.call(wrap);
                    } else {
                        (document.exitFullscreen || document.webkitExitFullscreen)?.call(document);
                    }
                });
                document.addEventListener('fullscreenchange', () => {
                    const btnIcon = document.querySelector('#map-fullscreen-btn i');
                    if (btnIcon) {
                        btnIcon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-arrows-fullscreen';
                    }
                    if (map) setTimeout(() => map.invalidateSize(), 150);
                });

                document.getElementById('map-zoom-in').addEventListener('click', () => map && map.zoomIn());
                document.getElementById('map-zoom-out').addEventListener('click', () => map && map.zoomOut());
                document.getElementById('map-recenter').addEventListener('click', () => {
                    if (!map) return;
                    if (jurisdictionBounds) map.fitBounds(jurisdictionBounds, { padding: [24, 24] });
                    else map.setView(CENTER, 13);
                });

                document.getElementById('base-street').addEventListener('change', () => setBasemap('street'));
                document.getElementById('base-satellite').addEventListener('change', () => setBasemap('satellite'));
                document.getElementById('layer-boundary').addEventListener('change', e => toggleLayer(boundaryLayer, e.target.checked));
                document.getElementById('layer-barangays').addEventListener('change', e => toggleLayer(barangayLayer, e.target.checked));
                document.getElementById('layer-markers').addEventListener('change', e => toggleLayer(markerLayer, e.target.checked));
            }

            function toggleLayer(layer, show) {
                if (!layer || !map) return;
                if (show) { if (!map.hasLayer(layer)) layer.addTo(map); }
                else { if (map.hasLayer(layer)) map.removeLayer(layer); }
            }

            function setBasemap(kind) {
                if (!map) return;
                if (kind === 'satellite') {
                    if (map.hasLayer(streetLayer)) map.removeLayer(streetLayer);
                    satLayer.addTo(map);
                } else {
                    if (map.hasLayer(satLayer)) map.removeLayer(satLayer);
                    streetLayer.addTo(map);
                }
            }

            function initMap() {
                const el = document.getElementById('dashboard-map');
                if (!el) return;

                map = L.map(el, { zoomControl: false }).setView(CENTER, 13);

                streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '' }).addTo(map);
                satLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, attribution: '' });

                markerLayer = L.layerGroup().addTo(map);
                barangayLayer = L.layerGroup();

                fetch(BOUNDARY_URL)
                    .then(r => r.ok ? r.json() : null)
                    .then(geo => {
                        if (!geo) return;
                        boundaryLayer = L.geoJSON(geo, {
                            style: { color: '#0b5ed7', weight: 2, fillOpacity: 0.03, dashArray: '4 3' }
                        }).addTo(map);
                        try { map.fitBounds(boundaryLayer.getBounds(), { padding: [24, 24] }); jurisdictionBounds = boundaryLayer.getBounds(); } catch (e) {}
                    })
                    .catch(() => {});

                fetch(BARANGAYS_URL)
                    .then(r => r.ok ? r.json() : null)
                    .then(geo => {
                        if (!geo) return;
                        L.geoJSON(geo, {
                            style: { color: '#20c997', weight: 1.5, fillOpacity: 0.04 },
                            onEachFeature: (feature, layer) => {
                                const name = feature.properties && (feature.properties.name || feature.properties.NAME || feature.properties.brgy);
                                if (name) layer.bindTooltip(name, { sticky: true });
                            }
                        }).addTo(barangayLayer);
                    })
                    .catch(() => {});
            }

            function priorityColorClass(priority) {
                const p = (priority || 'medium').toString().toLowerCase();
                if (p === 'critical') return 'p-critical';
                if (p === 'high') return 'p-high';
                if (p === 'low') return 'p-low';
                return 'p-medium';
            }

            function plotPoints(points) {
                if (!map) return;
                currentPoints = points;
                markerLayer.clearLayers();

                points.forEach(function (pt) {
                    if (!pt.latitude || !pt.longitude) return;
                    const lat = parseFloat(pt.latitude), lng = parseFloat(pt.longitude);
                    if (Number.isNaN(lat) || Number.isNaN(lng)) return;

                    const typeObj = pt.incident_type || pt.incidentType || {};
                    // Centralized icon+color resolution (see public/js/incident-map-icons.js)
                    // keeps this pin visually identical to every other incident map
                    // in the system, driven by the incident type's own configured icon.
                    const marker = L.marker([lat, lng], {
                        icon: window.RaniagIcons.buildDivIcon({
                            icon: typeObj.icon,
                            color: typeObj.color,
                            priority: pt.priority,
                            size: 26,
                        })
                    });

                    const typeName = typeObj.name || 'Incident';
                    marker.bindPopup(
                        `<strong>${escapeHtml(pt.tracking_number || ('#' + pt.id))}</strong><br>` +
                        `${escapeHtml(typeName)}<br>` +
                        `<span class="text-capitalize">${escapeHtml((pt.status || '').replace(/_/g, ' '))}</span>` +
                        (pt.barangay ? `<br><small class="text-muted">${escapeHtml(pt.barangay)}</small>` : '') +
                        `<br><a class="popup-view-btn" href="${INCIDENT_URL_BASE}/${pt.id}"><i class="bi bi-box-arrow-up-right"></i> View</a>`
                    );
                    marker.addTo(markerLayer);
                });

                const plotted = markerLayer.getLayers().length;
                const label = document.getElementById('map-count-label');
                if (label) label.textContent = `${plotted} point${plotted === 1 ? '' : 's'} plotted`;
            }

            function escapeHtml(str) {
                return (str ?? '').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
            }

            function loadData() {
                fetch(API_URL, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => IS_ADMIN ? renderAdmin(data) : renderRole(data))
                    .catch(err => console.error('Dashboard load error:', err));
            }

            // ---------------- Admin rendering ----------------
            function renderAdmin(data) {
                const sb = data.incident_status_breakdown || {};
                setText('kpi-total', data.total_incidents ?? 0);
                setText('kpi-in-progress', sb.in_progress ?? 0);
                setText('kpi-resolved', sb.resolved ?? 0);
                setText('kpi-agencies', data.active_agencies ?? 0);
                setText('kpi-total-sub', `Submitted ${sb.submitted ?? 0} · Closed ${sb.closed ?? 0}`);
                setText('kpi-active-assignments', `Active assignments ${data.active_assignments ?? 0}`);
                setText('kpi-resolved-week', `Completed this week ${data.assignments_completed_this_week ?? 0}`);

                const analytics = data.analytics || {};
                setText('kpi-avg-resolution', `Avg. resolution ${analytics.avg_resolution_hours ?? 0}h`);

                plotPoints(data.recent_incidents || []);

                const sms = data.sms_stats || {};
                setText('sms-sent', sms.sent ?? 0);
                setText('sms-failed', sms.failed ?? 0);
                setText('sms-pending', sms.pending ?? 0);
                setText('out-of-jurisdiction', analytics.out_of_jurisdiction_count ?? 0);

                renderTrendChart(analytics.weekly_trends || []);
                renderStatusChart(sb);
                renderCategoryChart(analytics.categories || {});
                renderBarangayChart(analytics.barangays || {});
                renderPriorityChart(analytics.priority_breakdown || {});
                renderAgencyResponseChart(analytics.agency_response_times || {});
                renderHotspots(analytics.redundancy_hotspots || []);
            }

            function setText(id, val) {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            }

            function ensureChart(key, ctxId, config) {
                const ctx = document.getElementById(ctxId);
                if (!ctx) return;
                if (charts[key]) { charts[key].destroy(); }
                charts[key] = new Chart(ctx, config);
            }

            const PALETTE = ['#0b5ed7', '#20c997', '#f59e0b', '#dc3545', '#8b5cf6', '#0891b2', '#84cc16', '#ec4899'];

            function renderTrendChart(rows) {
                ensureChart('trend', 'chart-trend', {
                    type: 'line',
                    data: {
                        labels: rows.map(r => r.label),
                        datasets: [{
                            label: 'Incidents',
                            data: rows.map(r => r.count),
                            borderColor: '#0b5ed7',
                            backgroundColor: 'rgba(11,94,215,0.08)',
                            fill: true, tension: 0.35, pointRadius: 3, pointBackgroundColor: '#0b5ed7'
                        }]
                    },
                    options: baseOpts({ legend: false })
                });
            }

            function renderStatusChart(sb) {
                const labels = Object.keys(sb);
                ensureChart('status', 'chart-status', {
                    type: 'doughnut',
                    data: { labels: labels.map(l => l.replace(/_/g, ' ')), datasets: [{ data: labels.map(l => sb[l]), backgroundColor: PALETTE, borderWidth: 2, borderColor: '#fff' }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } } }
                });
            }

            function renderCategoryChart(cat) {
                const labels = Object.keys(cat);
                ensureChart('category', 'chart-category', {
                    type: 'bar',
                    data: { labels, datasets: [{ data: labels.map(l => cat[l]), backgroundColor: '#0b5ed7', borderRadius: 6, maxBarThickness: 26 }] },
                    options: baseOpts({ legend: false, indexAxis: 'y' })
                });
            }

            function renderBarangayChart(brgy) {
                const labels = Object.keys(brgy);
                ensureChart('barangay', 'chart-barangay', {
                    type: 'bar',
                    data: { labels, datasets: [{ data: labels.map(l => brgy[l]), backgroundColor: '#20c997', borderRadius: 6, maxBarThickness: 26 }] },
                    options: baseOpts({ legend: false })
                });
            }

            const PRIORITY_COLORS = { critical: '#b91c1c', high: '#dc3545', medium: '#f59e0b', low: '#0d6efd' };
            function renderPriorityChart(priority) {
                const labels = Object.keys(priority);
                ensureChart('priority', 'chart-priority', {
                    type: 'doughnut',
                    data: {
                        labels: labels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
                        datasets: [{ data: labels.map(l => priority[l]), backgroundColor: labels.map(l => PRIORITY_COLORS[l] || '#94a3b8'), borderWidth: 2, borderColor: '#fff' }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } } }
                });
            }

            function renderAgencyResponseChart(rt) {
                const labels = Object.keys(rt);
                ensureChart('agencyResponse', 'chart-agency-response', {
                    type: 'bar',
                    data: { labels, datasets: [{ data: labels.map(l => rt[l]), backgroundColor: '#8b5cf6', borderRadius: 6, maxBarThickness: 22 }] },
                    options: baseOpts({ legend: false, indexAxis: 'y' })
                });
            }

            function baseOpts(opts) {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: opts.indexAxis || 'x',
                    plugins: { legend: { display: !!opts.legend, position: 'bottom' } },
                    scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f4f8' } } }
                };
            }

            function renderHotspots(rows) {
                const el = document.getElementById('hotspot-list');
                if (!el) return;
                if (!rows.length) { el.innerHTML = '<div class="empty-note">No repeat hotspots detected recently.</div>'; return; }
                el.innerHTML = rows.map(r => `
                    <div class="hotspot-row">
                        <span><i class="bi bi-geo-alt text-danger"></i> <strong>${escapeHtml(r.barangay)}</strong> &middot; ${escapeHtml(r.type)}</span>
                        <span class="hotspot-count">${r.count}×</span>
                    </div>
                `).join('');
            }

            // ---------------- Agency / Personnel rendering ----------------
            function renderRole(data) {
                const sb = data.incident_status_breakdown || {};
                setText('kpi-assigned', data.total_assigned_incidents ?? 0);
                setText('kpi-pending', data.pending_resolutions ?? 0);
                setText('kpi-progress-count', sb.in_progress ?? 0);
                setText('kpi-sms', data.sms_alerts_this_week ?? 0);

                plotPoints(data.active_dispatches || []);
                renderDispatchTable(data.active_dispatches || []);
                renderStatusFeed(data.recent_status_updates || []);
            }

            function renderDispatchTable(rows) {
                const body = document.getElementById('dispatch-table-body');
                if (!body) return;
                if (!rows.length) { body.innerHTML = '<tr><td colspan="5" class="empty-note">No active dispatches right now.</td></tr>'; return; }
                body.innerHTML = rows.map(r => {
                    const typeName = r.incident_type && r.incident_type.name ? r.incident_type.name : '—';
                    const priority = (r.priority || 'medium').toLowerCase();
                    return `
                        <tr>
                            <td class="fw-semibold">${escapeHtml(r.tracking_number || ('#' + r.id))}</td>
                            <td>${escapeHtml(typeName)}</td>
                            <td>${escapeHtml(r.barangay || '—')}</td>
                            <td><span class="badge badge-priority-${priority} text-capitalize">${escapeHtml(priority)}</span></td>
                            <td class="text-capitalize">${escapeHtml((r.status || '').replace(/_/g, ' '))}</td>
                        </tr>
                    `;
                }).join('');
            }

            function renderStatusFeed(rows) {
                const el = document.getElementById('status-feed');
                if (!el) return;
                if (!rows.length) { el.innerHTML = '<div class="empty-note">No recent updates.</div>'; return; }
                el.innerHTML = rows.map(r => `
                    <div class="feed-item">
                        <span class="feed-dot"></span>
                        <div>
                            <div class="small"><span class="text-capitalize">${escapeHtml((r.from_status || '').replace(/_/g, ' '))}</span> → <strong class="text-capitalize">${escapeHtml((r.to_status || '').replace(/_/g, ' '))}</strong></div>
                            ${r.comment ? `<div class="small text-muted">${escapeHtml(r.comment)}</div>` : ''}
                            <div class="small text-muted">${r.created_at ? new Date(r.created_at).toLocaleString() : ''}</div>
                        </div>
                    </div>
                `).join('');
            }
        })();
        </script>
    @endpush
</x-app-layout>
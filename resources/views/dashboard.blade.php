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
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
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
                box-shadow: 0 14px 30px -22px rgba(15, 23, 42, 0.45);
                transition: box-shadow .18s ease, transform .18s ease;
            }
            .map-card .map-toolbar {
                background: #fff;
                border-bottom: 1px solid var(--raniag-border);
                border-radius: 1rem 1rem 0 0;
            }
            #map-refresh-btn.is-refreshing i { animation: map-refresh-spin .7s linear infinite; }
            @keyframes map-refresh-spin { to { transform: rotate(360deg); } }

            /* ---- KPI Cards ---- */
            .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
            @media (max-width: 1199.98px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
            @media (max-width: 575.98px) { .kpi-grid { grid-template-columns: 1fr; } }

            .kpi-card {
                position: relative;
                padding: 1rem 1rem .95rem;
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: .1rem;
                text-decoration: none;
                color: inherit;
                min-width: 0;
            }
            .kpi-card:hover { background: #f8fafc; color: inherit; }
            .kpi-icon { position: absolute; top: .85rem; right: .85rem; width: 32px; height: 32px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: .9rem; }
            .kpi-icon.tone-primary { background: var(--raniag-primary-light); color: var(--raniag-primary); }
            .kpi-icon.tone-warning { background: #fff4e0; color: #b45309; }
            .kpi-icon.tone-success { background: #e7f8ee; color: var(--raniag-success); }
            .kpi-icon.tone-danger  { background: #fde8ea; color: var(--raniag-danger); }
            .kpi-body { min-width: 0; display: flex; flex-direction: column; padding-right: 2.4rem; }
            .kpi-card .kpi-value { order: 1; }
            .kpi-card .kpi-label { order: 2; }
            .kpi-card .kpi-trend { order: 3; }
            .kpi-card .kpi-sub { order: 4; }
            .kpi-card .kpi-label { font-size: .78rem; font-weight: 600; color: var(--raniag-muted); }
            .kpi-card .kpi-value { font-size: 1.7rem; font-weight: 750; letter-spacing: -.03em; color: var(--raniag-ink); line-height: 1.1; }
            .kpi-card .kpi-sub { font-size: .74rem; color: var(--raniag-muted); }
            .kpi-trend { display: inline-flex; align-items: center; gap: .1rem; font-size: .72rem; font-weight: 800; margin-top: .15rem; }
            .kpi-trend.up { color: #b45309; }
            .kpi-trend.down { color: #0f766e; }
            .kpi-trend.flat { color: var(--raniag-muted); }
            .risk-pill { font-size: .68rem; font-weight: 800; border-radius: 999px; padding: .15rem .5rem; text-transform: uppercase; }
            .risk-pill.low { background: #dcfce7; color: #166534; }
            .risk-pill.watch { background: #fef9c3; color: #854d0e; }
            .risk-pill.elevated { background: #ffedd5; color: #9a3412; }
            .risk-pill.high { background: #fee2e2; color: #991b1b; }

            /* ---- Performance rings ---- */
            .perf-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
            @media (max-width: 991.98px) { .perf-grid { grid-template-columns: 1fr 1fr; } }
            @media (max-width: 575.98px) { .perf-grid { grid-template-columns: 1fr; } }
            .perf-ring-card { padding: 1.2rem 1rem 1.35rem; display: flex; flex-direction: column; align-items: center; text-align: center; }
            .ring-wrap { position: relative; width: 128px; height: 128px; margin-bottom: .7rem; }
            .ring-svg { width: 100%; height: 100%; transform: rotate(-90deg); }
            .ring-track { fill: none; stroke: var(--raniag-surface); stroke-width: 10; }
            .ring-fill { fill: none; stroke-width: 10; stroke-linecap: round; stroke-dasharray: 326.73 326.73; stroke-dashoffset: 326.73; transition: stroke-dashoffset 1s cubic-bezier(.4,0,.2,1); }
            .ring-fill.tone-primary { stroke: var(--raniag-primary); }
            .ring-fill.tone-success { stroke: var(--raniag-success); }
            .ring-fill.tone-warning { stroke: #b45309; }
            .ring-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
            .ring-value { font-size: 1.55rem; font-weight: 800; color: var(--raniag-ink); line-height: 1; }
            .ring-unit { font-size: .68rem; font-weight: 700; color: var(--raniag-muted); margin-top: .15rem; text-transform: uppercase; letter-spacing: .03em; }
            .ring-label { font-weight: 700; font-size: .85rem; color: var(--raniag-ink); display: flex; align-items: center; gap: .35rem; }
            .ring-sub { font-size: .74rem; color: var(--raniag-muted); margin-top: .2rem; min-height: 1.1em; }

            /* ---- Section headers ---- */
            .section-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .85rem; flex-wrap: wrap; }
            .section-head h5 { font-weight: 800; font-size: 1rem; margin: 0; color: var(--raniag-ink); display: flex; align-items: center; gap: .5rem; }
            .section-head .section-sub { font-size: .78rem; color: var(--raniag-muted); margin: 0; }
            .live-pulse { display: inline-flex; align-items: center; gap: .4rem; font-size: .7rem; font-weight: 700; color: var(--raniag-success); text-transform: uppercase; letter-spacing: .04em; }
            .live-pulse .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--raniag-success); box-shadow: 0 0 0 0 rgba(22,163,74,.6); animation: pulse-dot 1.8s infinite; }
            @keyframes pulse-dot { 0% { box-shadow: 0 0 0 0 rgba(22,163,74,.55);} 70% { box-shadow: 0 0 0 8px rgba(22,163,74,0);} 100% { box-shadow: 0 0 0 0 rgba(22,163,74,0);} }

            /* ---- New-incident alert banner ---- */
            .incident-alert-banner {
                display: none;
                align-items: center;
                gap: .85rem;
                flex-wrap: wrap;
                padding: .85rem 1.1rem;
                margin-bottom: 1rem;
                border-radius: 1rem;
                border: 1px solid rgba(220, 53, 69, .35);
                background: linear-gradient(135deg, #fff5f5 0%, #ffe4e6 100%);
                box-shadow: 0 0 0 0 rgba(220, 53, 69, .35);
                animation: none;
            }
            .incident-alert-banner.is-visible { display: flex; }
            .incident-alert-banner .alert-pulse-dot {
                width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0;
                background: #dc3545;
                box-shadow: 0 0 0 0 rgba(220, 53, 69, .7);
                animation: alert-dot-pulse 1.4s infinite;
            }
            .incident-alert-banner .alert-body { flex: 1; min-width: 0; }
            .incident-alert-banner .alert-title {
                font-size: .78rem; font-weight: 800; letter-spacing: .04em;
                text-transform: uppercase; color: #b91c1c; margin-bottom: .15rem;
            }
            .incident-alert-banner .alert-copy { font-size: .92rem; color: #0f172a; font-weight: 600; }
            .incident-alert-banner .alert-meta { font-size: .78rem; color: #64748b; }
            @keyframes alert-banner-pulse {
                0%, 100% { box-shadow: none; }
            }
            @keyframes alert-dot-pulse {
                0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, .65); }
                70% { box-shadow: 0 0 0 12px rgba(220, 53, 69, 0); }
                100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
            }

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
            .legend-line { display: inline-block; width: 16px; height: 0; border-top-width: 3px; border-top-style: solid; margin-right: .35rem; vertical-align: middle; }
            .legend-muni { border-top-color: #0b3a8c; }
            .legend-brgy { border-top-color: #0f766e; border-top-style: dashed; }
            .map-referred { border-top: 1px solid var(--raniag-border); background: #fff8eb; padding: .75rem 1.1rem .9rem; }
            .map-referred-head { display: flex; gap: .6rem; align-items: flex-start; color: #8a5a00; margin-bottom: .45rem; }
            .map-referred-head i { font-size: 1.1rem; margin-top: .1rem; }
            .map-referred-head strong { display: block; color: #5c3b00; }
            .map-referred-head span { display: block; font-size: .78rem; }
            .map-referred a { display: flex; justify-content: space-between; gap: .75rem; padding: .4rem 0; border-top: 1px dashed rgba(138, 90, 0, .25); color: inherit; text-decoration: none; font-size: .84rem; }
            .map-referred a:hover { color: var(--raniag-primary); }
            .map-referred .ref-track { font-weight: 800; }

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
            .map-filters {
                display: flex; flex-wrap: wrap; gap: .45rem; align-items: center;
                padding: .55rem 1.1rem; border-bottom: 1px solid var(--raniag-border);
                background: #f8fafc;
            }
            .map-filters select {
                font-size: .78rem; font-weight: 600; border-radius: .5rem;
                border: 1px solid var(--raniag-border); padding: .3rem .55rem;
                background: #fff; color: var(--raniag-ink); max-width: 9.5rem;
            }
            .map-insight-panel {
                position: absolute; left: 12px; top: 12px; z-index: 500;
                width: min(280px, calc(100% - 24px));
                background: #fff; border-radius: .85rem;
                border: 1px solid var(--raniag-border);
                box-shadow: 0 .5rem 1.25rem rgba(15,23,42,.14);
                padding: .75rem .85rem; font-size: .82rem;
                display: none;
            }
            .map-insight-panel.is-open { display: block; }
            .map-insight-panel .insight-title {
                font-size: .68rem; font-weight: 800; letter-spacing: .05em;
                text-transform: uppercase; color: var(--raniag-muted); margin-bottom: .35rem;
            }
            .map-insight-panel .insight-name { font-weight: 800; font-size: .98rem; color: var(--raniag-ink); }
            .map-insight-panel .insight-stat {
                display: flex; justify-content: space-between; gap: .5rem;
                padding: .35rem 0; border-top: 1px dashed var(--raniag-border); margin-top: .35rem;
            }
            .map-insight-panel .insight-close {
                position: absolute; top: .45rem; right: .45rem; border: 0; background: transparent;
                color: var(--raniag-muted); font-size: .9rem; line-height: 1; cursor: pointer;
            }
            @media (max-width: 575.98px) {
                .map-insight-panel { left: 8px; right: 8px; width: auto; top: auto; bottom: 52px; }
            }

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
            .analytics-card .chart-wrap.short { height: 150px; }
            .pulse-row { display: grid; grid-template-columns: 7.4rem 1fr 2.2rem; gap: .45rem; align-items: center; margin: .4rem 0; font-size: .78rem; }
            .pulse-track { height: 8px; border-radius: 99px; background: #e8eef8; overflow: hidden; }
            .pulse-fill { display: block; height: 100%; width: 0; border-radius: 99px; transition: width .9s ease; }
            [data-theme="dark"] .pulse-track { background: #1c2b47; }
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

            .ops-row {
                display: grid;
                grid-template-columns: 7.5rem 1fr auto;
                gap: .65rem;
                align-items: center;
                padding: .7rem .15rem;
                border-bottom: 1px solid var(--raniag-border);
                color: inherit;
                text-decoration: none;
            }
            .ops-row:last-child { border-bottom: 0; }
            .ops-row:hover { background: var(--raniag-primary-light); }
            .ops-track { font-weight: 800; font-size: .84rem; }
            .ops-meta { font-size: .78rem; color: var(--raniag-muted); }
            .ops-age { font-size: .75rem; font-weight: 700; color: var(--raniag-muted); white-space: nowrap; }

            [data-theme="dark"] .dash-card,
            [data-theme="dark"] .map-insight-panel {
                background: var(--raniag-surface, #16213a);
                color: #e2e8f0;
            }
            [data-theme="dark"] .map-filters,
            [data-theme="dark"] .map-filters select,
            [data-theme="dark"] .map-toolbar,
            [data-theme="dark"] .kpi-card:hover {
                background: #1c2b47;
                color: #e2e8f0;
            }
            [data-theme="dark"] .map-filters select { border-color: #334155; }
        </style>
    @endpush

    {{-- New-incident alert (shown when poll detects a newer report) --}}
    <div id="incident-alert-banner" class="incident-alert-banner" role="alert" aria-live="assertive">
        <span class="alert-pulse-dot" aria-hidden="true"></span>
        <div class="alert-body">
            <div class="alert-title">New Incident Alert</div>
            <div class="alert-copy" id="incident-alert-copy">—</div>
            <div class="alert-meta" id="incident-alert-meta"></div>
        </div>
        <a href="#" id="incident-alert-link" class="btn btn-sm btn-danger">
            <i class="bi bi-box-arrow-up-right me-1"></i>Open Case
        </a>
        <button type="button" class="btn btn-sm btn-outline-danger" id="incident-alert-dismiss" aria-label="Dismiss">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>


    {{-- ===================== KPI STRIP ===================== --}}
    <div class="kpi-grid mb-4" id="kpi-grid">
        @if($isAdmin)
            <a href="{{ route('admin.incidents.index') }}" class="dash-card kpi-card tone-primary">
                <span class="kpi-icon tone-primary"><i class="bi bi-clipboard2-pulse"></i></span>
                <div class="kpi-body">
                    <div class="kpi-label">Total Incidents</div>
                    <div class="kpi-value" id="kpi-total">—</div>
                    <div class="kpi-trend flat" id="kpi-reports-trend"></div>
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
                    <div class="kpi-trend flat" id="kpi-resolved-trend"></div>
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
    {{-- ===================== LIVE MAP (primary — map-first for command decisions) ===================== --}}
    <div class="row mb-4" id="situational-map-section">
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
                @if($isAdmin)
                <div class="map-filters" id="map-filters">
                    <span class="small text-muted fw-semibold text-uppercase" style="letter-spacing:.04em;font-size:.68rem;">Filters</span>
                    <select id="filter-status" aria-label="Filter by status">
                        <option value="">All statuses</option>
                        <option value="submitted">Submitted</option>
                        <option value="received">Received</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In progress</option>
                        <option value="pending_info">Pending info</option>
                    </select>
                    <select id="filter-priority" aria-label="Filter by priority">
                        <option value="">All priorities</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                    <select id="filter-barangay" aria-label="Filter by barangay">
                        <option value="">All barangays</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="filter-clear">Clear</button>
                </div>
                @endif
                <div class="map-wrap">
                    <div id="dashboard-map" class="{{ $isAdmin ? 'h-admin' : 'h-role' }}" data-role="{{ $role }}"></div>
                    <div class="map-insight-panel" id="map-insight-panel" aria-live="polite">
                        <button type="button" class="insight-close" id="map-insight-close" aria-label="Close">&times;</button>
                        <div class="insight-title">Area insight</div>
                        <div class="insight-name" id="map-insight-name">—</div>
                        <div class="insight-stat"><span>Open reports</span><strong id="map-insight-open">—</strong></div>
                        <div class="insight-stat"><span>Hotspot flag</span><strong id="map-insight-hotspot">—</strong></div>
                        <div class="small text-muted mt-2 mb-0" id="map-insight-note">Tap a marker or choose a barangay filter to inspect this area.</div>
                    </div>
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
                            <input class="form-check-input" type="checkbox" id="layer-barangays" checked>
                            <label class="form-check-label" for="layer-barangays">Barangay borders</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="layer-markers" checked>
                            <label class="form-check-label" for="layer-markers">Incident Markers</label>
                        </div>
                    </div>
                </div>
                <div class="map-legend px-3 py-2 border-top">
                    <span><i class="legend-line legend-muni"></i>Pamplona boundary</span>
                    <span><i class="legend-line legend-brgy"></i>Barangay</span>
                    <span><span class="dot" style="background:#b91c1c"></span>Critical</span>
                    <span><span class="dot" style="background:#dc3545"></span>High</span>
                    <span><span class="dot" style="background:#f59e0b"></span>Medium</span>
                    <span><span class="dot" style="background:#0d6efd"></span>Low</span>
                    <span class="ms-auto small" id="map-count-label">— points plotted</span>
                </div>
                <div class="map-referred d-none" id="map-referred">
                    <div class="map-referred-head">
                        <i class="bi bi-signpost-split"></i>
                        <div>
                            <strong>Referred outside Pamplona</strong>
                            <span>These reports are not drawn on the map. The map stays on the municipal boundary.</span>
                        </div>
                    </div>
                    <div id="map-referred-list"></div>
                </div>
            </div>
        </div>
    </div>


    @if($isAdmin)
        {{-- ===================== PERFORMANCE OVERVIEW ===================== --}}
        <div class="mb-4">
            <div class="section-head">
                <h5><i class="bi bi-speedometer2 text-primary"></i> Performance Overview</h5>
                <p class="section-sub mb-0">How the response system is performing right now</p>
            </div>
            <div class="perf-grid">
                <div class="dash-card perf-ring-card">
                    <div class="ring-wrap">
                        <svg viewBox="0 0 120 120" class="ring-svg">
                            <circle class="ring-track" cx="60" cy="60" r="52"></circle>
                            <circle class="ring-fill tone-primary" id="ring-resolution-fill" cx="60" cy="60" r="52"></circle>
                        </svg>
                        <div class="ring-center">
                            <div class="ring-value" id="ring-resolution-value">—</div>
                            <div class="ring-unit">%</div>
                        </div>
                    </div>
                    <div class="ring-label"><i class="bi bi-check-circle"></i> Resolution Rate</div>
                    <div class="ring-sub" id="ring-resolution-sub">Cases resolved or closed</div>
                </div>
                <div class="dash-card perf-ring-card">
                    <div class="ring-wrap">
                        <svg viewBox="0 0 120 120" class="ring-svg">
                            <circle class="ring-track" cx="60" cy="60" r="52"></circle>
                            <circle class="ring-fill tone-success" id="ring-coverage-fill" cx="60" cy="60" r="52"></circle>
                        </svg>
                        <div class="ring-center">
                            <div class="ring-value" id="ring-coverage-value">—</div>
                            <div class="ring-unit">%</div>
                        </div>
                    </div>
                    <div class="ring-label"><i class="bi bi-diagram-3"></i> Dispatch Coverage</div>
                    <div class="ring-sub" id="ring-coverage-sub">Open cases with an active assignment</div>
                </div>
                <div class="dash-card perf-ring-card">
                    <div class="ring-wrap">
                        <svg viewBox="0 0 120 120" class="ring-svg">
                            <circle class="ring-track" cx="60" cy="60" r="52"></circle>
                            <circle class="ring-fill tone-warning" id="ring-sla-fill" cx="60" cy="60" r="52"></circle>
                        </svg>
                        <div class="ring-center">
                            <div class="ring-value" id="ring-sla-value">—</div>
                            <div class="ring-unit">%</div>
                        </div>
                    </div>
                    <div class="ring-label"><i class="bi bi-stopwatch"></i> On-time resolution</div>
                    <div class="ring-sub" id="ring-sla-sub">Resolved within the response target</div>
                </div>
            </div>
        </div>
    @endif

    @if($isAdmin)
        {{-- ===================== ADMIN ANALYTICS ===================== --}}
        <div class="section-head">
            <h5><i class="bi bi-lightning-charge text-primary"></i> Open incidents</h5>
            <p class="section-sub">Cases still open inside Pamplona, same set as the situational map</p>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-7">
                <div class="dash-card analytics-card h-100">
                    <strong class="small text-uppercase text-muted d-block mb-2">Needs a decision</strong>
                    <div id="ops-queue"><div class="empty-note">Loading open cases…</div></div>
                </div>
            </div>
            <div class="col-12 col-lg-5 d-flex flex-column">
                <div class="dash-card analytics-card flex-grow-1 mb-3">
                    <strong class="small text-uppercase text-muted d-block mb-2">Open reports by barangay</strong>
                    <div id="ops-places"><div class="empty-note">Loading places…</div></div>
                </div>
                <div class="dash-card analytics-card">
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
        <div class="section-head">
            <h5><i class="bi bi-clipboard2-pulse text-primary"></i> Incident picture</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <p class="section-sub mb-0" id="dash-period-label">This month</p>
                <div class="btn-group btn-group-sm" role="group" aria-label="Chart period">
                    <button type="button" class="btn btn-primary" data-dash-period="month">Month</button>
                    <button type="button" class="btn btn-outline-primary" data-dash-period="quarter">Quarter</button>
                    <button type="button" class="btn btn-outline-primary" data-dash-period="year">Year</button>
                </div>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-7">
                <div class="dash-card analytics-card h-100">
                    <strong class="small text-uppercase text-muted d-block mb-2">Incident status</strong>
                    <div id="status-meters"></div>
                    <strong class="small text-uppercase text-muted d-block mt-3 mb-2">Priority of open incidents</strong>
                    <div id="priority-meters"></div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="dash-card analytics-card mb-3">
                    <strong class="small text-uppercase text-muted d-block mb-2" id="trend-caption">Reports, last 6 months</strong>
                    <div class="chart-wrap short"><canvas id="chart-trend"></canvas></div>
                </div>
                <div class="dash-card analytics-card mb-3">
                    <strong class="small text-uppercase text-muted d-block mb-2">Repeat areas</strong>
                    <div id="repeat-areas"><div class="empty-note">Loading repeat areas…</div></div>
                </div>
                <div class="dash-card analytics-card">
                    <strong class="small text-uppercase text-muted d-block mb-2">Open incidents by type</strong>
                    <div id="category-meters"></div>
                </div>
            </div>
        </div>
    @else
        {{-- ===================== AGENCY / PERSONNEL: DISPATCHES + FEED ===================== --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-7">
                <div class="dash-card analytics-card h-100">
                    <div class="section-head mb-2">
                        <h5 class="mb-0"><i class="bi bi-truck text-primary"></i> {{ $isAgency ? 'Active dispatches' : 'My assignments' }}</h5>
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
                    <strong class="small text-uppercase text-muted d-block mb-2">Your cases right now</strong>
                    <div id="role-status-meters" class="mb-3"></div>
                    <strong class="small text-uppercase text-muted d-block mb-2"><i class="bi bi-activity text-primary"></i> Recent Status Updates</strong>
                    <div id="status-feed"><div class="empty-note">Loading updates…</div></div>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script src="{{ asset('js/raniag-mapbox.js') }}?v={{ @filemtime(public_path('js/raniag-mapbox.js')) }}"></script>
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
            const CENTER = [{{ config('raniag.map.default_lat') }}, {{ config('raniag.map.default_lng') }}];
            const MAP_CONFIG = @json(config('raniag.map'));
            const REFRESH_MS = 30000;
            const SEEN_KEY = 'raniag.dashboard.seen_incident_id.v2.' + ROLE;
            let alertShownFor = 0;

            let map, streetLayer, satLayer, boundaryLayer, barangayLayer, markerLayer, hazardLayer, evacLayer, jurisdictionBounds;
            let charts = {};
            let currentPoints = [];
            let rawPoints = [];
            let barangayCounts = {};
            let hotspotByBarangay = {};
            let alertPrimed = false;
            let dashPeriod = 'month';

            document.addEventListener('DOMContentLoaded', function () {
                initMap();
                document.querySelectorAll('[data-dash-period]').forEach((button) => {
                    button.addEventListener('click', () => {
                        dashPeriod = button.dataset.dashPeriod || 'month';
                        document.querySelectorAll('[data-dash-period]').forEach((item) => {
                            item.classList.toggle('btn-primary', item === button);
                            item.classList.toggle('btn-outline-primary', item !== button);
                        });
                        loadData({ manual: true });
                    });
                });
                loadData();
                setInterval(loadData, REFRESH_MS);
                bindToolbar();
                bindIncidentAlert();
                bindMapFilters();
            });

            function bindMapFilters() {
                ['filter-status', 'filter-priority', 'filter-barangay'].forEach((id) => {
                    const el = document.getElementById(id);
                    if (el) el.addEventListener('change', applyMapFilters);
                });
                const clearBtn = document.getElementById('filter-clear');
                if (clearBtn) {
                    clearBtn.addEventListener('click', function () {
                        ['filter-status', 'filter-priority', 'filter-barangay'].forEach((id) => {
                            const el = document.getElementById(id);
                            if (el) el.value = '';
                        });
                        applyMapFilters();
                        closeMapInsight();
                    });
                }
                const closeInsight = document.getElementById('map-insight-close');
                if (closeInsight) closeInsight.addEventListener('click', closeMapInsight);

                const brgyFilter = document.getElementById('filter-barangay');
                if (brgyFilter) {
                    brgyFilter.addEventListener('change', function () {
                        if (this.value) showBarangayInsight(this.value);
                        else closeMapInsight();
                    });
                }
            }

            function applyMapFilters() {
                const status = document.getElementById('filter-status')?.value || '';
                const priority = document.getElementById('filter-priority')?.value || '';
                const barangay = document.getElementById('filter-barangay')?.value || '';
                const filtered = rawPoints.filter((p) => {
                    if (status && String(p.status || '') !== status) return false;
                    if (priority && String(p.priority || '').toLowerCase() !== priority) return false;
                    if (barangay && String(p.barangay || '') !== barangay) return false;
                    return true;
                });
                plotPoints(filtered);
            }

            function populateBarangayFilter(counts) {
                const sel = document.getElementById('filter-barangay');
                if (!sel) return;
                const current = sel.value;
                const names = Object.keys(counts || {}).sort((a, b) => a.localeCompare(b));
                sel.innerHTML = '<option value="">All barangays</option>' +
                    names.map((n) => `<option value="${escapeHtml(n)}">${escapeHtml(n)} (${counts[n]})</option>`).join('');
                if (current && names.includes(current)) sel.value = current;
            }

            function showBarangayInsight(name) {
                const panel = document.getElementById('map-insight-panel');
                if (!panel || !name) return;
                const open = barangayCounts[name] ?? rawPoints.filter((p) => p.barangay === name).length;
                const hotspot = hotspotByBarangay[name];
                setText('map-insight-name', name);
                setText('map-insight-open', String(open));
                setText('map-insight-hotspot', hotspot
                    ? `${hotspot.count}× ${hotspot.type}`
                    : 'None flagged');
                const note = document.getElementById('map-insight-note');
                if (note) {
                    note.textContent = hotspot
                        ? `Repeat pattern detected for ${hotspot.type} in this barangay.`
                        : 'Use filters to isolate status or priority for this area.';
                }
                panel.classList.add('is-open');
            }

            function closeMapInsight() {
                document.getElementById('map-insight-panel')?.classList.remove('is-open');
            }

            function bindIncidentAlert() {
                const dismiss = document.getElementById('incident-alert-dismiss');
                if (dismiss) {
                    dismiss.addEventListener('click', function () {
                        const banner = document.getElementById('incident-alert-banner');
                        if (!banner) return;
                        const id = parseInt(banner.dataset.incidentId || '0', 10) || 0;
                        if (id) localStorage.setItem(SEEN_KEY, String(id));
                        banner.classList.remove('is-visible');
                    });
                }
            }

            function playAlertTone() {
                try {
                    const Ctx = window.AudioContext || window.webkitAudioContext;
                    if (!Ctx) return;
                    const ctx = new Ctx();
                    const now = ctx.currentTime;
                    [880, 1174].forEach((freq, i) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.value = freq;
                        gain.gain.setValueAtTime(0.0001, now);
                        gain.gain.exponentialRampToValueAtTime(0.18, now + 0.02 + i * 0.12);
                        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.22 + i * 0.14);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(now + i * 0.14);
                        osc.stop(now + 0.28 + i * 0.14);
                    });
                    setTimeout(() => ctx.close().catch(() => {}), 800);
                } catch (e) { /* autoplay policies may block — banner still shows */ }
            }

            function maybeShowIncidentAlert(latest) {
                if (!latest || !latest.id) return;

                const stored = parseInt(localStorage.getItem(SEEN_KEY) || '0', 10) || 0;
                const latestId = parseInt(latest.id, 10);
                alertPrimed = true;

                // Stay up until the dispatcher dismisses it. A newer id always
                // shows again. Do not mark an incident seen just because the
                // page loaded.
                if (latestId <= stored) return;

                const banner = document.getElementById('incident-alert-banner');
                const copy = document.getElementById('incident-alert-copy');
                const meta = document.getElementById('incident-alert-meta');
                const link = document.getElementById('incident-alert-link');
                if (!banner || !copy || !link) return;

                const typeLabel = latest.type || 'Incident';
                const place = latest.barangay ? ` in ${latest.barangay}` : '';
                copy.textContent = `${typeLabel}${place} · #${latest.tracking_number || latest.id}`;
                if (meta) {
                    const bits = [];
                    if (latest.priority) bits.push(String(latest.priority).replace(/_/g, ' '));
                    if (latest.status) bits.push(String(latest.status).replace(/_/g, ' '));
                    meta.textContent = bits.join(' · ');
                }
                link.href = INCIDENT_URL_BASE + '/' + latestId;
                banner.dataset.incidentId = String(latestId);
                banner.classList.add('is-visible');
                if (alertShownFor !== latestId) {
                    alertShownFor = latestId;
                    playAlertTone();
                }
            }

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

                document.getElementById('map-refresh-btn').addEventListener('click', function () {
                    loadData({ manual: true });
                });

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

                if (window.RANIAG_Mapbox) {
                    const basemap = window.RANIAG_Mapbox.addBasemap(map, MAP_CONFIG);
                    streetLayer = basemap.layer;
                } else {
                    streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '' }).addTo(map);
                }
                satLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, attribution: '' });

                markerLayer = L.layerGroup().addTo(map);
                barangayLayer = L.layerGroup();
                hazardLayer = L.layerGroup().addTo(map);
                evacLayer = L.layerGroup().addTo(map);

                fetch(BOUNDARY_URL)
                    .then(r => r.ok ? r.json() : null)
                    .then(geo => {
                        if (!geo) return;
                        boundaryLayer = L.geoJSON(geo, {
                            style: {
                                color: '#0b3a8c',
                                weight: 3,
                                opacity: 1,
                                fillColor: '#3d8bfd',
                                fillOpacity: 0.12,
                            }
                        }).addTo(map);
                        boundaryLayer.eachLayer((layer) => {
                            layer.bindTooltip('Pamplona area of responsibility', { sticky: true });
                        });
                        try { map.fitBounds(boundaryLayer.getBounds(), { padding: [24, 24] }); jurisdictionBounds = boundaryLayer.getBounds(); } catch (e) {}
                    })
                    .catch(() => {});

                fetch(BARANGAYS_URL)
                    .then(r => r.ok ? r.json() : null)
                    .then(geo => {
                        if (!geo) return;
                        L.geoJSON(geo, {
                            style: {
                                color: '#0f766e',
                                weight: 1.5,
                                opacity: 0.9,
                                fillColor: '#14b8a6',
                                fillOpacity: 0.06,
                                dashArray: '6 4',
                            },
                            onEachFeature: (feature, layer) => {
                                const name = feature.properties && (feature.properties.name || feature.properties.NAME || feature.properties.brgy || feature.properties.adm4_en);
                                if (name) layer.bindTooltip(String(name), { sticky: true, direction: 'center', className: 'brgy-tip' });
                            }
                        }).addTo(barangayLayer);
                        if (document.getElementById('layer-barangays')?.checked) {
                            barangayLayer.addTo(map);
                        }
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

            // Grouping radius in meters. Two reports of "the same spot" almost
            // never come back with identical coordinates — consumer phone GPS
            // is typically accurate to only 5-20m (worse indoors/under cover),
            // so two genuine duplicate reports can easily land 10+ meters
            // apart. The previous version snapped lat/lng to a ~1.1m grid
            // cell (toFixed(5)) and grouped only exact-same-cell points; that
            // was tighter than real GPS jitter, so duplicates almost never
            // shared a cell and kept rendering as separate, visually
            // overlapping pins — indistinguishable from the original
            // "stacked pins" bug even though the clustering code was present.
            // Distance is measured against each group's running centroid
            // (not a fixed grid), so two points don't miss each other by
            // sitting just across a cell boundary either.
            const CLUSTER_RADIUS_METERS = 20;
            let expandedClusterKey = null;

            function metersBetween(lat1, lng1, lat2, lng2) {
                const R = 6371000;
                const toRad = d => d * Math.PI / 180;
                const dLat = toRad(lat2 - lat1);
                const dLng = toRad(lng2 - lng1);
                const a = Math.sin(dLat / 2) ** 2 +
                    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
                return 2 * R * Math.asin(Math.sqrt(a));
            }

            // Builds groups by proximity instead of grid bucketing: each point
            // joins the nearest existing group within CLUSTER_RADIUS_METERS
            // (rolling the group's centroid to the running average), or starts
            // a new group. Keyed by the group's first incident id so the
            // spiderfy/collapse click handlers keep pointing at the right
            // group across re-renders.
            function buildGroups(points) {
                const groups = [];
                points.forEach(function (pt) {
                    if (!pt.latitude || !pt.longitude) return;
                    const lat = parseFloat(pt.latitude), lng = parseFloat(pt.longitude);
                    if (Number.isNaN(lat) || Number.isNaN(lng)) return;

                    let target = null;
                    for (let i = 0; i < groups.length; i++) {
                        if (metersBetween(lat, lng, groups[i].lat, groups[i].lng) <= CLUSTER_RADIUS_METERS) {
                            target = groups[i];
                            break;
                        }
                    }
                    if (target) {
                        const n = target.items.length + 1;
                        target.lat += (lat - target.lat) / n;
                        target.lng += (lng - target.lng) / n;
                        target.items.push(pt);
                    } else {
                        groups.push({ key: 'g' + (pt.id ?? groups.length), lat, lng, items: [pt] });
                    }
                });
                return groups;
            }

            function addIncidentMarker(pt, lat, lng) {
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
                marker.on('click', function () {
                    if (pt.barangay) showBarangayInsight(pt.barangay);
                });
                marker.addTo(markerLayer);
            }

            // Fans a group's pins out in a small ring around their true
            // coordinates (computed in screen pixels so the spacing looks
            // right at any zoom level) instead of leaving them stacked.
            function spiderfyOffset(lat, lng, index, total) {
                const center = map.latLngToLayerPoint([lat, lng]);
                const radius = 24 + Math.min(total, 8) * 4;
                const angle = (2 * Math.PI * index) / total;
                const point = L.point(center.x + radius * Math.cos(angle), center.y + radius * Math.sin(angle));
                const latlng = map.layerPointToLatLng(point);
                return [latlng.lat, latlng.lng];
            }

            function addClusterMarker(group, key) {
                // Highest-priority report in the group drives the badge color,
                // so a cluster hiding a critical incident still reads urgent.
                const rank = { critical: 4, high: 3, medium: 2, low: 1 };
                const top = group.items.reduce((a, b) =>
                    (rank[(b.priority || '').toLowerCase()] || 2) > (rank[(a.priority || '').toLowerCase()] || 2) ? b : a
                );
                const marker = L.marker([group.lat, group.lng], {
                    icon: window.RaniagIcons.buildClusterIcon({ count: group.items.length, priority: top.priority, size: 34 }),
                    zIndexOffset: 1000,
                });
                marker.bindTooltip(`${group.items.length} reports at this location — click to separate`, { direction: 'top' });
                marker.on('click', function () {
                    expandedClusterKey = key;
                    plotPoints(currentPoints);
                });
                marker.addTo(markerLayer);
            }

            function addCollapseHandle(group, key) {
                const handle = L.marker([group.lat, group.lng], {
                    icon: L.divIcon({ html: '<div class="raniag-marker-collapse"></div>', className: 'raniag-marker-marker', iconSize: [10, 10], iconAnchor: [5, 5] }),
                    zIndexOffset: 999,
                });
                handle.bindTooltip('Click to collapse back into a group', { direction: 'top' });
                handle.on('click', function () {
                    if (expandedClusterKey === key) expandedClusterKey = null;
                    plotPoints(currentPoints);
                });
                handle.addTo(markerLayer);
            }

            function plotHazardLayers(data) {
                if (!hazardLayer || !evacLayer) return;
                hazardLayer.clearLayers();
                evacLayer.clearLayers();

                (data.hazard_zones || []).forEach(function (z) {
                    if (!z.geometry) return;
                    const color = z.color || (z.type && z.type.color) || '#b45309';
                    L.geoJSON(z.geometry, {
                        style: { color, weight: 2, fillColor: color, fillOpacity: 0.18 },
                    }).bindPopup('<strong>' + escapeHtml(z.name || 'Hazard') + '</strong>').addTo(hazardLayer);
                });

                (data.evac_centers || []).forEach(function (c) {
                    const lat = parseFloat(c.latitude), lng = parseFloat(c.longitude);
                    if (Number.isNaN(lat) || Number.isNaN(lng)) return;
                    L.circleMarker([lat, lng], {
                        radius: 6,
                        color: '#fff',
                        weight: 2,
                        fillColor: '#0b5ed7',
                        fillOpacity: 1,
                    }).bindPopup('<strong>' + escapeHtml(c.name || 'Evac center') + '</strong>').addTo(evacLayer);
                });
            }

            function plotPoints(points) {
                if (!map) return;
                currentPoints = points;
                markerLayer.clearLayers();

                const groups = buildGroups(points);

                groups.forEach(function (group) {
                    const key = group.key;
                    if (group.items.length === 1) {
                        addIncidentMarker(group.items[0], group.lat, group.lng);
                    } else if (key === expandedClusterKey) {
                        group.items.forEach(function (pt, i) {
                            const [lat, lng] = spiderfyOffset(group.lat, group.lng, i, group.items.length);
                            addIncidentMarker(pt, lat, lng);
                        });
                        addCollapseHandle(group, key);
                    } else {
                        addClusterMarker(group, key);
                    }
                });

                const plotted = points.filter(p => p.latitude && p.longitude && !Number.isNaN(parseFloat(p.latitude)) && !Number.isNaN(parseFloat(p.longitude))).length;
                const label = document.getElementById('map-count-label');
                if (label) label.textContent = `${plotted} point${plotted === 1 ? '' : 's'} plotted`;
            }

            function escapeHtml(str) {
                return (str ?? '').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
            }

            let refreshInFlight = false;

            function loadData(options) {
                const manual = !!(options && options.manual);
                if (manual && refreshInFlight) return;

                const btn = document.getElementById('map-refresh-btn');
                if (manual) {
                    refreshInFlight = true;
                    btn?.classList.add('is-refreshing');
                    btn?.setAttribute('aria-busy', 'true');
                    if (btn) btn.disabled = true;
                }

                const glue = API_URL.includes('?') ? '&' : '?';
                const url = API_URL + glue + '_=' + Date.now()
                    + (IS_ADMIN ? '&period=' + encodeURIComponent(dashPeriod) : '')
                    + (manual ? '&fresh=1' : '');

                fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                    credentials: 'same-origin',
                })
                    .then(r => {
                        if (!r.ok) throw new Error('Dashboard refresh failed (' + r.status + ')');
                        return r.json();
                    })
                    .then(data => {
                        if (IS_ADMIN) renderAdmin(data);
                        else renderRole(data);
                        if (map) map.invalidateSize();
                        if (manual) markMapRefreshed();
                    })
                    .catch(err => {
                        console.error('Dashboard load error:', err);
                        if (manual) {
                            const label = document.getElementById('map-count-label');
                            if (label) label.textContent = 'Could not refresh the map. Try again.';
                        }
                    })
                    .finally(() => {
                        if (!manual) return;
                        refreshInFlight = false;
                        btn?.classList.remove('is-refreshing');
                        btn?.removeAttribute('aria-busy');
                        if (btn) btn.disabled = false;
                    });
            }

            function renderReferred(rows) {
                const box = document.getElementById('map-referred');
                const list = document.getElementById('map-referred-list');
                if (!box || !list) return;
                const items = Array.isArray(rows) ? rows : [];
                if (!items.length) {
                    box.classList.add('d-none');
                    list.innerHTML = '';
                    return;
                }
                box.classList.remove('d-none');
                list.innerHTML = items.map((row) => {
                    const href = INCIDENT_URL_BASE + '/' + row.id;
                    const place = row.place || 'Outside Pamplona';
                    const type = row.type ? escapeHtml(row.type) + ' · ' : '';
                    return `<a href="${href}">
                        <span><span class="ref-track">${escapeHtml(row.tracking_number || ('#' + row.id))}</span><span class="d-block text-muted">${type}${escapeHtml(place)}</span></span>
                        <span class="text-muted small">${escapeHtml(row.reported_at || '')}</span>
                    </a>`;
                }).join('');
            }

            function markMapRefreshed() {
                const label = document.getElementById('map-count-label');
                if (!label) return;
                const base = label.textContent.replace(/ · updated.*/, '');
                label.textContent = base + ' · updated just now';
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
                const picture = data.period || {};
                setTrend('kpi-reports-trend', picture.reports_change, picture.label);
                setTrend('kpi-resolved-trend', picture.resolved_change, picture.label);
                setText('dash-period-label', picture.label || '');
                setText('trend-caption', picture.caption ? ('Reports, ' + picture.caption.toLowerCase()) : 'Reports');
                renderRepeatAreas(picture);

                const analytics = data.analytics || {};
                setText('kpi-avg-resolution', `Avg. resolution ${analytics.avg_resolution_hours ?? 0}h`);

                barangayCounts = analytics.barangays || {};
                hotspotByBarangay = {};
                (analytics.redundancy_hotspots || []).forEach((h) => {
                    if (!h?.barangay) return;
                    // Keep the highest-count hotspot per barangay for the insight panel.
                    if (!hotspotByBarangay[h.barangay] || hotspotByBarangay[h.barangay].count < h.count) {
                        hotspotByBarangay[h.barangay] = { type: h.type, count: h.count };
                    }
                });
                populateBarangayFilter(barangayCounts);

                rawPoints = data.recent_incidents || [];
                renderReferred(data.referred_outside);
                applyMapFilters();
                plotHazardLayers(data);
                maybeShowIncidentAlert(data.latest_incident);

                const sms = data.sms_stats || {};
                setText('sms-sent', sms.sent ?? 0);
                setText('sms-failed', sms.failed ?? 0);
                setText('sms-pending', sms.pending ?? 0);
                setText('out-of-jurisdiction', analytics.out_of_jurisdiction_count ?? 0);

                renderOpsBoard(rawPoints);
                renderTrendChart((data.period && data.period.trend) || analytics.weekly_trends || []);
                const statusOrder = ['submitted', 'in_progress', 'resolved', 'closed'];
                const statusLabels = { submitted: 'Submitted', in_progress: 'In progress', resolved: 'Resolved', closed: 'Closed' };
                renderMeters('status-meters', statusOrder.map((key) => ({
                    label: statusLabels[key],
                    value: Number(sb[key]) || 0,
                    color: '#0b5ed7',
                })));
                const priorityColors = { critical: '#b91c1c', high: '#dc3545', medium: '#f59e0b', low: '#0d6efd' };
                const priorityOrder = ['critical', 'high', 'medium', 'low'];
                const priorityCounts = {};
                const typeCounts = {};
                (rawPoints || []).forEach((point) => {
                    const priority = String(point.priority || 'medium').toLowerCase();
                    priorityCounts[priority] = (priorityCounts[priority] || 0) + 1;
                    const typeName = (point.incident_type && point.incident_type.name) || (point.incidentType && point.incidentType.name) || 'Incident';
                    typeCounts[typeName] = (typeCounts[typeName] || 0) + 1;
                });
                renderMeters('priority-meters', priorityOrder.filter((key) => priorityCounts[key]).map((key) => ({
                    label: key,
                    value: priorityCounts[key],
                    color: priorityColors[key] || '#64748b',
                })));
                renderMeters('category-meters', Object.entries(typeCounts)
                    .sort((a, b) => b[1] - a[1])
                    .map(([label, value]) => ({ label, value, color: '#0f766e' })));

                const perf = data.performance || {};
                setRing('ring-resolution-fill', 'ring-resolution-value', perf.resolution_rate);
                setText('ring-resolution-sub', `${perf.resolved_closed_count ?? 0} of ${data.total_incidents ?? 0} cases resolved`);

                setRing('ring-coverage-fill', 'ring-coverage-value', perf.assignment_coverage_rate);
                setText('ring-coverage-sub', `${data.active_assignments ?? 0} of ${perf.open_incidents_count ?? 0} open cases dispatched`);

                setRing('ring-sla-fill', 'ring-sla-value', perf.sla_compliance);
                setText('ring-sla-sub', perf.avg_resolution_hours
                    ? `Avg ${perf.avg_resolution_hours}h vs ${perf.sla_target_hours ?? 48}h target`
                    : 'No completed cases yet');
            }

            function setText(id, val) {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            }

            function setTrend(id, change, label) {
                const el = document.getElementById(id);
                if (!el) return;
                if (!change) {
                    el.textContent = '';
                    return;
                }
                const direction = change.direction || 'flat';
                const icon = direction === 'up' ? 'bi-arrow-up-short' : (direction === 'down' ? 'bi-arrow-down-short' : 'bi-dash');
                el.className = 'kpi-trend ' + direction;
                el.innerHTML = `<i class="bi ${icon}"></i>${Math.abs(change.percent)}% vs previous ${label || 'period'}`;
            }

            function renderRepeatAreas(picture) {
                const el = document.getElementById('repeat-areas');
                if (!el) return;
                const most = picture.most_active;
                const rows = picture.repeat_areas || [];
                const lead = most
                    ? `<div class="hotspot-row"><span><strong>${escapeHtml(most.type)}</strong> in ${escapeHtml(most.barangay)}</span><span class="risk-pill ${escapeHtml(String(most.band || '').toLowerCase())}">${escapeHtml(most.band || '')} · ${most.count}</span></div>`
                    : '<div class="empty-note">No reports in this period.</div>';
                const list = rows.length
                    ? rows.map((row) => `<div class="hotspot-row"><span>${escapeHtml(row.barangay)} · ${escapeHtml(row.type)}</span><span class="risk-pill ${escapeHtml(String(row.band || '').toLowerCase())}">${row.count}×</span></div>`).join('')
                    : '<div class="empty-note">No barangay has the same incident more than once in this period.</div>';
                el.innerHTML = lead + list;
            }

            // r=52 circle circumference, matches the CSS stroke-dasharray above
            const RING_CIRCUMFERENCE = 2 * Math.PI * 52;
            function setRing(fillId, valueId, percent) {
                const fill = document.getElementById(fillId);
                const label = document.getElementById(valueId);
                const hasValue = percent !== null && percent !== undefined && !Number.isNaN(percent);
                const pct = hasValue ? Math.max(0, Math.min(100, percent)) : 0;
                if (fill) fill.style.strokeDashoffset = RING_CIRCUMFERENCE * (1 - pct / 100);
                if (label) label.textContent = hasValue ? Math.round(pct) : '—';
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
                            label: 'Reports',
                            data: rows.map(r => r.count),
                            borderColor: '#0b5ed7',
                            backgroundColor: 'rgba(11,94,215,0.08)',
                            fill: true, tension: 0.35, pointRadius: 3, pointBackgroundColor: '#0b5ed7'
                        }, {
                            label: 'Completed',
                            data: rows.map(r => r.resolved || 0),
                            borderColor: '#0f766e',
                            tension: 0.35, pointRadius: 3
                        }]
                    },
                    options: { ...baseOpts({ legend: true }), animation: { duration: 1100, easing: 'easeOutQuart' } }
                });
            }

            function renderStatusChart(sb) {
                const labels = Object.keys(sb);
                ensureChart('status', 'chart-status', {
                    type: 'doughnut',
                    data: { labels: labels.map(l => l.replace(/_/g, ' ')), datasets: [{ data: labels.map(l => sb[l]), backgroundColor: PALETTE, borderWidth: 2, borderColor: '#fff' }] },
                    options: { responsive: true, maintainAspectRatio: false, animation: { duration: 1100, easing: 'easeOutQuart', animateRotate: true }, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } } }
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

            const PRIORITY_COLORS = { critical: '#b91c1c', high: '#dc3545', medium: '#f59e0b', low: '#0d6efd' };
            function renderPriorityChart(priority) {
                const labels = Object.keys(priority);
                ensureChart('priority', 'chart-priority', {
                    type: 'doughnut',
                    data: {
                        labels: labels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
                        datasets: [{ data: labels.map(l => priority[l]), backgroundColor: labels.map(l => PRIORITY_COLORS[l] || '#94a3b8'), borderWidth: 2, borderColor: '#fff' }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '62%', animation: { duration: 1100, easing: 'easeOutQuart', animateRotate: true }, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } } }
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

            function renderMeters(id, rows) {
                const el = document.getElementById(id);
                if (!el) return;
                const items = (rows || []).filter((row) => row && row.label);
                if (!items.length) {
                    el.innerHTML = '<div class="empty-note">Nothing to show yet.</div>';
                    return;
                }
                const max = Math.max(1, ...items.map((row) => row.value));
                el.innerHTML = items.map((row) => {
                    const width = Math.round((row.value / max) * 100);
                    return `<div class="pulse-row"><span class="text-capitalize">${escapeHtml(row.label)}</span><span class="pulse-track"><span class="pulse-fill" style="width:${width}%;background:${row.color}"></span></span><strong>${row.value}</strong></div>`;
                }).join('');
            }

            function renderOpsBoard(points) {
                const queue = document.getElementById('ops-queue');
                const places = document.getElementById('ops-places');
                if (queue) {
                    const rank = { critical: 0, high: 1, medium: 2, low: 3 };
                    const rows = (points || []).slice().sort((a, b) => {
                        const pa = rank[(a.priority || '').toLowerCase()] ?? 4;
                        const pb = rank[(b.priority || '').toLowerCase()] ?? 4;
                        if (pa !== pb) return pa - pb;
                        return String(b.reported_at || '').localeCompare(String(a.reported_at || ''));
                    }).slice(0, 8);
                    queue.innerHTML = rows.length ? rows.map((r) => {
                        const typeName = (r.incident_type && r.incident_type.name) || (r.incidentType && r.incidentType.name) || 'Incident';
                        const priority = (r.priority || 'medium').toLowerCase();
                        const href = INCIDENT_URL_BASE + '/' + r.id;
                        return `<a class="ops-row" href="${href}">
                            <span class="ops-track">${escapeHtml(r.tracking_number || ('#' + r.id))}</span>
                            <span><span class="d-block fw-semibold">${escapeHtml(typeName)}</span><span class="ops-meta">${escapeHtml(r.barangay || '—')} · ${escapeHtml((r.status || '').replaceAll('_', ' '))}</span></span>
                            <span class="ops-age"><span class="badge badge-priority-${priority} text-capitalize">${escapeHtml(priority)}</span></span>
                        </a>`;
                    }).join('') : '<div class="empty-note">No open incidents inside Pamplona.</div>';
                }
                if (places) {
                    const counts = {};
                    (points || []).forEach((point) => {
                        const name = point.barangay || '';
                        if (!name) return;
                        counts[name] = (counts[name] || 0) + 1;
                    });
                    const entries = Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 6);
                    places.innerHTML = entries.length ? entries.map(([name, count]) =>
                        `<div class="hotspot-row"><span>${escapeHtml(name)}</span><span class="hotspot-count">${count} open</span></div>`
                    ).join('') : '<div class="empty-note">No open reports in Pamplona barangays.</div>';
                }
            }

            function baseOpts(opts) {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 1100, easing: 'easeOutQuart' },
                    indexAxis: opts.indexAxis || 'x',
                    plugins: { legend: { display: !!opts.legend, position: 'bottom' } },
                    scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f4f8' } } }
                };
            }

            // ---------------- Agency / Personnel rendering ----------------
            function renderRole(data) {
                const sb = data.incident_status_breakdown || {};
                setText('kpi-assigned', data.total_assigned_incidents ?? 0);
                setText('kpi-pending', data.pending_resolutions ?? 0);
                setText('kpi-progress-count', sb.in_progress ?? 0);
                setText('kpi-sms', data.sms_alerts_this_week ?? 0);

                plotPoints(data.active_dispatches || []);
                renderReferred(data.referred_outside);
                renderDispatchTable(data.active_dispatches || []);
                renderMeters('role-status-meters', Object.entries(sb).map(([label, value]) => ({
                    label: String(label).replaceAll('_', ' '),
                    value: Number(value) || 0,
                    color: '#0b5ed7',
                })));
                renderStatusFeed(data.recent_status_updates || []);
                maybeShowIncidentAlert(data.latest_incident);
            }

            function renderDispatchTable(rows) {
                const body = document.getElementById('dispatch-table-body');
                if (!body) return;
                if (!rows.length) { body.innerHTML = '<tr><td colspan="5" class="empty-note">No active dispatches right now.</td></tr>'; return; }
                body.innerHTML = rows.map(r => {
                    const typeName = r.incident_type && r.incident_type.name ? r.incident_type.name : '—';
                    const priority = (r.priority || 'medium').toLowerCase();
                    const href = INCIDENT_URL_BASE + '/' + r.id;
                    return `
                        <tr style="cursor:pointer" onclick="window.location='${href}'">
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
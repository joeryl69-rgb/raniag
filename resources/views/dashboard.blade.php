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

    <x-workspace-intro
        eyebrow="{{ $isAdmin ? 'COMMAND / SITUATIONAL AWARENESS' : ($isAgency ? 'AGENCY / RESPONSE OPERATIONS' : 'FIELD / RESPONSE OPERATIONS') }}"
        title="{{ $isAdmin ? 'Command dashboard' : ($isAgency ? 'Agency operations' : 'Field dashboard') }}"
        description="{{ $isAdmin ? 'A live overview of incidents, hazards, and response activity across Pamplona.' : ($isAgency ? 'Your active dispatches and the current operating picture for your agency.' : 'Your assigned cases and the latest field response picture.') }}"
        icon="{{ $isAdmin ? 'bi-speedometer2' : ($isAgency ? 'bi-building-gear' : 'bi-person-workspace') }}"
        variant="dashboard"
    />

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <style>
            :root {
                --raniag-danger: #dc3545;
                --raniag-warning: #f59e0b;
                --raniag-success: #16a34a;
            }

            .dash-card {
                border: 1px solid var(--raniag-border);
                border-radius: 1rem;
                background: var(--raniag-card);
                box-shadow: 0 14px 30px -22px rgba(15, 23, 42, 0.45);
                transition: box-shadow .18s ease, transform .18s ease;
            }
            .map-card .map-toolbar {
                background: var(--raniag-card);
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
            .kpi-card:hover { background: var(--raniag-card-raised); color: inherit; }
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

            /* ---- Live incident notification toast ---- */
            .incident-alert-banner {
                display: none;
                position: fixed;
                z-index: 1100;
                top: max(5.25rem, env(safe-area-inset-top, 0px));
                right: 1.25rem;
                width: min(470px, calc(100vw - 2rem));
                align-items: stretch;
                gap: .8rem;
                padding: 1rem;
                overflow: hidden;
                border: 1px solid rgba(255, 107, 114, .65);
                border-radius: 1rem;
                background:
                    radial-gradient(ellipse at 100% 0%, rgba(220, 38, 48, .24), transparent 45%),
                    linear-gradient(135deg, #111a2b 0%, #172238 60%, #241c2c 100%);
                color: #fff;
                box-shadow: 0 22px 62px -18px rgba(14, 23, 39, .75), 0 0 0 1px rgba(255, 255, 255, .08) inset;
                isolation: isolate;
            }
            .incident-alert-banner::before {
                content: "";
                position: absolute;
                z-index: -1;
                top: 0;
                bottom: 0;
                left: 0;
                width: 5px;
                background: linear-gradient(180deg, #ff7777, #ef233c 52%, #a80f2d);
                box-shadow: 0 0 18px rgba(239, 35, 60, .9);
            }
            .incident-alert-banner::after {
                content: "";
                position: absolute;
                z-index: -1;
                inset: 0 auto 0 -55%;
                width: 38%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .1), transparent);
                transform: skewX(-20deg);
            }
            .incident-alert-banner.is-visible { display: flex; animation: incident-toast-enter .42s cubic-bezier(.16, 1, .3, 1) both; }
            .incident-alert-banner.is-visible::after { animation: incident-toast-sweep 1.15s .15s ease-out both; }
            .incident-alert-banner.is-alarming { animation: incident-toast-enter .42s cubic-bezier(.16, 1, .3, 1) both, incident-toast-glow 2.2s .45s ease-in-out 2; }
            .incident-alert-banner .alert-pulse-dot {
                position: relative;
                display: grid;
                width: 3.5rem;
                height: 3.5rem;
                flex: 0 0 3.5rem;
                place-items: center;
                align-self: center;
                border: 1px solid rgba(255, 143, 143, .65);
                border-radius: 1rem;
                background: linear-gradient(145deg, #ff5662, #c91835);
                color: #fff;
                font-size: 1.45rem;
                box-shadow: 0 8px 24px rgba(220, 38, 48, .36), inset 0 1px 0 rgba(255,255,255,.28);
            }
            .incident-alert-banner.is-alarming .alert-pulse-dot::after {
                content: "";
                position: absolute;
                inset: -6px;
                border: 1px solid rgba(255, 98, 108, .65);
                border-radius: 1.15rem;
                animation: incident-alert-ring 1.5s ease-out 2;
            }
            .incident-alert-banner .alert-body { flex: 1; min-width: 0; align-self: center; }
            .incident-alert-banner .alert-kicker {
                display: flex;
                align-items: center;
                gap: .4rem;
                margin-bottom: .35rem;
                color: #ff949b;
                font-size: .64rem;
                font-weight: 900;
                letter-spacing: .14em;
                text-transform: uppercase;
            }
            .incident-alert-banner .alert-kicker-dot {
                width: .45rem;
                height: .45rem;
                border-radius: 50%;
                background: #ff555f;
                box-shadow: 0 0 0 3px rgba(255, 85, 95, .16), 0 0 12px rgba(255, 85, 95, .8);
            }
            .incident-alert-banner .alert-title {
                font-size: .98rem;
                font-weight: 850;
                letter-spacing: -.015em;
                color: #fff;
                margin-bottom: .2rem;
            }
            .incident-alert-banner .alert-copy {
                color: #f4f6fb;
                font-size: .86rem;
                font-weight: 750;
                line-height: 1.35;
                overflow-wrap: anywhere;
            }
            .incident-alert-banner .alert-meta { margin-top: .25rem; color: #b7c3d3; font-size: .73rem; text-transform: capitalize; }
            .incident-alert-banner .alert-backlog {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            margin-top: .5rem;
            padding: .2rem .48rem;
            border: 1px solid rgba(255, 157, 165, .35);
            border-radius: 999px;
            background: rgba(220, 38, 48, .17);
            color: #ffb9bd;
            font-size: .67rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            }
            .incident-alert-banner .alert-read-error {
            margin-top: .4rem;
            color: #ffc2a8;
            font-size: .72rem;
            font-weight: 700;
            }
            .incident-alert-banner .alert-actions { display: flex; align-items: center; gap: .4rem; }
            .incident-alert-banner .alert-open {
                border: 1px solid #ff7a82;
                border-radius: .65rem;
                background: linear-gradient(135deg, #f04452, #cb1e38);
                color: #fff;
                font-weight: 800;
                white-space: nowrap;
                box-shadow: 0 5px 16px rgba(220, 38, 48, .25);
            }
            .incident-alert-banner .alert-open:hover { border-color: #ffabb0; background: #e72f43; color: #fff; transform: translateY(-1px); }
            .incident-alert-banner .alert-dismiss {
                display: grid;
                width: 2.25rem;
                height: 2.25rem;
                flex: 0 0 2.25rem;
                place-items: center;
                border: 1px solid rgba(215, 226, 242, .28);
                border-radius: .65rem;
                background: rgba(255, 255, 255, .07);
                color: #dbe6f5;
            }
            .incident-alert-banner .alert-dismiss:hover { background: rgba(255, 255, 255, .17); color: #fff; }
            @keyframes incident-toast-enter {
                0% { opacity: 0; transform: translate3d(1.4rem, -1rem, 0) scale(.94); }
                65% { opacity: 1; transform: translate3d(-.25rem, 0, 0) scale(1.01); }
                100% { opacity: 1; transform: translate3d(0, 0, 0) scale(1); }
            }
            @keyframes incident-toast-sweep {
                from { left: -55%; }
                to { left: 130%; }
            }
            @keyframes incident-toast-glow {
                0%, 100% { box-shadow: 0 22px 62px -18px rgba(14, 23, 39, .75), 0 0 0 1px rgba(255, 255, 255, .08) inset; }
                50% { box-shadow: 0 22px 62px -18px rgba(14, 23, 39, .75), 0 0 24px rgba(239, 35, 60, .38), 0 0 0 1px rgba(255, 255, 255, .08) inset; }
            }
            @keyframes incident-alert-ring {
                0% { opacity: .8; transform: scale(.92); }
                100% { opacity: 0; transform: scale(1.45); }
            }
            @media (max-width: 575.98px) {
                .incident-alert-banner {
                    top: max(4.75rem, env(safe-area-inset-top, 0px));
                    right: .65rem;
                    width: calc(100vw - 1.3rem);
                    gap: .55rem;
                    padding: .8rem .7rem .8rem .85rem;
                }
                .incident-alert-banner .alert-pulse-dot { width: 2.75rem; height: 2.75rem; flex-basis: 2.75rem; font-size: 1.2rem; }
                .incident-alert-banner .alert-actions { gap: .25rem; }
                .incident-alert-banner .alert-open { padding-inline: .55rem; font-size: .76rem; }
                .incident-alert-banner .alert-dismiss { width: 2rem; height: 2rem; flex-basis: 2rem; }
            }
            @media (prefers-reduced-motion: reduce) {
                .incident-alert-banner.is-visible,
                .incident-alert-banner.is-alarming,
                .incident-alert-banner.is-visible::after,
                .incident-alert-banner.is-alarming .alert-pulse-dot::after { animation: none; }
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
            .map-legend .legend-outside { width: 11px; height: 11px; border-radius: 50%; border: 2px dashed #c2410c; display: inline-block; margin-right: .3rem; }
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
            #dashboard-map.h-admin { height: clamp(520px, 64vh, 760px); }
            #dashboard-map.h-role { height: 360px; }
            @media (max-width: 767.98px) { #dashboard-map.h-admin, #dashboard-map.h-role { height: 300px; } }

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
                display: flex; flex-wrap: nowrap; gap: .45rem; align-items: center;
                overflow-x: auto; overscroll-behavior-x: contain;
                padding: .45rem .85rem; border-bottom: 1px solid var(--raniag-border);
                background: var(--raniag-card);
            }
            .map-filters > span,
            .map-filters > button { flex: 0 0 auto; white-space: nowrap; }
            .map-filters select {
                min-height: 2.15rem; font-size: .74rem; font-weight: 600; border-radius: .5rem;
                border: 1px solid var(--raniag-border); padding: .28rem .55rem;
                background: var(--raniag-card); color: var(--raniag-ink); max-width: 9.5rem;
            }
            .map-filters > .rg-select-control { width: 7rem; flex: 0 0 7rem; }
            .map-filters > .rg-select-control .rg-select-trigger {
                min-height: 2.15rem; padding: .28rem .55rem; border-radius: .5rem;
                font-size: .74rem; font-weight: 600;
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
                background: var(--raniag-card, #121a2c);
                color: #e2e8f0;
            }
            [data-theme="dark"] .map-filters,
            [data-theme="dark"] .map-filters select,
            [data-theme="dark"] .map-toolbar {
                background: var(--raniag-card, #121a2c);
                color: var(--raniag-ink, #e8eef6);
            }
            [data-theme="dark"] .kpi-card {
                background: var(--raniag-card, #121a2c);
                color: var(--raniag-ink, #e8eef6);
            }
            [data-theme="dark"] .kpi-card:hover {
                background: var(--raniag-card-raised, #182338);
                color: var(--raniag-ink, #e8eef6);
            }
            [data-theme="dark"] .map-filters select { border-color: #334155; }
        </style>
    @endpush

    {{-- Live notifications are loaded from each user's authorized backend feed. --}}
    <div id="incident-alert-banner" class="incident-alert-banner" role="alert" aria-live="assertive" aria-atomic="true">
        <span class="alert-pulse-dot" aria-hidden="true"><i class="bi bi-exclamation-lg"></i></span>
        <div class="alert-body">
            <div class="alert-kicker"><span class="alert-kicker-dot"></span>Live command alert</div>
            <div class="alert-title" id="incident-alert-title">New incident reported</div>
            <div class="alert-copy" id="incident-alert-copy">—</div>
            <div class="alert-meta" id="incident-alert-meta"></div>
            <div class="alert-backlog" id="incident-alert-backlog" aria-live="polite"></div>
            <div class="alert-read-error d-none" id="incident-alert-read-error" role="status"></div>
        </div>
        <div class="alert-actions">
            <a href="#" id="incident-alert-link" class="btn btn-sm alert-open">Open</a>
            <button type="button" class="alert-dismiss" id="incident-alert-dismiss" aria-label="Dismiss alert">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
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
                <div class="map-filters" id="map-filters">
                    <span class="small text-muted fw-semibold text-uppercase" style="letter-spacing:.04em;font-size:.68rem;">Filters</span>
                    @if($isAdmin)
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
                    @endif
                    <select id="filter-barangay" aria-label="Filter by barangay">
                        <option value="">All barangays</option>
                        <option value="outside">All outside Pamplona</option>
                        <option value="inside">All inside Pamplona</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="filter-clear">Clear</button>
                </div>
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
                            <input class="form-check-input" type="radio" name="basemap" id="base-street">
                            <label class="form-check-label" for="base-street">Street</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="basemap" id="base-satellite" checked>
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
                    <span><i class="legend-outside"></i>Outside Pamplona</span>
                    <span class="ms-auto small" id="map-count-label">— points plotted</span>
                </div>
                <div class="map-referred d-none" id="map-referred">
                    <div class="map-referred-head">
                        <i class="bi bi-signpost-split"></i>
                        <div>
                            <strong>Referred outside Pamplona</strong>
                            <span>Open reports with a location are pinned on the map. This list is for referrals that are no longer an open case.</span>
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
            <p class="section-sub">Open cases on the situational map, including reports outside Pamplona</p>
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
                    <button type="button" class="btn btn-outline-primary" data-dash-period="week">Week</button>
                    <button type="button" class="btn btn-primary" data-dash-period="month">Month</button>
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
            const IS_ADMIN = @json($isAdmin);
            const API_URL = "{{ route($routePrefix . '.dashboard.api') }}";
            const ALERTS_URL = "{{ route($routePrefix . '.dashboard.alerts') }}";
            const ALERT_READ_URL_TEMPLATE = "{{ route('notifications.mark_read', ['notification' => '__ID__']) }}";
            const BOUNDARY_URL = "{{ route($routePrefix . '.dashboard.boundary') }}";
            const BARANGAYS_URL = "{{ route($routePrefix . '.dashboard.barangays') }}";
            const INCIDENT_URL_BASE = "{{ route($routePrefix . '.incidents.show', ['incident' => '__ID__']) }}".replace('/__ID__', '');
            const CENTER = [{{ config('raniag.map.default_lat') }}, {{ config('raniag.map.default_lng') }}];
            const MAP_CONFIG = @json(config('raniag.map'));
            const REFRESH_MS = 30000;
            let alertCursor = Number(@json($alertCursor ?? 0)) || 0;

            let map, streetLayer, satLayer, mapBasemap, boundaryLayer, barangayLayer, markerLayer, hazardLayer, evacLayer, jurisdictionBounds;
            let mapFrameSet = false;
            let charts = {};
            let currentPoints = [];
            let rawPoints = [];
            let barangayCounts = {};
            let hotspotByBarangay = {};
            const alertQueue = [];
            const queuedAlertIds = new Set();
            let currentIncidentAlert = null;
            let pendingAlertCount = 0;
            let alertReminderTimer = 0;
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
                loadAlertFeed();
                setInterval(loadData, REFRESH_MS);
                setInterval(loadAlertFeed, 10000);
                bindToolbar();
                bindIncidentAlert();
                bindMapFilters();
            });

            function bindMapFilters() {
                const brgyFilter = document.getElementById('filter-barangay');
                if (brgyFilter) {
                    brgyFilter.addEventListener('change', function () {
                        mapFrameSet = false;
                        if (!this.value) closeMapInsight();
                    });
                }
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
                        mapFrameSet = false;
                        applyMapFilters();
                        closeMapInsight();
                    });
                }
                const closeInsight = document.getElementById('map-insight-close');
                if (closeInsight) closeInsight.addEventListener('click', closeMapInsight);
            }

            function frameMapToPins(points, lock) {
                if (!map || mapFrameSet) return;
                let bounds = jurisdictionBounds
                    ? L.latLngBounds(jurisdictionBounds.getSouthWest(), jurisdictionBounds.getNorthEast())
                    : null;
                const scope = document.getElementById('filter-barangay')?.value || '';
                const center = bounds ? bounds.getCenter() : L.latLng(CENTER[0], CENTER[1]);
                if (scope !== 'inside') {
                    (points || []).forEach((point) => {
                        if (pointIsInsidePamplona(point)) return;
                        const lat = parseFloat(point.latitude);
                        const lng = parseFloat(point.longitude);
                        if (Number.isNaN(lat) || Number.isNaN(lng)) return;
                        if (metersBetween(center.lat, center.lng, lat, lng) > 25000) return;
                        if (!bounds) bounds = L.latLngBounds([lat, lng], [lat, lng]);
                        else bounds.extend([lat, lng]);
                    });
                }
                if (!bounds) return;
                map.fitBounds(bounds, { padding: [36, 36], maxZoom: 13 });
                if (lock && jurisdictionBounds) mapFrameSet = true;
            }

            const officialBarangays = @json(config('raniag.barangays', []));

            function pointIsInsidePamplona(point) {
                const name = String(point?.barangay || '');
                if (!officialBarangays.includes(name)) return false;
                if (String(point?.status || '') === 'outside_aor') return false;
                if (point?.within_jurisdiction === false) return false;
                return true;
            }

            function applyMapFilters() {
                const status = document.getElementById('filter-status')?.value || '';
                const priority = document.getElementById('filter-priority')?.value || '';
                const barangay = document.getElementById('filter-barangay')?.value || '';
                const filtered = rawPoints.filter((p) => {
                    if (status && String(p.status || '') !== status) return false;
                    if (priority && String(p.priority || '').toLowerCase() !== priority) return false;
                    if (barangay === 'inside' && !pointIsInsidePamplona(p)) return false;
                    if (barangay === 'outside' && pointIsInsidePamplona(p)) return false;
                    return true;
                });
                plotPoints(filtered);
            }

            function populateBarangayFilter() {
                const sel = document.getElementById('filter-barangay');
                if (!sel) return;
                const current = sel.value;
                if ([...sel.options].some((option) => option.value === current)) sel.value = current;
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

            function prepareAlertAudio() {
                return window.RANIAGAlertSound?.unlock() || Promise.resolve(false);
            }

            function bindIncidentAlert() {
                const banner = document.getElementById('incident-alert-banner');
                const dismiss = document.getElementById('incident-alert-dismiss');
                const open = document.getElementById('incident-alert-link');
                dismiss?.addEventListener('click', function () {
                    acknowledgeIncidentAlert(false);
                });
                open?.addEventListener('click', function (event) {
                    if (!currentIncidentAlert) return;
                    event.preventDefault();
                    acknowledgeIncidentAlert(true);
                });
                if (!banner) return;
                const unlockAudio = () => {
                    prepareAlertAudio().then((ready) => {
                        if (ready && currentIncidentAlert) playAlertTone();
                    });
                };
                document.addEventListener('pointerdown', unlockAudio, { passive: true });
                document.addEventListener('keydown', unlockAudio);
            }

            function playAlertTone() {
                if (document.hidden) return;
                window.RANIAGAlertSound?.play();
            }

            function updateAlertBacklog() {
                const backlog = document.getElementById('incident-alert-backlog');
                const title = document.getElementById('incident-alert-title');
                if (!backlog || !title || !currentIncidentAlert) return;

                const waiting = Math.max(alertQueue.length, pendingAlertCount - 1);
                const count = Math.max(1, pendingAlertCount);
                title.textContent = count > 1
                    ? `${count} incident alerts need attention`
                    : (currentIncidentAlert.type === 'incident.assigned' ? 'New dispatch assigned' : 'New incident reported');
                backlog.textContent = waiting > 0
                    ? `${waiting} more alert${waiting === 1 ? '' : 's'} waiting`
                    : 'Unacknowledged · stays here until opened or dismissed';
            }

            function restartAlertAnimation() {
                const banner = document.getElementById('incident-alert-banner');
                if (!banner) return;
                banner.classList.remove('is-alarming');
                void banner.offsetWidth;
                banner.classList.add('is-alarming');
            }

            function remindIncidentAlert() {
                if (!currentIncidentAlert || document.hidden) return;
                restartAlertAnimation();
                playAlertTone();
            }

            function startAlertReminders() {
                clearInterval(alertReminderTimer);
                if (!currentIncidentAlert) return;
                alertReminderTimer = window.setInterval(remindIncidentAlert, 30000);
            }

            function showNextIncidentAlert() {
                const banner = document.getElementById('incident-alert-banner');
                if (!banner || currentIncidentAlert || !alertQueue.length) return;
                const alert = alertQueue.shift();
                const copy = document.getElementById('incident-alert-copy');
                const meta = document.getElementById('incident-alert-meta');
                const link = document.getElementById('incident-alert-link');
                const title = document.getElementById('incident-alert-title');
                if (!copy || !link || !title) return;

                const incidentId = Number(alert.incident_id);
                if (!incidentId) return showNextIncidentAlert();
                currentIncidentAlert = alert;
                const typeLabel = alert.incident_type || 'Incident';
                const place = alert.barangay ? ` · ${alert.barangay}` : '';
                copy.textContent = `${typeLabel}${place} · #${alert.tracking_number || incidentId}`;
                if (meta) {
                    const bits = [];
                    if (alert.priority) bits.push(String(alert.priority).replace(/_/g, ' '));
                    if (alert.status) bits.push(String(alert.status).replace(/_/g, ' '));
                    meta.textContent = bits.join(' · ');
                }
                link.href = alert.target_url || INCIDENT_URL_BASE + '/' + incidentId;
                banner.dataset.notificationId = String(alert.id);
                document.getElementById('incident-alert-read-error')?.classList.add('d-none');
                updateAlertBacklog();
                banner.classList.add('is-visible');
                restartAlertAnimation();
                playAlertTone();
                startAlertReminders();
            }

            function acknowledgeIncidentAlert(openIncident) {
                if (!currentIncidentAlert) return;
                const notificationId = Number(currentIncidentAlert.id);
                const readUrl = ALERT_READ_URL_TEMPLATE.replace('__ID__', encodeURIComponent(notificationId));
                const dismiss = document.getElementById('incident-alert-dismiss');
                const open = document.getElementById('incident-alert-link');
                const errorEl = document.getElementById('incident-alert-read-error');
                [dismiss, open].forEach((button) => {
                    if (button) button.setAttribute('aria-disabled', 'true');
                });
                fetch(readUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    credentials: 'same-origin',
                })
                    .then((response) => {
                        if (!response.ok) throw new Error('Alert acknowledgement failed (' + response.status + ')');
                        return response.json();
                    })
                    .then(() => {
                        const incidentId = Number(currentIncidentAlert?.incident_id);
                        currentIncidentAlert = null;
                        clearInterval(alertReminderTimer);
                        const banner = document.getElementById('incident-alert-banner');
                        banner?.classList.remove('is-visible', 'is-alarming');
                        pendingAlertCount = Math.max(0, pendingAlertCount - 1);
                        if (openIncident && incidentId) {
                            window.location.assign(INCIDENT_URL_BASE + '/' + incidentId);
                            return;
                        }
                        updateAlertBacklog();
                        window.setTimeout(showNextIncidentAlert, 180);
                    })
                    .catch((error) => {
                        console.error('Dashboard alert acknowledgement error:', error);
                        if (errorEl) {
                            errorEl.textContent = 'Could not acknowledge this alert. It will stay visible; try again.';
                            errorEl.classList.remove('d-none');
                        }
                    })
                    .finally(() => {
                        [dismiss, open].forEach((button) => {
                            if (button) button.removeAttribute('aria-disabled');
                        });
                    });
            }

            function consumeAlertFeed(feed) {
                if (!feed || !Array.isArray(feed.items) || !Array.isArray(feed.pending)) return;
                const nextCursor = Number(feed.cursor);
                if (Number.isFinite(nextCursor) && nextCursor >= alertCursor) {
                    alertCursor = nextCursor;
                }
                [...feed.pending, ...feed.items]
                    .sort((first, second) => Number(first.id) - Number(second.id))
                    .forEach((item) => {
                    const id = Number(item.id);
                    if (!id || queuedAlertIds.has(id)) return;
                    queuedAlertIds.add(id);
                    alertQueue.push(item);
                });
                const count = Number(feed.pending_count);
                if (Number.isFinite(count) && count >= 0) {
                    pendingAlertCount = count;
                } else {
                    pendingAlertCount = Math.max(pendingAlertCount, alertQueue.length + (currentIncidentAlert ? 1 : 0));
                }
                if (currentIncidentAlert) updateAlertBacklog();
                showNextIncidentAlert();
            }

            let alertFeedInFlight = false;

            function loadAlertFeed() {
                if (alertFeedInFlight) return;
                alertFeedInFlight = true;
                const glue = ALERTS_URL.includes('?') ? '&' : '?';
                fetch(ALERTS_URL + glue + 'after=' + encodeURIComponent(alertCursor) + '&_=' + Date.now(), {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                    credentials: 'same-origin',
                })
                    .then((response) => {
                        if (!response.ok) throw new Error('Dashboard alert refresh failed (' + response.status + ')');
                        return response.json();
                    })
                    .then(consumeAlertFeed)
                    .catch((error) => {
                        console.error('Dashboard alert feed error:', error);
                    })
                    .finally(() => {
                        alertFeedInFlight = false;
                    });
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
                if (mapBasemap?.setStyle) {
                    mapBasemap.setStyle(kind);
                    return;
                }
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
                window.RaniagMapScroll?.guard(map);

                if (window.RANIAG_Mapbox) {
                    mapBasemap = window.RANIAG_Mapbox.addBasemap(map, MAP_CONFIG, { control: false });
                    streetLayer = mapBasemap.streetLayer;
                    satLayer = mapBasemap.satelliteLayer;
                } else {
                    streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '' });
                    satLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, attribution: '' }).addTo(map);
                }

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
                        try {
                            jurisdictionBounds = boundaryLayer.getBounds();
                            frameMapToPins(currentPoints, currentPoints.length > 0);
                        } catch (e) {}
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
                const outside = !pointIsInsidePamplona(pt);
                if (outside) {
                    L.circleMarker([lat, lng], {
                        radius: 16,
                        color: '#c2410c',
                        weight: 2,
                        dashArray: '3 3',
                        fillColor: '#fdba74',
                        fillOpacity: 0.35,
                        interactive: false,
                    }).addTo(markerLayer);
                }
                const marker = L.marker([lat, lng], {
                    icon: window.RaniagIcons.buildDivIcon({
                        icon: typeObj.icon,
                        color: typeObj.color,
                        priority: pt.priority,
                        size: 26,
                    })
                });

                const typeName = typeObj.name || 'Incident';
                const placeLine = outside
                    ? `<br><small class="text-muted">${escapeHtml(pt.barangay ? pt.barangay + ' · ' : '')}Outside Pamplona</small>`
                    : (pt.barangay ? `<br><small class="text-muted">${escapeHtml(pt.barangay)}</small>` : '');
                marker.bindPopup(
                    `<strong>${escapeHtml(pt.tracking_number || ('#' + pt.id))}</strong><br>` +
                    `${escapeHtml(typeName)}<br>` +
                    `<span class="text-capitalize">${escapeHtml((pt.status || '').replace(/_/g, ' '))}</span>` +
                    placeLine +
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
                frameMapToPins(points, true);
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
                    }).join('') : '<div class="empty-note">No open incidents on the map.</div>';
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

                rawPoints = data.situational_incidents || data.active_dispatches || [];
                applyMapFilters();
                renderReferred(data.referred_outside);
                renderDispatchTable(data.active_dispatches || []);
                renderMeters('role-status-meters', Object.entries(sb).map(([label, value]) => ({
                    label: String(label).replaceAll('_', ' '),
                    value: Number(value) || 0,
                    color: '#0b5ed7',
                })));
                renderStatusFeed(data.recent_status_updates || []);
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
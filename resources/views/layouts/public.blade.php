<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ config('raniag.name') }} — Incident reporting for {{ config('raniag.organization') }}">
    <meta name="theme-color" content="#0b1220">

    <title>@yield('title', config('raniag.name')) — {{ config('raniag.organization') }}</title>

    {{-- Social preview --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', config('raniag.name')) — {{ config('raniag.organization') }}">
    <meta property="og:description" content="Report incidents fast, track them transparently.">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|instrument-serif:400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">
    @stack('styles')
    <link href="{{ asset('css/public.css') }}?v={{ @filemtime(public_path('css/public.css')) }}" rel="stylesheet">

    <style>
        /* ============================================================
           RANIAG public shell — design tokens
           ============================================================ */
        :root {
            --rg-ink:        #0b1220;
            --rg-ink-2:      #121b2e;
            --rg-surface:    #f6f7fb;
            --rg-card:       #ffffff;
            --rg-line:       rgba(11, 18, 32, .10);
            --rg-text:       #16203a;
            --rg-muted:      #5b6780;
            --rg-brand:      #0b5ed7;      /* primary brand blue */
            --rg-brand-soft: #3d8bfd;      /* lighter blue accent */
            --rg-brand-dark: #0a53c4;      /* hover state for blue buttons */
            --rg-brand-darker: #084298;    /* active/pressed state for blue buttons */
            --rg-alert:      #b45309;      /* genuine warning/alert color — never used for CTAs */
            --rg-warning:    #b45309;      /* alias, kept for clarity where "warning" is meant explicitly */
            --rg-radius:     16px;
            --rg-shadow:     0 18px 40px -22px rgba(11,18,32,.45);
            --rg-shadow-sm:  0 6px 18px -10px rgba(11,18,32,.35);
            --rg-grad:       linear-gradient(135deg, var(--rg-brand), var(--rg-brand-soft));

            /* Aliases: public.css (shared with the admin layout) is written
               against the --raniag-* token names. Map them here so those
               rules resolve on public pages instead of silently failing. */
            --raniag-primary:       var(--rg-brand);
            --raniag-primary-dark:  #084298;
            --raniag-primary-light: rgba(8,66,152,.12);
            --raniag-accent:        var(--rg-brand-soft);
            --raniag-surface:       var(--rg-surface);
            --raniag-border:        var(--rg-line);
            --raniag-radius:        var(--rg-radius);
            --raniag-radius-sm:     10px;
            --raniag-card-shadow:   var(--rg-shadow-sm);
        }

        body.raniag-public {
            font-family: 'Instrument Sans', system-ui, -apple-system, sans-serif;
            background: var(--rg-surface);
            color: var(--rg-text);
            -webkit-font-smoothing: antialiased;
        }

        /* ---------- scroll progress ---------- */
        #rg-progress {
            position: fixed; top: 0; left: 0; height: 3px; width: 0%;
            background: var(--rg-grad); z-index: 2100;
            transition: width .08s linear;
        }

        /* ---------- navbar ---------- */
        .raniag-navbar {
            background: rgba(11, 18, 32, .82);
            backdrop-filter: saturate(160%) blur(14px);
            -webkit-backdrop-filter: saturate(160%) blur(14px);
            border-bottom: 1px solid rgba(255,255,255,.08);
            position: sticky; top: 0; z-index: 1040;
            transition: background .25s ease, box-shadow .25s ease;
        }
        .raniag-navbar.is-scrolled { background: rgba(11,18,32,.96); box-shadow: 0 10px 30px -18px rgba(0,0,0,.8); }

        .raniag-navbar .navbar-brand { letter-spacing: -.02em; color: #fff; }
        .raniag-brand-icon {
            display: inline-grid; place-items: center;
            width: 36px; height: 36px; border-radius: 11px;
            background: var(--rg-grad);
            color: #fff; font-size: 1.05rem;
            box-shadow: 0 8px 20px -10px rgba(11,94,215,.9);
        }
        .raniag-brand-sub {
            display: block; font-size: .66rem; font-weight: 500; line-height: 1;
            text-transform: uppercase; letter-spacing: .16em;
            color: rgba(255,255,255,.55); margin-top: 3px;
        }

        .raniag-navbar .nav-link {
            position: relative;
            color: rgba(255,255,255,.72) !important;
            font-weight: 500; font-size: .94rem;
            padding: .55rem .9rem !important; border-radius: 10px;
            transition: color .18s ease, background .18s ease;
        }
        .raniag-navbar .nav-link:hover { color: #fff !important; background: rgba(255,255,255,.07); }
        .raniag-navbar .nav-link.active { color: #fff !important; }
        .raniag-navbar .nav-link.active::after {
            content: ''; position: absolute; left: .9rem; right: .9rem; bottom: .18rem;
            height: 2px; border-radius: 2px; background: var(--rg-grad);
        }
        .raniag-navbar .navbar-toggler { border-color: rgba(255,255,255,.2); }

        .rg-btn-report {
            background: var(--rg-brand); color: #ffffff !important;
            border-radius: 999px; padding: .55rem 1.15rem !important;
            font-weight: 700;
            letter-spacing: 0.3px;
            box-shadow: 0 10px 24px -12px rgba(11,94,215,.95);
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease;
        }
        .rg-btn-report:hover { transform: translateY(-1px); background: var(--rg-brand-dark); box-shadow: 0 12px 28px -10px rgba(11,94,215,1); color: #ffffff !important; }
        .rg-btn-report.active { background: var(--rg-brand-dark); color: #ffffff !important; }
        .rg-btn-report::after { display: none !important; }

        .rg-btn-ghost {
            border: 1px solid rgba(255,255,255,.22); border-radius: 999px;
            padding: .5rem 1rem !important; color: #fff !important;
        }
        .rg-btn-ghost:hover { background: rgba(255,255,255,.1); }

        .rg-tagline {
            font-size: .95rem; font-weight: 600; letter-spacing: .01em;
            color: var(--rg-brand-soft);
        }

        /* ---------- ambient page top ---------- */
        .rg-shell { position: relative; overflow: clip; }
        .rg-shell::before,
        .rg-shell::after {
            content: ''; position: absolute; border-radius: 50%; filter: blur(70px);
            pointer-events: none; z-index: 0;
        }
        .rg-shell::before {
            width: 460px; height: 460px; top: -220px; left: -140px;
            background: rgba(11,94,215,.28);
        }
        .rg-shell::after {
            width: 380px; height: 380px; top: -160px; right: -120px;
            background: rgba(217,85,43,.18);
        }
        .rg-shell > * { position: relative; z-index: 1; }

        /* ---------- status strip ---------- */
        .rg-strip {
            background: var(--rg-ink-2); color: rgba(255,255,255,.72);
            font-size: .8rem; letter-spacing: .01em;
        }
        .rg-strip .rg-dot {
            width: 8px; height: 8px; border-radius: 50%; background: #35d39a;
            display: inline-block; margin-right: .45rem;
            box-shadow: 0 0 0 0 rgba(53,211,154,.6);
            animation: rg-pulse 2.2s infinite;
        }
        @keyframes rg-pulse {
            0%   { box-shadow: 0 0 0 0 rgba(53,211,154,.55); }
            70%  { box-shadow: 0 0 0 9px rgba(53,211,154,0); }
            100% { box-shadow: 0 0 0 0 rgba(53,211,154,0); }
        }

        /* ---------- flash alert ---------- */
        .rg-flash {
            border: 1px solid rgba(8,66,152,.22);
            border-left: 4px solid var(--rg-brand);
            background: #fff; color: var(--rg-text);
            border-radius: var(--rg-radius);
            box-shadow: var(--rg-shadow-sm);
            animation: rg-rise .35s ease both;
        }
        @keyframes rg-rise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

        /* ---------- footer ---------- */
        .raniag-footer {
            background: var(--rg-ink);
            color: rgba(255,255,255,.62);
            border-top: 1px solid rgba(255,255,255,.07);
        }
        .raniag-footer a { color: rgba(255,255,255,.72); text-decoration: none; }
        .raniag-footer a:hover { color: #fff; text-decoration: underline; }
        .rg-foot-title {
            font-size: .72rem; text-transform: uppercase; letter-spacing: .16em;
            color: rgba(255,255,255,.42); font-weight: 600;
        }
        .rg-foot-rule { border-color: rgba(255,255,255,.09); }

        /* ---------- loading overlay ---------- */
        #global-loading-overlay {
            position: fixed; inset: 0; z-index: 2000;
            background: rgba(11, 18, 32, .72);
            backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center; padding: 1rem;
            transition: opacity .2s ease;
        }
        #global-loading-overlay.d-none { display: none !important; }
        body.rg-scroll-locked { overflow: hidden; position: fixed; left: 0; right: 0; width: 100%; }
        #global-loading-overlay .rg-loader {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 18px; padding: 1.75rem 2.25rem; text-align: center;
        }
        #global-loading-overlay .spinner-border { width: 2.75rem; height: 2.75rem; border-width: .28rem; color: var(--rg-brand-soft) !important; }
        #global-loading-overlay .loading-text { margin-top: .9rem; color: #f8fafc; font-weight: 600; letter-spacing: .01em; }

        /* ---------- reveal on scroll (progressive enhancement) ---------- */
        [data-rg-reveal] { opacity: 0; transform: translateY(14px); transition: opacity .5s ease, transform .5s ease; }
        [data-rg-reveal].is-in { opacity: 1; transform: none; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
            [data-rg-reveal] { opacity: 1; transform: none; }
        }

        /* ============================================================
           SHARED PAGE COMPONENTS — used by every public view
           ============================================================ */

        /* ---------- page header (same on every page) ---------- */
        .rg-page-head { margin-bottom: 1.75rem; }
        .rg-eyebrow {
            display: inline-flex; align-items: center; gap: .4rem;
            font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .16em;
            color: var(--rg-brand);
            background: rgba(11,94,215,.10);
            border: 1px solid rgba(11,94,215,.22);
            border-radius: 999px; padding: .3rem .7rem; margin-bottom: .75rem;
        }
        .rg-page-title {
            font-size: clamp(1.55rem, 3vw, 2.1rem); font-weight: 700;
            letter-spacing: -.025em; color: var(--rg-ink); margin: 0;
        }
        .rg-page-sub { color: var(--rg-muted); margin: .5rem 0 0; max-width: 62ch; }

        /* ---------- cards ---------- */
        .raniag-card, .card.raniag-card {
            background: var(--rg-card);
            border: 1px solid var(--rg-line);
            border-radius: var(--rg-radius);
            box-shadow: var(--rg-shadow-sm);
            transition: box-shadow .25s ease, transform .25s ease;
        }
        .raniag-card-header, .card-header.raniag-card-header {
            background: #fbfcfe;
            border-bottom: 1px solid var(--rg-line);
            font-weight: 600; letter-spacing: -.01em; color: var(--rg-ink);
            border-radius: var(--rg-radius) var(--rg-radius) 0 0 !important;
        }
        .rg-card-hover:hover { transform: translateY(-3px); box-shadow: var(--rg-shadow); }

        /* ---------- hero ---------- */
        .raniag-hero {
            position: relative; overflow: hidden;
            border-radius: calc(var(--rg-radius) + 8px);
            background: radial-gradient(120% 140% at 8% 0%, #17493f 0%, var(--rg-ink) 62%);
            color: #fff; box-shadow: var(--rg-shadow);
            border: 1px solid rgba(255,255,255,.06);
        }
        .raniag-hero::after {
            content: ''; position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
            background-size: 46px 46px; mask-image: radial-gradient(60% 60% at 80% 10%, #000, transparent);
            pointer-events: none;
        }
        .raniag-hero > * { position: relative; z-index: 1; }
        .rg-hero-title { font-weight: 700; letter-spacing: -.03em; }

        /* ---------- icon tiles ---------- */
        .rg-icon-tile {
            display: inline-grid; place-items: center;
            width: 46px; height: 46px; border-radius: 14px;
            background: rgba(11,94,215,.12); color: var(--rg-brand);
            font-size: 1.15rem;
        }
        .rg-icon-tile.is-alert { background: rgba(217,85,43,.12); color: var(--rg-alert); }

        /* ---------- step badge ---------- */
        .raniag-step-badge {
            display: inline-grid; place-items: center;
            width: 28px; height: 28px; border-radius: 9px;
            background: var(--rg-grad); color: #fff;
            font-size: .82rem; font-weight: 700;
            box-shadow: 0 8px 18px -10px rgba(11,94,215,.9);
        }

        /* ---------- selectable type cards ---------- */
        .raniag-type-card {
            cursor: pointer; border: 1px solid var(--rg-line); border-radius: 14px;
            background: #fff; box-shadow: none;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease, background .18s ease;
        }
        .raniag-type-card input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
        .raniag-type-card:hover { border-color: rgba(11,94,215,.5); transform: translateY(-2px); box-shadow: var(--rg-shadow-sm); }
        .raniag-type-card.selected,
        .raniag-type-card:has(input:checked) {
            border-color: var(--rg-brand); background: rgba(11,94,215,.06);
            box-shadow: 0 0 0 3px rgba(11,94,215,.14);
        }

        /* ---------- buttons ---------- */
        .btn-primary {
            --bs-btn-bg: var(--rg-brand); --bs-btn-border-color: var(--rg-brand);
            --bs-btn-hover-bg: var(--rg-brand-dark); --bs-btn-hover-border-color: var(--rg-brand-dark);
            --bs-btn-active-bg: var(--rg-brand-darker); --bs-btn-active-border-color: var(--rg-brand-darker);
            --bs-btn-disabled-bg: var(--rg-brand); --bs-btn-disabled-border-color: var(--rg-brand);
            border-radius: 999px; font-weight: 600;
            box-shadow: 0 12px 26px -16px rgba(11,94,215,.9);
        }
        .btn-outline-primary {
            --bs-btn-color: var(--rg-brand); --bs-btn-border-color: rgba(11,94,215,.4);
            --bs-btn-hover-bg: var(--rg-brand); --bs-btn-hover-border-color: var(--rg-brand);
            --bs-btn-active-bg: var(--rg-brand); --bs-btn-active-border-color: var(--rg-brand);
            border-radius: 999px; font-weight: 600;
        }
        .btn-outline-secondary, .btn-light, .btn-outline-light, .btn-danger {
            border-radius: 999px; font-weight: 600;
        }
        .btn-success {
            --bs-btn-bg: var(--rg-brand); --bs-btn-border-color: var(--rg-brand);
            --bs-btn-hover-bg: var(--rg-brand-dark); --bs-btn-hover-border-color: var(--rg-brand-dark);
            --bs-btn-active-bg: var(--rg-brand-darker); --bs-btn-active-border-color: var(--rg-brand-darker);
            --bs-btn-disabled-bg: var(--rg-brand); --bs-btn-disabled-border-color: var(--rg-brand);
            border-radius: 999px; font-weight: 600;
        }
        .btn-link { color: var(--rg-brand); font-weight: 600; }
        .rg-btn-alert {
            background: var(--rg-warning); border-color: var(--rg-warning); color: #fff;
            border-radius: 999px; font-weight: 600;
            box-shadow: 0 12px 26px -16px rgba(180,83,9,.85);
        }
        .rg-btn-alert:hover { filter: brightness(1.06); color: #fff; }

        /* ---------- forms ---------- */
        .form-label { font-weight: 600; font-size: .88rem; color: var(--rg-ink); }
        .form-control, .form-select {
            border-radius: 12px; border-color: var(--rg-line);
            padding: .6rem .85rem; background: #fdfdff;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--rg-brand-soft);
            box-shadow: 0 0 0 3px rgba(11,94,215,.16);
            background: #fff;
        }
        .form-control[readonly] { background: #f3f5f9; }
        .form-check-input:checked { background-color: var(--rg-brand); border-color: var(--rg-brand); }
        .form-check-input:focus { box-shadow: 0 0 0 3px rgba(11,94,215,.16); border-color: var(--rg-brand-soft); }
        .form-text { color: var(--rg-muted); }

        /* ---------- alerts ---------- */
        .alert { border-radius: var(--rg-radius); border-width: 1px; }
        .alert-danger { border-left: 4px solid #d33b2f; }
        .alert-warning { border-left: 4px solid #d9852b; }

        /* ---------- tracking number ---------- */
        .raniag-tracking-number {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 1.4rem; font-weight: 700; letter-spacing: .12em;
            color: var(--rg-ink);
        }
        .rg-code-panel {
            border: 1px dashed rgba(8,66,152,.35);
            background: rgba(11,94,215,.07);
            border-radius: var(--rg-radius);
        }

        /* ---------- timeline ---------- */
        .raniag-timeline { position: relative; padding-left: 1.35rem; }
        .raniag-timeline::before {
            content: ''; position: absolute; left: 5px; top: .35rem; bottom: .35rem;
            width: 2px; background: var(--rg-line);
        }
        .raniag-timeline-item { position: relative; padding-bottom: 1.15rem; }
        .raniag-timeline-item:last-child { padding-bottom: 0; }
        .raniag-timeline-item::before {
            content: ''; position: absolute; left: -1.35rem; top: .45rem;
            width: 12px; height: 12px; border-radius: 50%;
            background: var(--rg-grad); box-shadow: 0 0 0 3px rgba(11,94,215,.16);
        }

        /* ---------- progress tracker ---------- */
        .raniag-progress-tracker {
            padding: 2rem 0;
        }
        .progress-container {
            position: relative;
            padding: 2rem 0;
        }
        .progress-track {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--rg-line);
            transform: translateY(-50%);
            z-index: 1;
        }
        .progress-fill {
            position: absolute;
            top: 50%;
            left: 0;
            height: 2px;
            background: #0b5ed7;
            transform: translateY(-50%);
            z-index: 2;
            transition: width 0.4s ease, background 0.3s ease;
        }
        /* Status-specific fill colors */
        .progress-fill[data-current-status="submitted"] { background: #0b5ed7; }
        .progress-fill[data-current-status="received"] { background: #17a2b8; }
        .progress-fill[data-current-status="assigned"] { background: #ffc107; }
        .progress-fill[data-current-status="in_progress"] { background: #fd7e14; }
        .progress-fill[data-current-status="resolved"] { background: #28a745; }
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 3;
        }
        .progress-step {
            flex: 1;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .step-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid var(--rg-line);
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
            position: relative;
            z-index: 4;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: var(--rg-muted);
        }
        .progress-step.completed .step-dot {
            background: #0b5ed7;
            border-color: #0b5ed7;
            box-shadow: 0 0 0 3px rgba(11,94,215,.12);
            color: #fff;
        }
        .progress-step[data-status="received"].completed .step-dot { background: #17a2b8; border-color: #17a2b8; box-shadow: 0 0 0 3px rgba(23,162,184,.12); color: #fff; }
        .progress-step[data-status="assigned"].completed .step-dot { background: #ffc107; border-color: #ffc107; box-shadow: 0 0 0 3px rgba(255,193,7,.12); color: #000; }
        .progress-step[data-status="in_progress"].completed .step-dot { background: #fd7e14; border-color: #fd7e14; box-shadow: 0 0 0 3px rgba(253,126,20,.12); color: #fff; }
        .progress-step[data-status="resolved"].completed .step-dot { background: #28a745; border-color: #28a745; box-shadow: 0 0 0 3px rgba(40,167,69,.12); color: #fff; }
        .step-name {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--rg-muted);
            white-space: nowrap;
            transition: color 0.3s ease;
        }
        .progress-step.completed .step-name {
            color: var(--rg-ink);
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .progress-container {
                padding: 1.5rem 0;
            }
            .progress-steps {
                gap: 0.5rem;
            }
            .step-dot {
                width: 28px;
                height: 28px;
                border-width: 2.5px;
                margin-bottom: 0.5rem;
            }
            .step-name {
                font-size: 0.75rem;
            }
        }
        @media (max-width: 480px) {
            .progress-container {
                padding: 1rem 0;
            }
            .step-dot {
                width: 24px;
                height: 24px;
                border-width: 2px;
                margin-bottom: 0.4rem;
            }
            .step-name {
                font-size: 0.7rem;
            }
        }

        /* ---------- map ---------- */
        #incident-map { height: 340px; border-radius: 14px; border: 1px solid var(--rg-line); overflow: hidden; }

    </style>
</head>
<body class="raniag-public d-flex flex-column min-vh-100">
    <div id="rg-progress"></div>

    <a href="#rg-main" class="visually-hidden-focusable position-absolute top-0 start-0 m-2 btn btn-light btn-sm">Skip to content</a>

    {{-- ================= top strip ================= --}}
    <div class="rg-strip py-2 d-none d-md-block">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><span class="rg-dot"></span>Reporting channel online — 24/7 intake</span>
            <span class="d-flex align-items-center gap-3">
                <span><i class="bi bi-shield-lock me-1"></i>Confidential &amp; anonymous option</span>
                <span class="d-none d-lg-inline"><i class="bi bi-clock-history me-1"></i>Track any report with your reference code</span>
            </span>
        </div>
    </div>

    {{-- ================= navbar ================= --}}
    <nav class="navbar navbar-expand-lg navbar-dark raniag-navbar" id="rg-nav">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('public.home') }}">
                <span class="raniag-brand-icon"><i class="bi bi-shield-check"></i></span>
                <span class="d-flex flex-column lh-1">
                    {{ config('raniag.name') }}
                    <span class="raniag-brand-sub">{{ config('raniag.organization') }}</span>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav"
                    aria-controls="publicNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="publicNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.home') ? 'active' : '' }}"
                           href="{{ route('public.home') }}">
                            <i class="bi bi-house-door me-1 d-lg-none"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.track*') ? 'active' : '' }}"
                           href="{{ route('public.track') }}">
                            <i class="bi bi-search me-1 d-lg-none"></i>Track Report
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.dashboard') ? 'active' : '' }}"
                           href="{{ route('public.dashboard') }}">
                            <i class="bi bi-bar-chart-line me-1 d-lg-none"></i>Community Dashboard
                        </a>
                    </li>
                    @auth
                        <li class="nav-item">
                            <a class="nav-link rg-btn-ghost" href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2 me-1"></i>Dashboard
                            </a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link rg-btn-ghost" href="{{ route('login') }}">
                                <i class="bi bi-person-badge me-1"></i>Staff Login
                            </a>
                        </li>
                    @endauth
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="nav-link rg-btn-report {{ request()->routeIs('public.report.*') ? 'active' : '' }}"
                           href="{{ route('public.report.create') }}">
                            <i class="bi bi-megaphone-fill me-1"></i>Report an Incident
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- ================= main ================= --}}
    <main id="rg-main" class="rg-shell flex-grow-1 py-4 py-lg-5">
        @if (session('success'))
            <div class="container mb-4">
                <div class="rg-flash alert alert-dismissible fade show d-flex align-items-start gap-2 p-3" role="alert">
                    <i class="bi bi-check-circle-fill fs-5" style="color: var(--rg-brand);"></i>
                    <div class="flex-grow-1">{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="container mb-4">
                <div class="rg-flash alert alert-dismissible fade show d-flex align-items-start gap-2 p-3" role="alert"
                     style="border-left-color: var(--rg-alert);">
                    <i class="bi bi-exclamation-triangle-fill fs-5" style="color: var(--rg-alert);"></i>
                    <div class="flex-grow-1">{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    {{-- ================= footer ================= --}}
    <footer class="raniag-footer pt-5 pb-4 mt-auto">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="raniag-brand-icon"><i class="bi bi-shield-check"></i></span>
                        <span class="text-white fw-semibold">{{ config('raniag.name') }}</span>
                    </div>
                    <p class="small mb-0" style="max-width: 34ch;">
                        Incident Reporting and Analytics System for {{ config('raniag.organization') }}.
                        Every report is logged, routed, and auditable.
                    </p>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="rg-foot-title mb-2">Report</div>
                    <ul class="list-unstyled small mb-0 d-grid gap-2">
                        <li><a href="{{ route('public.report.create') }}">File an incident</a></li>
                        <li><a href="{{ route('public.track') }}">Track a report</a></li>
                        <li><a href="{{ route('public.home') }}">How it works</a></li>
                    </ul>
                </div>

                <div class="col-6 col-lg-4">
                    <div class="rg-foot-title mb-2">Access</div>
                    <ul class="list-unstyled small mb-0 d-grid gap-2">
                        @auth
                            <li><a href="{{ route('dashboard') }}">Staff dashboard</a></li>
                        @else
                            <li><a href="{{ route('login') }}">Staff login</a></li>
                        @endauth
                        <li><span class="text-white-50"><i class="bi bi-shield-lock me-1"></i>Confidential submissions</span></li>
                    </ul>
                </div>
            </div>

            <hr class="rg-foot-rule my-4">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small">
                <span>&copy; {{ date('Y') }} {{ config('raniag.organization') }} — {{ config('raniag.name') }}</span>
                <span class="text-white-50">Incident Reporting and Analytics System</span>
            </div>
        </div>
    </footer>

    {{-- ================= loading overlay ================= --}}
    <div id="global-loading-overlay" class="d-none" role="status" aria-live="polite">
        <div class="rg-loader">
            <div class="spinner-border" role="status" aria-hidden="true"></div>
            <div class="loading-text">Processing, please wait...</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Robust scroll lock: `overflow:hidden` on <body> alone doesn't stop
    // touch/rubber-band scrolling on iOS Safari, which let the page scroll
    // behind the loading screen during report submission. Pinning the body
    // with `position:fixed` (and restoring the exact scroll offset after)
    // blocks scrolling everywhere, which matters here for safety: the
    // person must not be able to scroll away or resubmit mid-transmission.
    let rgLockedScrollY = 0;
    function showLoadingOverlay(message = 'Processing, please wait...') {
        const overlay = document.getElementById('global-loading-overlay');
        if (!overlay) return;
        const label = overlay.querySelector('.loading-text');
        if (label) { label.textContent = message; }
        overlay.classList.remove('d-none');
        rgLockedScrollY = window.scrollY || window.pageYOffset || 0;
        document.body.classList.add('rg-scroll-locked');
        document.body.style.top = (-rgLockedScrollY) + 'px';
    }
    function hideLoadingOverlay() {
        document.getElementById('global-loading-overlay')?.classList.add('d-none');
        document.body.classList.remove('rg-scroll-locked');
        document.body.style.top = '';
        window.scrollTo(0, rgLockedScrollY);
    }

    // Sticky navbar state + scroll progress bar
    (function () {
        const nav = document.getElementById('rg-nav');
        const bar = document.getElementById('rg-progress');
        const onScroll = () => {
            const y = window.scrollY || 0;
            if (nav) nav.classList.toggle('is-scrolled', y > 8);
            if (bar) {
                const h = document.documentElement.scrollHeight - window.innerHeight;
                bar.style.width = (h > 0 ? (y / h) * 100 : 0) + '%';
            }
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    })();

    // Reveal-on-scroll for any element with data-rg-reveal
    (function () {
        const items = document.querySelectorAll('[data-rg-reveal]');
        if (!items.length || !('IntersectionObserver' in window)) {
            items.forEach(el => el.classList.add('is-in'));
            return;
        }
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
        items.forEach(el => io.observe(el));
    })();

    // Auto-dismiss flash messages
    setTimeout(() => {
        document.querySelectorAll('.rg-flash').forEach(el => {
            bootstrap.Alert.getOrCreateInstance(el).close();
        });
    }, 7000);

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('SW Registered', reg))
                .catch(err => console.error('SW Registration Failed', err));
        });
    }
    </script>
    <x-lightbox />
    @stack('scripts')
</body>
</html>

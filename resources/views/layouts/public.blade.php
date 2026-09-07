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

        /* Icons: bootstrap-icons.min.css uses font-display:block (FOIT).
           Swap shows text/layout immediately; icon glyph fills in when ready. */
        @font-face {
            font-family: bootstrap-icons;
            font-display: swap;
            src: url("{{ asset('vendor/bootstrap-icons/fonts/bootstrap-icons.woff2') }}") format("woff2");
        }

        /* ---------- reveal on scroll — GSAP handles opacity/transform ---------- */
        /* GSAP sets inline styles; this only hides elements before GSAP loads */
          /* Content remains visible even when optional animation assets are
              unavailable or intentionally omitted for a faster first paint. */
          [data-rg-reveal]:not(.gsap-ready) { opacity: 1; }
          [data-rg-reveal].gsap-ready { opacity: 1; }
        /* Live-refresh regions must never flash invisible after a DOM swap */
        [data-live-refresh-target] [data-rg-reveal] { opacity: 1 !important; transform: none !important; }

        /* Lenis smooth-scroll html setup */
        html.lenis, html.lenis body { height: auto; }
        .lenis.lenis-smooth { scroll-behavior: auto !important; }
        .lenis.lenis-smooth [data-lenis-prevent] { overscroll-behavior: contain; }

        /* Hero word reveal — clip-path animation base */
        [data-rg-hero-word] { display: inline-block; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
            [data-rg-reveal]:not(.gsap-ready) { opacity: 1; }
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

        /* ---------- incident type cards ---------- */
        .rg-type-tile {
            display: flex; align-items: flex-start; gap: .85rem;
            background: var(--rg-card); border: 1px solid var(--rg-line);
            border-left: 3px solid var(--rg-brand-soft);
            border-radius: var(--rg-radius); box-shadow: var(--rg-shadow-sm);
            padding: 1rem 1.1rem; text-align: left;
            transition: transform .2s ease, box-shadow .2s ease, border-left-color .2s ease;
        }
        .rg-type-tile:hover {
            transform: translateY(-3px); box-shadow: var(--rg-shadow);
            border-left-color: var(--rg-brand);
        }
        .rg-type-tile .rg-icon-tile { flex: 0 0 auto; margin-top: .1rem; }
        .rg-type-tile__desc { font-size: .78rem; margin-top: .15rem; line-height: 1.35; }

        /* ---------- FAQ accordion ---------- */
        .rg-faq-accordion .accordion-item {
            border: 1px solid var(--rg-line); border-radius: var(--rg-radius) !important;
            margin-bottom: .6rem; overflow: hidden;
        }
        .rg-faq-accordion .accordion-button {
            font-weight: 600; color: var(--rg-ink);
        }
        .rg-faq-accordion .accordion-button:not(.collapsed) {
            background: rgba(11,94,215,.06); color: var(--rg-brand-dark); box-shadow: none;
        }
        .rg-faq-accordion .accordion-button:focus { box-shadow: 0 0 0 3px rgba(11,94,215,.14); }

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
                <span class="raniag-brand-icon"><img src="/images/icons/icon-72x72.png" alt="RANIAG" class="w-100 h-100" style="object-fit:contain;"></span>
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
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.support') ? 'active' : '' }}"
                           href="{{ route('public.support') }}">
                            <i class="bi bi-headset me-1 d-lg-none"></i>Support
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
                        <span class="raniag-brand-icon"><img src="/images/icons/icon-72x72.png" alt="RANIAG" class="w-100 h-100" style="object-fit:contain;"></span>
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
    <script src="{{ asset('js/live-refresh.js') }}?v={{ @filemtime(public_path('js/live-refresh.js')) }}"></script>
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

    // Reveal helper for content that renders AFTER the initial GSAP/
    // ScrollTrigger setup below (e.g. dashboard KPIs injected once an
    // async fetch resolves). ScrollTrigger can't watch something that
    // doesn't exist yet, so pages with async content call this manually,
    // right after they un-hide their container, instead of relying on
    // data-rg-reveal/data-rg-stagger.
    window.rgRevealNow = function (root) {
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced || typeof gsap === 'undefined') return;
        const scope = (typeof root === 'string') ? document.querySelector(root) : (root || document);
        if (!scope) return;
        scope.querySelectorAll('[data-rg-stagger]').forEach(container => {
            if (container.dataset.rgStagger === 'css') return;
            const children = Array.from(container.children);
            if (!children.length) return;
            gsap.from(children, { y: 30, opacity: 0, duration: 0.55, stagger: { amount: 0.3, from: 'start' }, ease: 'power3.out' });
        });
        scope.querySelectorAll('[data-rg-pop]').forEach((el, i) => {
            gsap.from(el, { scale: 0.7, opacity: 0, duration: 0.45, delay: i * 0.06, ease: 'back.out(1.7)' });
        });
    };

    // ============================================================
    //  GSAP + Lenis — initialised after scripts are deferred-loaded
    // ============================================================
    function initPublicMotion() {
        // ------ respect reduced-motion preference ------
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // ------ mark all reveal elements as gsap-ready (removes CSS opacity:0) ------
        document.querySelectorAll('[data-rg-reveal]').forEach(el => el.classList.add('gsap-ready'));

        if (prefersReduced || typeof gsap === 'undefined' || typeof Lenis === 'undefined' || typeof ScrollTrigger === 'undefined') {
            // Fallback: ensure everything visible, run navbar logic natively
            const nav = document.getElementById('rg-nav');
            const bar = document.getElementById('rg-progress');
            const onScroll = () => {
                const y = window.scrollY || 0;
                if (nav) nav.classList.toggle('is-scrolled', y > 8);
                if (bar) { const h = document.documentElement.scrollHeight - window.innerHeight; bar.style.width = (h > 0 ? (y / h) * 100 : 0) + '%'; }
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
            return;
        }

        // ------ 1. Lenis smooth scroll ------
        const lenis = new Lenis({
            duration: 1.15,
            easing: t => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            smoothTouch: false,
            touchMultiplier: 1.8,
            infinite: false,
        });

        // Sync Lenis with GSAP ticker for frame-perfect timing
        gsap.ticker.add(time => lenis.raf(time * 1000));
        gsap.ticker.lagSmoothing(0);

        // ------ 2. ScrollTrigger ------
        gsap.registerPlugin(ScrollTrigger);
        lenis.on('scroll', ScrollTrigger.update);
        ScrollTrigger.scrollerProxy(document.documentElement, {
            scrollTop(value) { return arguments.length ? lenis.scrollTo(value, { immediate: true }) : lenis.scroll; },
            getBoundingClientRect() { return { top: 0, left: 0, width: window.innerWidth, height: window.innerHeight }; },
        });

        // ------ 3. Scroll progress bar (via Lenis event) ------
        const bar = document.getElementById('rg-progress');
        if (bar) {
            lenis.on('scroll', ({ scroll, limit }) => {
                bar.style.width = (limit > 0 ? (scroll / limit) * 100 : 0) + '%';
            });
        }

        // ------ 4. Navbar sticky state (via Lenis) ------
        const nav = document.getElementById('rg-nav');
        if (nav) {
            lenis.on('scroll', ({ scroll }) => nav.classList.toggle('is-scrolled', scroll > 8));
        }

        // ------ 6. Hero section — staggered entrance ------
        const heroEyebrow = document.querySelector('[data-rg-hero-eyebrow]');
        const heroTitle   = document.querySelector('[data-rg-hero-title]');
        const heroTag     = document.querySelector('[data-rg-hero-tag]');
        const heroDesc    = document.querySelector('[data-rg-hero-desc]');
        const heroBtns    = document.querySelector('[data-rg-hero-btns]');
        const heroCard    = document.querySelector('[data-rg-hero-card]');

        if (heroTitle) {
            const heroTl = gsap.timeline({ delay: 0.22 });
            if (heroEyebrow) heroTl.from(heroEyebrow, { y: 22, opacity: 0, duration: 0.55, ease: 'power3.out' });
            heroTl.from(heroTitle,   { y: 38, opacity: 0, duration: 0.75, ease: 'power4.out' }, heroEyebrow ? '-=0.25' : '+=0');
            if (heroTag)  heroTl.from(heroTag,  { y: 18, opacity: 0, duration: 0.5, ease: 'power3.out' }, '-=0.5');
            if (heroDesc) heroTl.from(heroDesc, { y: 18, opacity: 0, duration: 0.5, ease: 'power3.out' }, '-=0.42');
            if (heroBtns) heroTl.from(heroBtns, { y: 16, opacity: 0, duration: 0.45, ease: 'power3.out' }, '-=0.38');
            if (heroCard) heroTl.from(heroCard,  { x: 32, opacity: 0, duration: 0.65, ease: 'power3.out' }, '-=0.52');
        }

        // ------ 7. Generic reveal blocks (data-rg-reveal) ------
        gsap.utils.toArray('[data-rg-reveal]').forEach(el => {
            // Skip if inside a live-refresh zone (those are always visible)
            if (el.closest('[data-live-refresh-target]')) return;
            // Skip elements that start hidden (e.g. inside a d-none container
            // awaiting an async fetch) — ScrollTrigger can't measure a
            // display:none element, so it locks in a wrong trigger position
            // and the element is left part-transparent forever. Pages with
            // this pattern call window.rgRevealNow() themselves once the
            // container is un-hidden (see dashboard.blade.php).
            if (el.offsetParent === null) return;
            gsap.from(el, {
                y: 38,
                opacity: 0,
                duration: 0.72,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: el,
                    start: 'top 90%',
                    toggleActions: 'play none none none',
                    once: true,
                },
            });
        });

        // ------ 8. Card stagger groups (data-rg-stagger) ------
        gsap.utils.toArray('[data-rg-stagger]').forEach(container => {
            if (container.dataset.rgStagger === 'css') return;
            // Skip containers that start hidden (e.g. #pd-content on the
            // dashboard, which is d-none until its fetch() resolves). Same
            // reasoning as the data-rg-reveal guard above — a tween created
            // against a display:none container leaves its children stuck at
            // a dimmed, never-completed opacity once the container is later
            // shown. window.rgRevealNow() handles the reveal for these once
            // they're actually visible.
            if (container.offsetParent === null) return;
            const children = Array.from(container.children);
            if (!children.length) return;
            const staggerAnimation = {
                y: 44,
                opacity: 0,
                duration: 0.6,
                stagger: { amount: 0.35, from: 'start' },
                ease: 'power3.out',
            };
            if (container.dataset.rgStagger !== 'on-load') {
                staggerAnimation.scrollTrigger = {
                    trigger: container,
                    start: 'top 88%',
                    toggleActions: 'play none none none',
                    once: true,
                };
            }
            gsap.from(children, staggerAnimation);
        });

        // ------ 9. Feature icon tiles — subtle scale pop ------
        gsap.utils.toArray('.rg-icon-tile').forEach(tile => {
            gsap.from(tile, {
                scale: 0.6,
                opacity: 0,
                duration: 0.5,
                ease: 'back.out(1.7)',
                scrollTrigger: { trigger: tile, start: 'top 90%', once: true },
            });
        });

        // ------ 9a. Generic scale-pop for one-off elements (data-rg-pop) ------
        // (Fine to use inside a live-refresh zone too — the periodic
        // refresh only swaps innerHTML after this initial pass has
        // already played once; it doesn't re-trigger this animation.)
        gsap.utils.toArray('[data-rg-pop]').forEach((el, i) => {
            gsap.from(el, {
                scale: 0.65, opacity: 0, duration: 0.5, delay: i * 0.06,
                ease: 'back.out(1.7)',
                scrollTrigger: { trigger: el, start: 'top 92%', once: true },
            });
        });

        // ------ 9b. Live impact counters — count up from 0 ------
        gsap.utils.toArray('[data-rg-counter]').forEach(el => {
            const target = parseInt(el.dataset.rgCounterTo || '0', 10);
            const counterObj = { val: 0 };
            gsap.to(counterObj, {
                val: target,
                duration: 1.4,
                ease: 'power2.out',
                onUpdate: () => { el.textContent = Math.round(counterObj.val).toLocaleString(); },
                scrollTrigger: { trigger: el, start: 'top 90%', once: true },
            });
        });

        // ------ 10. Download CTA — slide from left ------
        const dlCta = document.querySelector('.rg-download-cta');
        if (dlCta) {
            gsap.from(dlCta, {
                x: -28,
                opacity: 0,
                duration: 0.7,
                ease: 'power3.out',
                scrollTrigger: { trigger: dlCta, start: 'top 88%', once: true },
            });
        }

        // ------ 11. Footer columns stagger ------
        const footerCols = document.querySelectorAll('.raniag-footer .row > [class*="col"]');
        if (footerCols.length) {
            gsap.from(footerCols, {
                y: 28,
                opacity: 0,
                duration: 0.6,
                stagger: 0.1,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: '.raniag-footer',
                    start: 'top 92%',
                    once: true,
                },
            });
        }

        // ------ 12. Announce cards — horizontal stagger ------
        gsap.utils.toArray('.rg-announce-card').forEach((card, i) => {
            gsap.from(card, {
                y: 30,
                opacity: 0,
                duration: 0.55,
                delay: i * 0.1,
                ease: 'power3.out',
                scrollTrigger: { trigger: card, start: 'top 90%', once: true },
            });
        });

        // ------ 13. Support card — subtle scale ------
        const supportCard = document.querySelector('.rg-support-card');
        if (supportCard) {
            gsap.from(supportCard, {
                scale: 0.97,
                opacity: 0,
                duration: 0.65,
                ease: 'power3.out',
                scrollTrigger: { trigger: supportCard, start: 'top 88%', once: true },
            });
        }

        // ------ 14. GSAP card hover micro-interactions ------
        document.querySelectorAll('.raniag-card, .rg-announce-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                gsap.to(card, { y: -4, boxShadow: '0 22px 44px -18px rgba(11,18,32,.32)', duration: 0.25, ease: 'power2.out' });
            });
            card.addEventListener('mouseleave', () => {
                gsap.to(card, { y: 0, boxShadow: '', duration: 0.3, ease: 'power2.out' });
            });
        });

        // ------ 15. Announcement section header slide ------
        const updatesHead = document.querySelector('#updates .d-flex.align-items-center.justify-content-between');
        if (updatesHead) {
            gsap.from(updatesHead, {
                y: 22, opacity: 0, duration: 0.55, ease: 'power3.out',
                scrollTrigger: { trigger: updatesHead, start: 'top 90%', once: true },
            });
        }

        // ------ 16. rg-strip top bar fade ------
        const strip = document.querySelector('.rg-strip');
        if (strip) gsap.from(strip, { opacity: 0, duration: 0.5, ease: 'power2.out' });

        // Refresh ScrollTrigger after all setup
        ScrollTrigger.refresh();

        // Positions computed above can be stale: Bootstrap Icons (a webfont)
        // and any late CSS/images can reflow the page after this point,
        // shifting elements out from under the trigger markers ScrollTrigger
        // just recorded. When that happens, an element whose "from" state
        // (opacity:0) already rendered never gets its trigger re-checked,
        // so it stays invisible even though it's on screen. Recompute once
        // fonts finish and once more on full window load to catch both.
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(() => ScrollTrigger.refresh());
        }
        window.addEventListener('load', () => ScrollTrigger.refresh());
    }

    function loadPublicScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    // Motion is decorative, so load it after the first screen and during an
    // idle window. The page remains interactive if a CDN is slow or blocked.
    window.addEventListener('load', () => {
        const start = () => {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                initPublicMotion();
                return;
            }

            loadPublicScript('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js')
                .then(() => loadPublicScript('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js'))
                .then(() => loadPublicScript('https://unpkg.com/lenis@1.1.14/dist/lenis.min.js'))
                .then(initPublicMotion)
                .catch(() => initPublicMotion());
        };

        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(start, { timeout: 2000 });
        } else {
            setTimeout(start, 1000);
        }
    }, { once: true });

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
    @unless(request()->routeIs('public.support'))
        <x-help-fab :href="route('public.support')" />
    @endunless
    @stack('scripts')
</body>
</html>

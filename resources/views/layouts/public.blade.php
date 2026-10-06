<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ config('raniag.name') }} — Incident reporting for {{ config('raniag.organization') }}">
    <meta name="theme-color" content="#0b1726">

    <title>@yield('title', config('raniag.name')) — {{ config('raniag.organization') }}</title>

    {{-- Social preview --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', config('raniag.name')) — {{ config('raniag.organization') }}">
    <meta property="og:description" content="Report incidents fast, track them transparently.">
    <meta name="twitter:card" content="summary_large_image">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link rel="manifest" href="/manifest.json?v=8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" type="image/svg+xml" href="/images/icons/raniag-master.svg?v=8">
    <link rel="alternate icon" type="image/png" href="/favicon.png?v=8">
    <link rel="apple-touch-icon" href="/images/icons/icon-192.png?v=8">
    <script src="{{ asset('js/public-theme.js') }}?v={{ @filemtime(public_path('js/public-theme.js')) }}"></script>
    @stack('styles')
    <link href="{{ asset('css/public.css') }}?v={{ @filemtime(public_path('css/public.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/raniag-loader.css') }}?v={{ @filemtime(public_path('css/raniag-loader.css')) }}" rel="stylesheet">

    <style>
        /* ============================================================
           RANIAG public shell — design tokens
           ============================================================ */
        :root {
            --rg-ink:        #0b1726;
            --rg-ink-2:      #12263a;
            --rg-surface:    #eaf0f2;
            --rg-card:       #ffffff;
            --rg-line:       rgba(11, 23, 38, .13);
            --rg-text:       #17293a;
            --rg-muted:      #52677a;
            --rg-brand:      #086b78;
            --rg-brand-soft: #29aa96;
            --rg-brand-dark: #075761;
            --rg-brand-darker: #03434c;
            --rg-alert:      #a54b12;
            --rg-warning:    #a54b12;
            --rg-radius:     12px;
            --rg-shadow:     0 18px 44px -28px rgba(11,23,38,.48);
            --rg-shadow-sm:  0 7px 20px -14px rgba(11,23,38,.38);
            --rg-grad:       linear-gradient(135deg, var(--rg-brand), var(--rg-brand-soft));
            --rg-live:       #087f5b;
            --rg-caution:    #9a5b00;
            --rg-critical:   #a52b35;
            --rg-signal:     #48b9ef;
            --rg-gold:       #e9bd5c;

            /* Aliases: public.css (shared with the admin layout) is written
               against the --raniag-* token names. Map them here so those
               rules resolve on public pages instead of silently failing. */
            --raniag-primary:       var(--rg-brand);
            --raniag-primary-dark:  #084298;
            --raniag-primary-light: rgba(8,107,120,.12);
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
            background: var(--rg-grad); z-index: 1045; /* above the sticky navbar (1040), below Bootstrap's modal backdrop/modal (1050/1055) so it never sits in front of a dimmed page */
            transition: width .08s linear;
        }

        /* ---------- navbar ---------- */
        .raniag-navbar {
            background: rgba(11, 18, 32, .82);
            backdrop-filter: saturate(160%) blur(14px);
            -webkit-backdrop-filter: saturate(160%) blur(14px);
            border-bottom: 1px solid rgba(82,205,177,.42);
            position: sticky; top: 0; z-index: 1040;
            transition: background .25s ease, box-shadow .25s ease, border-color .25s ease;
        }
        .raniag-navbar .container { min-height:68px; }
        .raniag-navbar.is-scrolled { background: rgba(11,18,32,.96); box-shadow: 0 10px 30px -18px rgba(0,0,0,.8); }

        .raniag-navbar .navbar-brand { letter-spacing: -.02em; color: #fff; }
        .raniag-brand-icon {
            display: inline-grid; place-items: center;
            width: 38px; height: 38px; border-radius: 8px;
            background: #fff; overflow: hidden;
            color: #fff; font-size: 1.05rem;
            box-shadow: 0 8px 20px -10px rgba(0,0,0,.55);
        }
        .raniag-brand-sub {
            display: block; font-size: .66rem; font-weight: 500; line-height: 1;
            text-transform: uppercase; letter-spacing: .16em;
            color: rgba(255,255,255,.55); margin-top: 3px;
        }

        .raniag-navbar .nav-link {
            position: relative;
            color: rgba(255,255,255,.72) !important;
            font-weight: 600; font-size: .82rem; letter-spacing:.01em;
            padding: .5rem .65rem !important; border-radius: 4px;
            transition: color .18s ease, background .18s ease;
        }
        .raniag-navbar .nav-link:hover { color: #fff !important; background: rgba(255,255,255,.07); }
        .raniag-navbar .nav-link.active { color: #fff !important; }
        .raniag-navbar .nav-link.active::after {
            content: ''; position: absolute; left: .65rem; right: .65rem; bottom: .12rem;
            height: 2px; border-radius: 2px; background: #58d5b5;
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

        body.rg-scroll-locked { overflow: hidden; position: fixed; left: 0; right: 0; width: 100%; }

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
        .raniag-hero-photo { position: relative; min-height: 100%; overflow: hidden; }
        .raniag-hero-photo img {
            width: 100%; height: 100%; min-height: 340px;
            object-fit: cover; object-position: center 58%;
            display: block;
        }
        .raniag-hero-photo::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background: linear-gradient(90deg, rgba(11,18,32,.55), rgba(11,18,32,0) 34%);
        }
        @media (max-width: 991.98px) {
            .raniag-hero-photo { order: -1; }
            .raniag-hero-photo img { min-height: 220px; max-height: 260px; }
            .raniag-hero-photo::after { display: none; }
        }
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

        /* Progress tracker lives in public.css. Do not restyle it here:
           this block loads after that file and was keeping the old
           per-status colors on the public tracking page. */

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

        /* ============================================================
           Public emergency-operations identity
           ============================================================ */
        body.raniag-public {
            background:
                linear-gradient(rgba(16, 42, 57, .025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(16, 42, 57, .025) 1px, transparent 1px),
                #eaf0f2;
            background-size: 28px 28px, 28px 28px, auto;
        }
        .raniag-navbar {
            background: rgba(8, 23, 38, .97);
            border-bottom: 2px solid #1b716e;
            box-shadow: 0 8px 24px -18px rgba(4, 17, 29, .9);
        }
        .raniag-navbar .navbar-brand { font-size: .97rem; }
        .raniag-navbar .navbar-brand .raniag-brand-icon {
            border-radius: 8px;
            background: #d9efea;
            box-shadow: none;
        }
        .raniag-navbar .nav-link { font-size: .86rem; }
        .raniag-navbar .nav-link.active::after { background: var(--rg-gold); }
        .rg-strip {
            background: #071522;
            border-bottom: 1px solid rgba(255,255,255,.08);
            color: #c4d5df;
            font-size: .73rem;
            letter-spacing: .025em;
        }
        .rg-strip .rg-dot, .rg-status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            margin-right: .45rem;
            border-radius: 50%;
            background: #44d39b;
            box-shadow: 0 0 0 3px rgba(68,211,155,.15);
            vertical-align: 1px;
        }
        .rg-btn-report {
            background: #087b78;
            color: #ffffff !important;
            border-radius: 5px;
            box-shadow: none;
        }
        .rg-btn-report:hover, .rg-btn-report.active {
            background: #0a9187;
            color: #ffffff !important;
            box-shadow: none;
        }
        .rg-shell { overflow: clip; }
        .rg-shell::before, .rg-shell::after { display: none; }
        .rg-shell > * { position: relative; z-index: auto; }
        .raniag-hero {
            min-height: 400px;
            border: 0;
            border-radius: 8px;
            background: #0b1726;
            box-shadow: 0 22px 48px -28px rgba(4,17,29,.72);
        }
        .raniag-hero::after {
            opacity: .7;
            background-image:
                linear-gradient(rgba(158,207,219,.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(158,207,219,.08) 1px, transparent 1px);
            background-size: 32px 32px;
            mask-image: linear-gradient(110deg, #000 0%, transparent 72%);
        }
        .raniag-hero .row > .col-lg-7 { padding-top: clamp(2rem,5vw,4rem) !important; padding-bottom: clamp(2rem,5vw,4rem) !important; }
        .raniag-hero [data-rg-hero-eyebrow] {
            color: #9ab3bf !important;
            letter-spacing: .17em;
            font-size: .68rem !important;
        }
        .raniag-hero [data-rg-hero-title] {
            max-width: 12ch;
            font-size: clamp(2.4rem, 5.5vw, 4.4rem);
            line-height: .98;
            letter-spacing: -.055em;
        }
        .raniag-hero .rg-tagline { color: var(--rg-gold); font-size: .82rem; letter-spacing: .1em; text-transform: uppercase; }
        .raniag-hero [data-rg-hero-desc] { max-width: 45ch; font-size: 1rem; line-height: 1.65; color: #c2d0d8 !important; }
        .raniag-hero .btn {
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            border-radius: 5px;
            font-size: .9rem;
        }
        .raniag-hero .btn-light { border-color: var(--rg-gold); background: var(--rg-gold); color: #17293a; }
        .raniag-hero .btn-outline-light { color: #e4eef2; border-color: rgba(228,238,242,.38); }
        .raniag-hero .btn-outline-light:hover { color: #0b1726; background: #e4eef2; }
        .raniag-hero-photo { min-height: 400px; }
        .raniag-hero-photo img { min-height: 400px; filter: saturate(.72) contrast(1.06); }
        .raniag-hero-photo::after {
            display: block;
            background: linear-gradient(90deg, rgba(11,23,38,.52), rgba(11,23,38,.02) 66%), linear-gradient(0deg, rgba(11,23,38,.5), transparent 56%);
        }
        .raniag-hero-photo::before {
            content: 'PAMPLONA  /  MDRRMO';
            position: absolute;
            z-index: 2;
            left: 1.25rem;
            bottom: 1.1rem;
            padding: .45rem .65rem;
            border: 1px solid rgba(255,255,255,.34);
            background: rgba(8,23,38,.72);
            color: #f2f6f7;
            font: 700 .63rem/1.2 ui-monospace, SFMono-Regular, Menlo, monospace;
            letter-spacing: .14em;
        }
        .rg-home-situation {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin: -1.1rem 1rem 1.65rem;
            position: relative;
            z-index: 2;
            border: 1px solid #cad6da;
            border-radius: 6px;
            background: #fff;
            box-shadow: 0 14px 30px -24px rgba(11,23,38,.62);
        }
        .rg-home-situation-link {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: .8rem;
            padding: 1rem 1.1rem;
            color: var(--rg-text);
            text-decoration: none;
            transition: background .18s ease;
        }
        .rg-home-situation-link + .rg-home-situation-link { border-left: 1px solid #dbe3e5; }
        .rg-home-situation-link:hover { background: #f1f7f6; color: var(--rg-ink); }
        .rg-home-situation-icon {
            display: grid;
            place-items: center;
            flex: 0 0 38px;
            width: 38px;
            height: 38px;
            border-radius: 5px;
            background: #e7f3f1;
            color: #086b78;
            font-size: 1rem;
        }
        .rg-home-situation-copy { display: grid; gap: .16rem; min-width: 0; }
        .rg-home-situation-copy strong { font-size: .84rem; line-height: 1.35; }
        .rg-home-situation-kicker {
            color: #5c7180;
            font: 700 .61rem/1.2 ui-monospace, SFMono-Regular, Menlo, monospace;
            letter-spacing: .1em;
            text-transform: uppercase;
        }
        .rg-home-situation-kicker .rg-status-dot { width: 6px; height: 6px; margin-right: .3rem; }
        .rg-home-situation-arrow { margin-left: auto; color: #8296a2; font-size: .85rem; }
        .rg-desk {
            border: 1px solid #20384c;
            border-radius: 7px;
            background: linear-gradient(115deg, #12283b 0%, #0c1c2b 65%, #102638 100%);
            box-shadow: 0 18px 40px -28px rgba(8,23,38,.72);
        }
        .rg-desk-kicker, .rg-desk-rail li:first-child i { color: #e9bd5c; }
        .rg-desk-rail::before { background: rgba(167,198,207,.22); }
        .rg-desk-rail::after { background: #48d5a2; box-shadow: 0 0 12px rgba(72,213,162,.78); }
        .rg-desk-rail i { border-radius: 5px; background: #1a374a; border-color: #385467; color: #a7d8df; }
        .rg-page-title { letter-spacing: -.04em; }
        .rg-eyebrow {
            border-radius: 4px;
            color: #075b64;
            background: #e0efec;
            border-color: #c1dfda;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            letter-spacing: .11em;
        }
        .raniag-card, .card.raniag-card {
            border-radius: 7px;
            box-shadow: 0 14px 32px -27px rgba(11,23,38,.56);
        }
        .raniag-card-header, .card-header.raniag-card-header {
            background: #f2f6f6;
            border-radius: 7px 7px 0 0 !important;
        }
        .raniag-step-badge { border-radius: 5px; background: #086b78; box-shadow: none; }
        .btn-primary, .btn-success, .btn-outline-primary, .btn-outline-secondary, .btn-light, .btn-outline-light, .btn-danger {
            border-radius: 5px;
        }
        .btn-primary, .btn-success {
            --bs-btn-bg: #086b78;
            --bs-btn-border-color: #086b78;
            --bs-btn-hover-bg: #075761;
            --bs-btn-hover-border-color: #075761;
            --bs-btn-active-bg: #03434c;
            --bs-btn-active-border-color: #03434c;
            --bs-btn-disabled-bg: #086b78;
            --bs-btn-disabled-border-color: #086b78;
            box-shadow: none;
        }
        .form-control, .form-select { border-radius: 5px; }
        .form-control:focus, .form-select:focus, .form-check-input:focus {
            border-color: #087d84;
            box-shadow: 0 0 0 3px rgba(8,107,120,.19);
        }
        .form-label { color: #203749; }
        .rg-faq-accordion .accordion-item { border-radius: 6px !important; }
        .rg-faq-accordion .accordion-button:not(.collapsed) { background: #e8f2f0; color: #075b64; }
        .rg-live-pill { color: #07634b; background: #def4eb; border-color: #9fd8c2; }
        .rg-live-pill::before { background: #087f5b; }
        .rg-hazard-stage {
            min-height: 560px;
            border: 1px solid #183244;
            border-radius: 7px;
            box-shadow: 0 18px 36px -28px rgba(11,23,38,.66);
        }
        #hazard-map { height: min(76vh, 740px); min-height: 560px; }
        .rg-hazard-panel {
            height: min(76vh, 740px);
            padding: 14px;
            border: 1px solid #20394d;
            border-radius: 7px;
            background: #102338;
            color: #e7f0f2;
            box-shadow: 0 18px 36px -28px rgba(11,23,38,.66);
        }
        .rg-hazard-panel .text-muted, .rg-hazard-panel .small.text-muted,
        .rg-hazard-panel .rg-hazard-legend span { color: #a8bdc7 !important; }
        .rg-hazard-panel h2, .rg-hazard-panel .fw-semibold { color: #f0f5f6; }
        .rg-hazard-panel-head { padding-bottom: .55rem; border-bottom: 1px solid #294257; }
        .rg-hazard-panel-head .small[style] { color: #79d8c2 !important; font-family: ui-monospace, monospace; text-transform: uppercase; letter-spacing: .08em; }
        .rg-hazard-chip, .rg-hazard-tab {
            color: #d3e2e6;
            border-color: #3a5367;
            background: #162e43;
            border-radius: 4px;
        }
        .btn-check:checked + .rg-hazard-chip, .rg-hazard-tab.is-active {
            color: #effbf8;
            background: #11685f;
            border-color: #3cc6a1;
        }
        .rg-hazard-tabs { border-bottom: 1px solid #294257; padding-bottom: 8px; }
        .rg-hazard-nearest, .rg-hazard-route { color: #e2ecef; background: #162e43; border-color: #365064 !important; }
        .rg-hazard-list-item { color: #e2ecef; }
        .rg-hazard-list-item:hover, .rg-hazard-list-item.is-active { background: #183b4c; border-color: #367b7b; }
        .rg-hazard-locate { border-color: #4bc6a8; border-radius: 4px; color: #075b52; }
        .rg-case {
            border: 1px solid #20384c;
            border-radius: 7px;
            background: #0e2032;
            box-shadow: 0 22px 50px -30px rgba(8,23,38,.82);
        }
        .rg-case-board { background: radial-gradient(circle at 12% 8%, rgba(45,152,151,.22), transparent 34%), #0e2032; }
        .rg-case-ticket { border-radius: 5px; }
        .rg-case-ticket button { border-radius: 5px; background: #086b78; }
        .rg-case-ticket button:hover { background: #075761; }
        .rg-case-path i { border-radius: 5px; }
        .rg-community { max-width: 1240px; }
        .rg-community-head { margin-bottom: 20px; }
        .rg-community-head h1 { color: #102638; font-size: clamp(1.9rem,3.6vw,3.15rem); letter-spacing: -.055em; }
        .rg-kpi-row { grid-template-columns: repeat(4, minmax(0,1fr)); gap: 10px; }
        .rg-kpi, .rg-situation-card, .rg-active-callout {
            border-color: #cbd8dc;
            border-radius: 7px;
            box-shadow: 0 12px 28px -24px rgba(11,23,38,.55);
        }
        .rg-kpi { padding: 16px; }
        .rg-kpi-open { background: linear-gradient(135deg, #0c514f, #0b373d); }
        .rg-kpi:not(.rg-kpi-open) { border-top: 3px solid #236f76; }
        .rg-kpi strong { font: 700 2.4rem/1 ui-monospace, SFMono-Regular, Menlo, monospace; }
        .rg-community-label { color: #236f76; font-family: ui-monospace, monospace; letter-spacing: .09em; }
        .rg-kpi-open .rg-community-label { color: #9ae4ce; }
        .rg-situation-card { padding: 18px; }
        .rg-situation-card h2 { color: #132b3d; font-size: .98rem; }
        .rg-card-head { border-bottom: 1px solid #e2e9e9; padding-bottom: 12px; margin-bottom: 14px; }
        .rg-period-switch { border-radius: 5px; }
        .rg-period-switch button, .rg-period-switch button.is-on { border-radius: 3px; }
        .rg-period-switch button.is-on { background: #086b78; }
        .rg-risk-pill, .rg-repeat-band { border-radius: 4px; }
        .rg-risk-pill.low, .band-low { background: #d8f2e8; color: #07583e; }
        .rg-risk-pill.watch, .band-watch { background: #fff0c9; color: #704500; }
        .rg-risk-pill.elevated, .band-elevated { background: #ffe2c2; color: #803c07; }
        .rg-risk-pill.high, .band-high { background: #f9d7d9; color: #79222a; }
        .rg-community-updated { font-family: ui-monospace, monospace; }
        .rg-stepper { border: 1px solid #cbd8dc; border-radius: 7px; background: #fff; }
        .rg-stepper-node { border-radius: 5px; }
        .rg-stepper-item.is-current .rg-stepper-node { background: #086b78; border-color: #086b78; }
        .rg-stepper-item.is-done .rg-stepper-node { background: #087f5b; border-color: #087f5b; }
        .rg-wizard-dock { border: 1px solid #cbd8dc; border-radius: 7px; }
        .rg-field-feedback { display: block; margin-top: .35rem; color: #a52b35; font-size: .82rem; font-weight: 600; }
        .is-invalid:focus { border-color: #a52b35; box-shadow: 0 0 0 3px rgba(165,43,53,.14); }
        .raniag-footer {
            background:
                linear-gradient(90deg, rgba(53,122,128,.13) 1px, transparent 1px),
                #091927;
            background-size: 30px 30px, auto;
            border-top: 3px solid #1d716c;
        }
        .raniag-footer .raniag-brand-icon { border-radius: 6px; }
        :focus-visible { outline: 3px solid #147b86; outline-offset: 3px; }
        .raniag-navbar :focus-visible, .raniag-footer :focus-visible, .rg-case-board :focus-visible {
            outline-color: #f0c96d;
        }
        @media (max-width: 991.98px) {
            .rg-home-situation { margin: -1rem .6rem 1.4rem; }
            #hazard-map, .rg-hazard-stage { min-height: 330px; }
            .rg-hazard-panel { height: auto; }
            .rg-hazard-pane { max-height: 300px; }
        }
        @media (max-width: 767.98px) {
            .raniag-hero { min-height: 0; }
            .raniag-hero-photo, .raniag-hero-photo img { min-height: 235px; max-height: 270px; }
            .rg-home-situation { grid-template-columns: 1fr; margin: -.7rem .45rem 1.3rem; }
            .rg-home-situation-link { padding: .8rem .9rem; }
            .rg-home-situation-link + .rg-home-situation-link { border-left: 0; border-top: 1px solid #dbe3e5; }
            .rg-kpi-row { grid-template-columns: 1fr 1fr; }
            .rg-kpi-open { grid-column: 1 / -1; }
            .rg-community-grid { grid-template-columns: 1fr; }
            .rg-community-head .btn { width: 100%; justify-content: center; }
            .raniag-hero .d-flex.gap-3 > .btn { flex: 1 1 100%; justify-content: center; }
        }
        @media (max-width: 420px) {
            .rg-kpi-row { gap: 8px; }
            .rg-kpi { padding: 13px; }
            .rg-kpi strong { font-size: 1.8rem; }
            .rg-community-label { font-size: .61rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .rg-status-dot, .rg-live-pill::before { animation: none !important; }
        }
        .rg-theme-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            min-height: 38px;
            padding: .45rem .72rem;
            border: 1px solid rgba(255,255,255,.24);
            border-radius: 4px;
            background: transparent;
            color: #e4eff1;
            font-size: .76rem;
            font-weight: 700;
        }
        .rg-theme-toggle:hover { border-color: #65cdb3; color: #7de0c3; }
        .rg-theme-toggle .bi { font-size: .9rem; }
        .rg-theme-toggle--mobile { display:none; flex:0 0 auto; white-space:nowrap; }
        .rg-nav-actions { display:flex;align-items:center;gap:.55rem; }
        .rg-strip-separator { padding: 0 .35rem; color: #e9bd5c; }
        .rg-strip-location { color: #cfdee2; }
        body.raniag-public #rg-main { transition: opacity .14s ease, transform .14s ease; }
        body.rg-page-arriving #rg-main { animation: rg-page-arrive .3s cubic-bezier(.2,.75,.25,1) both; }
        body.rg-page-leaving #rg-main { opacity:.12; transform:translateY(5px); }
        @keyframes rg-page-arrive {
            from { opacity:0; transform:translateY(8px); }
            to { opacity:1; transform:translateY(0); }
        }
        body.rg-page-leaving::after {
            content:"";
            position:fixed;
            z-index:2000;
            inset:0 0 auto;
            height:3px;
            background:linear-gradient(90deg,#e9bd5c,#4bc5ad);
            transform-origin:left;
            animation:rg-page-progress .14s ease-out both;
            pointer-events:none;
        }
        @keyframes rg-page-progress { from { transform:scaleX(0); } to { transform:scaleX(1); } }
        @media (prefers-reduced-motion: reduce) {
            body.raniag-public #rg-main { transition:none; }
            body.rg-page-arriving #rg-main { animation:none; }
            body.rg-page-leaving #rg-main { opacity:1; transform:none; }
            body.rg-page-leaving::after { animation:none; }
        }
        .rg-help-fab {
            position: fixed !important;
            inset: auto max(20px, env(safe-area-inset-right, 0px)) max(20px, env(safe-area-inset-bottom, 0px)) auto !important;
            z-index: 1085 !important;
            width: auto;
            height: 54px;
            gap: 9px;
            padding: 0 16px;
            border: 1px solid rgba(255,255,255,.6);
            border-radius: 999px;
            background: #087b78;
            color: #fff;
            box-shadow: 0 10px 30px rgba(7,31,40,.3);
            animation: none;
        }
        .rg-help-fab:hover { background: #075f60; color: #fff; }
        .rg-help-fab__label { position:static;right:auto;opacity:1;background:transparent;border-radius:0;padding:0;color:inherit; }
        body[data-jo-page="report"] .rg-help-fab {
            bottom: max(92px, calc(env(safe-area-inset-bottom, 0px) + 82px)) !important;
        }
        html[data-public-theme="dark"] {
            color-scheme: dark;
            --bs-primary: #48c8b3;
            --bs-primary-rgb: 72, 200, 179;
            --bs-link-color: #78dfcf;
            --bs-link-hover-color: #b2f5e8;
            --bs-info: #66cfff;
            --bs-info-rgb: 102, 207, 255;
            --rg-ink: #eef4f3;
            --rg-ink-2: #dce8e8;
            --rg-surface: #0c1923;
            --rg-card: #142630;
            --rg-line: rgba(204,226,228,.15);
            --rg-text: #e3ecee;
            --rg-muted: #a8bbc2;
            --rg-brand: #42c3ad;
            --rg-brand-soft: #66d6c1;
            --rg-brand-dark: #168d83;
            --rg-brand-darker: #0c6e69;
            --raniag-primary: #39b8a5;
            --raniag-primary-dark: #65d6c4;
            --raniag-primary-light: rgba(57,184,165,.17);
            --raniag-accent: #70cbbb;
            --raniag-accent-dark: #65d6c4;
            --raniag-surface: #0c1923;
            --raniag-border: rgba(204,226,228,.16);
            --raniag-card-shadow: 0 12px 30px rgba(0,0,0,.2);
        }
        html[data-public-theme="dark"] body,
        html[data-public-theme="dark"] .modal-content { color-scheme: dark; }
        html[data-public-theme="dark"] body.raniag-public {
            background:
                linear-gradient(rgba(127,176,185,.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(127,176,185,.035) 1px, transparent 1px),
                #0c1923;
            color: #e3ecee;
        }
        html[data-public-theme="dark"] .rg-shell { color: #e3ecee; }
        html[data-public-theme="dark"] #rg-main .card,
        html[data-public-theme="dark"] #rg-main .card.raniag-card,
        html[data-public-theme="dark"] #rg-main .rg-app-card {
            background-color: #142630 !important;
            border-color: #304955 !important;
            color: #e3ecee !important;
        }
        html[data-public-theme="dark"] #rg-main .card .card-header,
        html[data-public-theme="dark"] #rg-main .card .raniag-card-header,
        html[data-public-theme="dark"] #rg-main .rg-app-card .card-header {
            background: #1b333f !important;
            border-color: #38525e !important;
            color: #e8f0f0 !important;
        }
        html[data-public-theme="dark"] #rg-main .card .card-header .text-primary,
        html[data-public-theme="dark"] #rg-main .rg-app-card .card-header .text-primary { color: #78dfcf !important; }
        html[data-public-theme="dark"] #rg-main .card .card-header .bi:not(.raniag-step-badge .bi),
        html[data-public-theme="dark"] #rg-main .rg-app-card .card-header .bi:not(.raniag-step-badge .bi) { color: #78dfcf; }
        html[data-public-theme="dark"] #rg-main .card .card-body,
        html[data-public-theme="dark"] #rg-main .rg-app-card .card-body { color: #dce8e8; }
        html[data-public-theme="dark"] #rg-main .card .card-footer,
        html[data-public-theme="dark"] #rg-main .rg-app-card .card-footer {
            background: #10232d !important;
            border-color: #304955 !important;
            color: #b8cbd0 !important;
        }
        html[data-public-theme="dark"] #rg-main a:not(.btn):not(.nav-link):not(.rg-home-primary-action):not(.rg-home-secondary-action) { color: #78dfcf; }
        html[data-public-theme="dark"] #rg-main .rg-home-secondary-action { color: #e9eff0; }
        html[data-public-theme="dark"] #rg-main .text-primary,
        html[data-public-theme="dark"] #rg-main .link-primary,
        html[data-public-theme="dark"] #rg-main .btn-link { color: #78dfcf !important; }
        html[data-public-theme="dark"] #rg-main .btn-primary {
            background: #66d6c1;
            border-color: #66d6c1;
            color: #092723;
        }
        html[data-public-theme="dark"] #rg-main .btn-primary:hover,
        html[data-public-theme="dark"] #rg-main .btn-primary:focus-visible {
            background: #a2ecdc;
            border-color: #a2ecdc;
            color: #092723;
        }
        html[data-public-theme="dark"] #rg-main .btn-primary:disabled { background: #456c69; border-color: #456c69; color: #edf4f3; }
        html[data-public-theme="dark"] .rg-page-title,
        html[data-public-theme="dark"] .rg-faq-accordion .accordion-button,
        html[data-public-theme="dark"] .form-label,
        html[data-public-theme="dark"] .rg-community-head h1,
        html[data-public-theme="dark"] .rg-situation-card h2 { color: #e8f0f0; }
        html[data-public-theme="dark"] .rg-page-sub,
        html[data-public-theme="dark"] .rg-community-head p,
        html[data-public-theme="dark"] .text-muted,
        html[data-public-theme="dark"] .form-text,
        html[data-public-theme="dark"] .rg-privacy-copy,
        html[data-public-theme="dark"] .rg-community-updated { color: #a9bdc3 !important; }
        html[data-public-theme="dark"] .raniag-card,
        html[data-public-theme="dark"] .card.raniag-card,
        html[data-public-theme="dark"] .rg-support-card,
        html[data-public-theme="dark"] .rg-stepper,
        html[data-public-theme="dark"] .rg-wizard-dock,
        html[data-public-theme="dark"] .rg-kpi:not(.rg-kpi-open),
        html[data-public-theme="dark"] .rg-situation-card,
        html[data-public-theme="dark"] .rg-active-callout,
        html[data-public-theme="dark"] .rg-case-ticket,
        html[data-public-theme="dark"] .rg-announce-card {
            background-color: #142630;
            border-color: #304955;
            color: #e2ecee;
        }
        html[data-public-theme="dark"] .raniag-card-header,
        html[data-public-theme="dark"] .card-header.raniag-card-header {
            background: #1b333f;
            border-color: #304955;
            color: #e8f0f0;
        }
        html[data-public-theme="dark"] .raniag-type-card {
            background: #172d38;
            border-color: #3a5661;
            color: #e3ecee;
        }
        html[data-public-theme="dark"] .raniag-type-card.selected,
        html[data-public-theme="dark"] .raniag-type-card:has(input:checked) {
            background: #163f43;
            border-color: #43c3aa;
        }
        html[data-public-theme="dark"] .form-control,
        html[data-public-theme="dark"] .form-select {
            background-color: #10232d;
            border-color: #47606b;
            color: #edf4f4;
        }
        html[data-public-theme="dark"] .form-control::placeholder { color: #9aacb2; }
        html[data-public-theme="dark"] .form-control[readonly] { background-color: #1b303a; }
        html[data-public-theme="dark"] .form-check-label { color: #dce8e8; }
        html[data-public-theme="dark"] .form-check-input { background-color: #233b46; border-color: #6d8790; }
        html[data-public-theme="dark"] .form-check-input:checked { background-color: #087b78; border-color: #087b78; }
        html[data-public-theme="dark"] .accordion-item,
        html[data-public-theme="dark"] .rg-faq-accordion .accordion-item {
            background: #142630;
            border-color: #304955;
        }
        html[data-public-theme="dark"] .rg-faq-accordion .accordion-button {
            background: #142630;
            color: #e3ecee;
        }
        html[data-public-theme="dark"] .rg-faq-accordion .accordion-button:not(.collapsed) {
            background: #1a383d;
            color: #8be0d0;
        }
        html[data-public-theme="dark"] .accordion-body { color: #bbcbcf; }
        html[data-public-theme="dark"] .rg-code-panel { background: #183542; border-color: #39776f; }
        html[data-public-theme="dark"] .raniag-tracking-number { color: #edfaf7; }
        html[data-public-theme="dark"] .rg-case-ticket {
            background: radial-gradient(circle at 0 18px, transparent 8px, #142630 9px) left center / 18px 28px repeat-y, #142630;
            color: #e6eeee;
        }
        html[data-public-theme="dark"] .rg-case-ticket label,
        html[data-public-theme="dark"] .rg-case-hint,
        html[data-public-theme="dark"] .rg-case-lost { color: #a9bdc3; }
        html[data-public-theme="dark"] .rg-case-ticket input {
            border-bottom-color: #bdd0d3;
            color: #f0f6f5;
        }
        html[data-public-theme="dark"] .rg-kpi strong,
        html[data-public-theme="dark"] .rg-active-callout strong,
        html[data-public-theme="dark"] .rg-repeat-row strong { color: #e8f2f1; }
        html[data-public-theme="dark"] .rg-card-head { border-color: #304955; }
        html[data-public-theme="dark"] .rg-rank-track,
        html[data-public-theme="dark"] .rg-period-switch { background: #203640; border-color: #405862; }
        html[data-public-theme="dark"] .rg-place-row,
        html[data-public-theme="dark"] .rg-repeat-row,
        html[data-public-theme="dark"] .rg-activity-item { border-color: #304955; }
        html[data-public-theme="dark"] .rg-activity-icon { background: #203640; }
        html[data-public-theme="dark"] .rg-period-switch button { color: #ccdcdf; }
        html[data-public-theme="dark"] .rg-period-switch button.is-on { color: #fff; }
        html[data-public-theme="dark"] .rg-hazard-panel { background: #102338; }
        html[data-public-theme="dark"] .rg-home-shortcuts { border-color: #38525e; }
        html[data-public-theme="dark"] .rg-home-shortcuts>a { color: #e4efef; }
        html[data-public-theme="dark"] .rg-home-shortcuts>a+a { border-color: #38525e; }
        html[data-public-theme="dark"] .rg-home-shortcuts>a:hover { background: #18363c; color: #93e6d6; }
        html[data-public-theme="dark"] .rg-home-shortcuts small,
        html[data-public-theme="dark"] .rg-home-shortcuts .rg-shortcut-arrow { color: #a9bdc3; }
        html[data-public-theme="dark"] .rg-journey-heading h2 { color: #edf4f3; }
        html[data-public-theme="dark"] .rg-journey-heading>p:last-child,
        html[data-public-theme="dark"] .rg-journey-step>div>span { color: #adc0c5; }
        html[data-public-theme="dark"] .rg-section-index,
        html[data-public-theme="dark"] .rg-journey-step-no { color: #a1b5bb; }
        html[data-public-theme="dark"] .rg-journey-step,
        html[data-public-theme="dark"] .rg-journey-step:last-child { border-color: #38525e; }
        html[data-public-theme="dark"] .rg-journey-step h3 { color: #edf5f4; }
        html[data-public-theme="dark"] .rg-journey-step a,
        html[data-public-theme="dark"] .rg-journey-step p { color: #70d5c1; }
        html[data-public-theme="dark"] .rg-journey-tour { border-color: #45616b; color: #e4efef; }
        html[data-public-theme="dark"] .rg-journey-tour span { border-color: #45616b; color: #afc3c8; }
        html[data-public-theme="dark"] .rg-log-item h3 { color: #e8f1f1; }
        html[data-public-theme="dark"] .rg-log-meta,
        html[data-public-theme="dark"] .rg-log-item p { color: #adc0c5; }
        html[data-public-theme="dark"] .rg-announce-badge { background: #183c42; color: #80ddcb; }
        html[data-public-theme="dark"] .rg-community-label { color: #80ddcb; }
        html[data-public-theme="dark"] .rg-community-label.rg-kpi-open { color: #a5ebd9; }
        html[data-public-theme="dark"] .rg-privacy-note { background: #163c35; color: #8be0c2; }
        html[data-public-theme="dark"] .rg-hazard-list-item { color: #e2ecef; }
        html[data-public-theme="dark"] .btn-outline-primary {
            --bs-btn-color: #84dfd0;
            --bs-btn-border-color: #4c9e96;
            --bs-btn-hover-color: #092421;
            --bs-btn-hover-bg: #72d7c2;
            --bs-btn-hover-border-color: #72d7c2;
        }
        html[data-public-theme="dark"] .rg-info-box { background: #382e19; border-color: #745b2c; color: #f4e7bb; }
        html[data-public-theme="dark"] .rg-lock-note { background: #382e19; border-color: #745b2c; color: #f4e7bb; }
        @media (max-width: 1199.98px) {
            .raniag-navbar .navbar-collapse { padding: .6rem 0 1rem; }
            .rg-theme-toggle--mobile { display:inline-flex; width:auto; justify-content:center; margin:0; }
            .rg-theme-toggle:not(.rg-theme-toggle--mobile) { display:none; }
        }
        @media (max-width: 991.98px) {
            .rg-home-hero { margin-inline:-1rem; }
        }
        @media (max-width: 991.98px) {
            .rg-help-fab { width:54px;height:54px;right:max(15px,env(safe-area-inset-right,0px)) !important;padding:0;justify-content:center; }
            .rg-help-fab__label { position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap; }
        }
        @media (max-width: 420px) {
            .rg-theme-toggle--mobile { width:38px;height:38px;min-height:38px;padding:0; }
            .rg-theme-toggle--mobile [data-rg-theme-label] { position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap; }
        }
    </style>
</head>
<body class="raniag-public d-flex flex-column min-vh-100" @php
    $joPage = match (true) {
        request()->routeIs('public.report.*') => 'report',
        request()->routeIs('public.hazard.*') => 'hazard',
        request()->routeIs('public.track*') => 'track',
        request()->routeIs('public.support') => 'support',
        request()->routeIs('public.dashboard') => 'dashboard',
        default => 'home',
    };
@endphp data-jo-page="{{ $joPage }}">
    <div id="rg-progress"></div>

    <a href="#rg-main" class="visually-hidden-focusable position-absolute top-0 start-0 m-2 btn btn-light btn-sm">Skip to content</a>

    {{-- ================= top strip ================= --}}
    <div class="rg-strip py-2 d-none d-md-block">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><span class="rg-dot"></span>RANIAG <span class="rg-strip-separator">/</span> PUBLIC REPORTING</span>
            <span class="rg-strip-location"><i class="bi bi-geo-alt me-1"></i>Pamplona, Cagayan</span>
        </div>
    </div>

    {{-- ================= navbar ================= --}}
    <nav class="navbar navbar-expand-xl navbar-dark raniag-navbar" id="rg-nav">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('public.home') }}">
                <span class="raniag-brand-icon"><img src="/images/icons/raniag-master.svg" alt="RANIAG" class="w-100 h-100" style="object-fit:contain;"></span>
                <span class="d-flex flex-column lh-1">
                    {{ config('raniag.name') }}
                    <span class="raniag-brand-sub">{{ config('raniag.organization') }}</span>
                </span>
            </a>

            <div class="rg-nav-actions">
                <button type="button" class="rg-theme-toggle rg-theme-toggle--mobile" data-rg-theme-toggle aria-label="Switch to dark mode" aria-pressed="false">
                    <i class="bi bi-moon-stars" aria-hidden="true"></i><span data-rg-theme-label>Light mode</span>
                </button>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav"
                        aria-controls="publicNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="publicNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.home') ? 'active' : '' }}"
                           data-rg-tour="home"
                           href="{{ route('public.home') }}">
                            <i class="bi bi-house-door me-1 d-lg-none"></i>{{ __('Home') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.track*') ? 'active' : '' }}"
                           data-rg-tour="track"
                           href="{{ route('public.track') }}">
                            <i class="bi bi-search me-1 d-lg-none"></i>{{ __('Track Report') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.hazard.*') ? 'active' : '' }}"
                           data-rg-tour="hazard"
                           href="{{ route('public.hazard.map') }}">
                            <i class="bi bi-map me-1 d-lg-none"></i>Live Map
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.dashboard') ? 'active' : '' }}"
                           data-rg-tour="dashboard"
                           href="{{ route('public.dashboard') }}">
                            <i class="bi bi-bar-chart-line me-1 d-lg-none"></i>{{ __('Community Dashboard') }}
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
                                <i class="bi bi-person-badge me-1"></i>{{ __('Staff Login') }}
                            </a>
                        </li>
                    @endauth
                    <li class="nav-item">
                        <button type="button" class="rg-theme-toggle" data-rg-theme-toggle aria-label="Switch to dark mode" aria-pressed="false">
                            <i class="bi bi-moon-stars" aria-hidden="true"></i><span data-rg-theme-label>Light mode</span>
                        </button>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="nav-link rg-btn-report {{ request()->routeIs('public.report.*') ? 'active' : '' }}"
                           data-rg-tour="report"
                           href="{{ route('public.report.create') }}">
                            <i class="bi bi-megaphone-fill me-1"></i>{{ __('Report an Incident') }}
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
                        <span class="raniag-brand-icon"><img src="/images/icons/raniag-master.svg" alt="RANIAG" class="w-100 h-100" style="object-fit:contain;"></span>
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
                        <li><a href="#" id="jo-replay-tour">Ask JO again</a></li>
                        <li><a href="{{ route('public.support') }}">Support center</a></li>
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
    <x-loading-overlay />

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="{{ asset('js/live-refresh.js') }}?v={{ @filemtime(public_path('js/live-refresh.js')) }}"></script>
    <script src="{{ asset('js/public-page-transition.js') }}?v={{ @filemtime(public_path('js/public-page-transition.js')) }}"></script>
    @if ($joPage === 'home')
        <script src="{{ asset('js/public-home-pointer.js') }}?v={{ @filemtime(public_path('js/public-home-pointer.js')) }}"></script>
    @endif
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
    window.showLoadingOverlay = showLoadingOverlay;
    window.hideLoadingOverlay = hideLoadingOverlay;

    // A page can be restored from the browser's back/forward cache with
    // whatever overlay state it had at the moment of navigating away (e.g.
    // a form that showed the overlay right before submitting). Without
    // this, going Back after a submit can leave the overlay stuck forever
    // with no way to interact with the page again.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            hideLoadingOverlay();
        }
    });

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
        const journey = document.querySelector('[data-rg-journey]');

        function setJourneyStep(index, animate = false) {
            if (!journey) return;
            const steps = Array.from(journey.querySelectorAll('[data-journey-step]'));
            const step = steps[index];
            if (!step) return;
            steps.forEach((item, itemIndex) => {
                item.classList.toggle('is-active', itemIndex === index);
                if (itemIndex === index) item.setAttribute('aria-current', 'step');
                else item.removeAttribute('aria-current');
            });
            const counter = document.getElementById('rg-journey-counter');
            const status = document.getElementById('rg-journey-status');
            const mascot = document.getElementById('rg-journey-jo');
            const caption = document.getElementById('rg-journey-jo-line');
            if (counter) counter.textContent = `${String(index + 1).padStart(2, '0')} / ${String(steps.length).padStart(2, '0')}`;
            if (status) status.textContent = step.dataset.status || '';
            if (mascot && mascot.getAttribute('src') !== step.dataset.jo) {
                if (animate && typeof gsap !== 'undefined') {
                    gsap.to(mascot, {
                        autoAlpha: 0,
                        y: 12,
                        duration: 0.18,
                        onComplete: () => {
                            mascot.src = step.dataset.jo || mascot.src;
                            gsap.to(mascot, { autoAlpha: 1, y: 0, duration: 0.35, ease: 'power2.out' });
                        },
                    });
                } else {
                    mascot.src = step.dataset.jo || mascot.src;
                }
            }
            if (caption) {
                if (animate && typeof gsap !== 'undefined') {
                    gsap.fromTo(caption, { autoAlpha: 0, y: 8 }, {
                        autoAlpha: 1, y: 0, duration: 0.38, ease: 'power2.out', overwrite: true,
                    });
                }
                caption.textContent = step.dataset.line || '';
            }
        }

        // ------ mark all reveal elements as gsap-ready (removes CSS opacity:0) ------
        document.querySelectorAll('[data-rg-reveal]').forEach(el => el.classList.add('gsap-ready'));

        if (prefersReduced || typeof gsap === 'undefined' || typeof Lenis === 'undefined' || typeof ScrollTrigger === 'undefined') {
            if (journey) {
                const steps = Array.from(journey.querySelectorAll('[data-journey-step]'));
                setJourneyStep(0);
                if ('IntersectionObserver' in window) {
                    const observer = new IntersectionObserver((entries) => {
                        const current = entries
                            .filter(entry => entry.isIntersecting)
                            .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                        if (current) setJourneyStep(Number(current.target.dataset.journeyStep));
                    }, { rootMargin: '-38% 0px -38% 0px', threshold: [0, .15, .35, .6] });
                    steps.forEach(step => observer.observe(step));
                }
            }
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
        if (journey) {
            const steps = Array.from(journey.querySelectorAll('[data-journey-step]'));
            setJourneyStep(0);
            steps.forEach((step, index) => {
                ScrollTrigger.create({
                    trigger: step,
                    start: 'top 56%',
                    end: 'bottom 44%',
                    onEnter: () => setJourneyStep(index, true),
                    onEnterBack: () => setJourneyStep(index, true),
                });
            });
            const route = journey.querySelector('.rg-map-route');
            if (route) {
                gsap.fromTo(route, { scaleX: 0.25, transformOrigin: 'left center' }, {
                    scaleX: 1,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: journey,
                        start: 'top 70%',
                        end: 'bottom 25%',
                        scrub: 0.8,
                    },
                });
            }
        }
        lenis.on('scroll', ScrollTrigger.update);
        window.addEventListener('scroll', ScrollTrigger.update, { passive: true });

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

        // ------ 10. Footer columns stagger ------
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
            // Keep the full motion system on mobile too. It is still loaded
            // after the first screen and during idle time, so it does not
            // block the initial render.
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
    <script src="{{ asset('js/public-guide.js') }}?v={{ @filemtime(public_path('js/public-guide.js')) }}"></script>
    @stack('scripts')
</body>
</html>

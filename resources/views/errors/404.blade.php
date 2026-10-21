<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1726">
    <link rel="icon" type="image/svg+xml" href="/images/icons/raniag-master.svg?v=8">
    <link rel="alternate icon" type="image/png" href="/favicon.png?v=8">
    <title>Page Not Found — {{ config('raniag.organization') }}</title>
    <script src="{{ asset('js/public-theme.js') }}?v={{ @filemtime(public_path('js/public-theme.js')) }}"></script>
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: "Instrument Sans", system-ui, sans-serif;
            background:
                linear-gradient(rgba(141,197,203,.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(141,197,203,.08) 1px, transparent 1px),
                #eaf0f2;
            background-size: 28px 28px, 28px 28px, auto;
            color: #173043;
        }
        .rg-cursor-field {
            position: fixed; inset: 0; z-index: 0; opacity: 0; pointer-events: none;
            background:
                radial-gradient(420px circle at var(--rg-pointer-x, 50vw) var(--rg-pointer-y, 50vh), rgba(13,132,118,.22), transparent 74%),
                radial-gradient(120px circle at var(--rg-pointer-x, 50vw) var(--rg-pointer-y, 50vh), rgba(72,200,179,.12), transparent 78%);
            transition: opacity .2s ease;
        }
        .rg-cursor-field.is-active { opacity: 1; }
        .rg-missing {
            position: relative;
            z-index: 1;
            width: min(560px, 100%);
            overflow: hidden;
            padding: clamp(28px, 5vw, 42px);
            border: 1px solid #294754;
            border-radius: 8px;
            background:
                radial-gradient(ellipse at 88% 0%, rgba(16,104,98,.28), transparent 42%),
                linear-gradient(160deg, #102b39, #0b1b25 72%);
            color: #e8f1f0;
            box-shadow: 0 28px 70px -40px rgba(8,23,33,.85);
        }
        .rg-missing-radar {
            width: 72px; height: 72px; border-radius: 50%; position: relative; overflow: hidden; margin-bottom: 18px;
            border: 1px solid rgba(118,199,188,.4);
            background: radial-gradient(circle, rgba(46,177,151,.25) 0 32%, transparent 33%), repeating-radial-gradient(circle, rgba(151,210,204,.47) 0 1px, transparent 1px 12px);
        }
        .rg-missing-radar span {
            position: absolute; inset: 0;
            background: conic-gradient(from 0deg, transparent 0 70%, rgba(96,215,178,.25) 86%, rgba(191,247,226,.9) 100%);
            animation: rg-missing-sweep 2.8s linear infinite;
        }
        .rg-missing-radar img { position: absolute; left: 50%; top: 50%; width: 28px; height: 28px; transform: translate(-50%, -50%); }
        .rg-missing-kicker { display: flex; align-items: center; gap: 8px; margin: 0 0 14px; color: #8ec9c4; font: 700 .68rem/1.2 ui-monospace, monospace; letter-spacing: .14em; }
        .rg-missing-kicker i { width: 7px; height: 7px; border-radius: 50%; background: #5dd6aa; box-shadow: 0 0 0 4px rgba(93,214,170,.14); }
        h1 { margin: 0; color: #63d1b8; font-size: clamp(4.2rem, 12vw, 6rem); line-height: .85; letter-spacing: -.07em; }
        h2 { margin: 8px 0 12px; color: #f4f7f6; font-size: clamp(1.6rem, 4vw, 2.15rem); letter-spacing: -.04em; }
        p { margin: 0; max-width: 42ch; color: #c5d5d8; line-height: 1.6; }
        .rg-missing-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 22px; }
        .rg-missing-home, .rg-missing-track {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 48px; padding: 0 16px; border-radius: 4px; font-weight: 750; text-decoration: none;
        }
        .rg-missing-home { background: #087b78; color: #fff; }
        .rg-missing-home:hover { background: #0a9187; color: #fff; }
        .rg-missing-track { border: 1px solid #4e6c74; color: #d7e8e8; }
        .rg-missing-track:hover { border-color: #7dccc0; color: #fff; }
        .rg-missing-guide { display: flex; align-items: flex-end; gap: 12px; margin-top: 28px; }
        .rg-missing-guide img { width: 92px; height: auto; filter: drop-shadow(0 10px 12px rgba(0,0,0,.35)); }
        .rg-missing-guide figcaption { margin: 0 0 8px; padding: 10px 12px; border: 1px solid rgba(203,227,229,.22); border-radius: 4px; background: rgba(8,24,34,.72); }
        .rg-missing-guide span { display: block; margin-bottom: 4px; color: #69d5bb; font: 700 .58rem/1.2 ui-monospace, monospace; letter-spacing: .1em; }
        .rg-missing-guide strong { color: #e8f1f0; font-size: .84rem; }
        footer { margin-top: 22px; padding-top: 14px; border-top: 1px solid rgba(190,216,219,.16); color: #9db4b8; font-size: .78rem; }
        footer strong { color: #d5e4e4; }
        @keyframes rg-missing-sweep { to { transform: rotate(360deg); } }
        html[data-public-theme="dark"] body { color-scheme: dark; background: #0c1923; }
        @media (pointer: coarse), (prefers-reduced-motion: reduce) {
            .rg-cursor-field, .rg-missing-radar span { display: none; }
        }
    </style>
</head>
<body>
    <div class="rg-cursor-field" aria-hidden="true"></div>
    <main class="rg-missing">
        <div class="rg-missing-radar" aria-hidden="true">
            <span></span>
            <img src="/images/icons/raniag-master.svg" alt="" width="28" height="28">
        </div>
        <p class="rg-missing-kicker"><i></i> RANIAG — {{ config('raniag.organization') }}</p>
        <h1>404</h1>
        <h2>This page is not on the map.</h2>
        <p>The address may be outdated, or the page was moved. You can go back to the public desk or look up a report you already filed.</p>
        <div class="rg-missing-actions">
            <a class="rg-missing-home" href="{{ route('public.home') }}"><i class="bi bi-house-door" aria-hidden="true"></i> Back to Home</a>
            <a class="rg-missing-track" href="{{ route('public.track') }}"><i class="bi bi-search" aria-hidden="true"></i> Track a report</a>
        </div>
        <figure class="rg-missing-guide">
            <img src="/images/guide/jo-map.png?v=1" alt="JO, the RANIAG guide" width="92" height="122">
            <figcaption>
                <span>JO · CASE DESK</span>
                <strong>Start again from Home, or open a report with its tracking number.</strong>
            </figcaption>
        </figure>
        <footer>
            <strong>Incident Reporting and Analytics System</strong><br>
            {{ config('raniag.organization') }}
        </footer>
    </main>
    <script src="{{ asset('js/public-home-pointer.js') }}?v={{ @filemtime(public_path('js/public-home-pointer.js')) }}"></script>
</body>
</html>

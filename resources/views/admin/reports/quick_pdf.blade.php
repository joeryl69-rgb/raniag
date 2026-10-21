<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $record->title }} — {{ config('raniag.organization') }}</title>
    <style>
        @page { size: A4 portrait; margin: 36mm 15mm 22mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; font-size: 10pt; color: #1d2e3b; }
        h1 { font-size: 17px; color: #173d66; margin: 20px 0 5px; }
        h2 { font-size: 12px; color: #173d66; margin: 15px 0 5px; }
        h3 { font-size: 10px; margin: 4px 0; }
        p { line-height: 1.45; }
        .quick-report-kicker { font-size: 8px; color: #0d5fba; font-weight: bold; letter-spacing: 1px; }
        .meta { color: #617384; font-size: 8px; margin: 0 0 10px; text-align: center; }
        .quick-report-reading { border-left: 3px solid #0d5fba; background: #eff6fc; padding: 7px 9px; font-weight: bold; }
        .quick-report-metrics { width: 100%; margin: 8px 0 12px; }
        .quick-report-metrics>div { display: inline-block; width: 30%; min-height: 44px; border: 1px solid #dce5eb; padding: 7px; margin: 0 1% 4px 0; vertical-align: top; }
        .quick-report-metrics span { display: block; color: #617384; font-size: 7px; }
        .quick-report-metrics strong { display: block; font-size: 13px; margin: 3px 0; }
        .quick-report-metrics small { font-size: 7px; color: #617384; }
        .quick-report-section { border-top: 1px solid #dce5eb; padding-top: 9px; margin-top: 10px; page-break-inside: auto; break-inside: auto; }
        .quick-report-section-heading { margin-bottom: 6px; }
        .quick-report-section-heading, .quick-map-frame, .quick-hotspot, .quick-no-hotspots { page-break-inside: avoid; break-inside: avoid; }
        .quick-report-section-heading h2 { display: inline; margin: 0; }
        .quick-report-section-note,.quick-report-count { float: right; color: #64788a; font-size: 8px; }
        .quick-map-frame { width: 100%; height: 315px; border: 1px solid #dce5eb; background: #f5f9f7; text-align: center; }
        .quick-map-svg { width: 100%; height: 100%; }
        .quick-map-label { font: bold 13px Arial, sans-serif; fill: #173d4b; }
        .quick-map-legend { margin: 5px 0; color: #617384; }
        .quick-map-legend span { margin-right: 10px; font-size: 7px; }
        .quick-map-legend i { display: inline-block; width: 8px; height: 8px; margin-right: 3px; border: 1px solid #607b87; }
        .legend-low { background: #e9eff2; }.legend-watch { background: #b8d7c7; }.legend-elevated { background: #f5df91; }.legend-high { background: #f2b35e; }.legend-critical { background: #e66c59; }
        .quick-report-method,.quick-report-footnote,.quick-case-note { font-size: 7px; color: #617384; }
        .quick-hotspot { border-top: 1px solid #e3e9ed; padding: 7px 0; page-break-inside: avoid; }
        .quick-hotspot-heading { width: 100%; }
        .quick-hotspot-rank { color: #0d5fba; font-weight: bold; }
        .quick-hotspot-title h3 { display: inline; font-size: 10px; }
        .quick-hotspot-title span { color: #617384; font-size: 8px; margin-left: 5px; }
        .quick-risk-band { float: right; color: #88530a; font-size: 7px; font-weight: bold; }
        .quick-hotspot-description { margin: 5px 0; color: #455968; }
        .quick-hotspot-facts span { display: inline-block; width: 31%; color: #617384; font-size: 7px; }
        .quick-hotspot-cases table,.quick-report-bottom-grid table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .quick-hotspot-cases th,.quick-hotspot-cases td { border: 1px solid #dce5eb; padding: 4px; text-align: left; font-size: 7px; }
        .quick-hotspot-cases th { background: #eef3f7; color: #435869; }
        .quick-hotspot-cases thead, .quick-report-bottom-grid thead { display: table-header-group; }
        .quick-hotspot-cases tr, .quick-report-bottom-grid tr { page-break-inside: avoid; break-inside: avoid; }
        .quick-no-hotspots { background: #f2faf5; padding: 8px; }
        .quick-no-hotspots strong { color: #23653d; }
        .quick-no-hotspots p { margin: 3px 0; }
        .quick-report-bottom-grid { width: 100%; }
        .quick-report-bottom-grid>section { width: 49%; display: inline-block; vertical-align: top; }
        .rg-bars { width: 100%; }
        .rg-bar { margin: 4px 0; font-size: 7px; }
        .rg-bar-label { display: inline-block; width: 38%; }
        .rg-bar-track { display: inline-block; width: 45%; height: 6px; background: #e2e8f0; }
        .rg-bar-fill { display: block; height: 6px; background: #1a365d; }
        .rg-bar-value { display: inline-block; width: 12%; text-align: right; font-weight: bold; }
        .quick-report-footnote { border-top: 1px solid #dce5eb; padding-top: 7px; }
    </style>
</head>
<body>
    <p class="meta">{{ $record->date_from->format('F j, Y') }} – {{ $record->date_to->format('F j, Y') }} · Filed {{ $record->filed_at?->format('M j, Y g:i a') }}</p>
    @include('admin.reports.partials._quick_report_content', ['quickReport' => $quickReport])
</body>
</html>

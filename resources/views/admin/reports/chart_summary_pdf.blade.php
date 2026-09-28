<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chart Summary Report — {{ config('raniag.organization') }}</title>
    <style>
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 20px 20px 45px 20px;
        }
        .filters {
            background-color: #f8fafc;
            padding: 10px 15px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #1a365d;
            font-size: 10pt;
        }
        .filters strong { color: #1a365d; }
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #1a365d;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-top: 25px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 6px 10px; text-align: left; border: 1px solid #e2e8f0; font-size: 9.5pt; }
        th { background-color: #1a365d; color: white; font-weight: bold; font-size: 9pt; text-transform: uppercase; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .aor-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 8.5pt;
            font-weight: bold;
            color: #fff;
            background-color: #1a365d;
        }
        .narrative {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #198754;
            padding: 12px 15px;
            margin-bottom: 20px;
            font-size: 10pt;
        }
        .narrative p { margin: 0 0 6px; }
        .narrative p:last-child { margin-bottom: 0; }

        /* CSS-only horizontal bar chart: percentage-width bars via a div's
           width style, no JS
           and no external image, so every render — screen preview, PDF,
           repeat generation with the same filters — draws identically. */
        .bar-row { display: flex; align-items: center; margin-bottom: 6px; page-break-inside: avoid; }
        .bar-label { width: 34%; font-size: 9pt; padding-right: 8px; text-align: right; color: #333; }
        .bar-track { width: 46%; background-color: #eef2f6; border-radius: 3px; overflow: hidden; height: 14px; }
        .bar-fill { height: 14px; background-color: #1a365d; border-radius: 3px; }
        .bar-fill.alt { background-color: #198754; }
        .bar-value { width: 20%; font-size: 9pt; padding-left: 8px; color: #555; }
        .no-data { color: #666; font-style: italic; font-size: 9.5pt; padding: 6px 0; }
    </style>
</head>
<body>
    @include('admin.reports.partials._footer')
    @include('admin.reports.partials._letterhead', ['rgLetterheadTitle' => 'Operations brief — '.config('raniag.organization')])

    <div class="meta-info" style="text-align:right; font-size:9pt; color:#666; margin-bottom:20px;">
        <strong>Generated:</strong> {{ $generated_at->format('M d, Y h:i A') }}
    </div>

    <div class="filters">
        <p><strong>Date Range:</strong> {{ \Carbon\Carbon::parse($filters['date_from'])->format('F d, Y') }} — {{ \Carbon\Carbon::parse($filters['date_to'])->format('F d, Y') }}</p>
        <p>
            <strong>AOR Scope:</strong>
            <span class="aor-badge">
                @switch($filters['aor_scope'])
                    @case('outside_aor_only') Outside-AOR Only @break
                    @case('all') AOR + Outside-AOR @break
                    @default MDRRMO Pamplona AOR Only
                @endswitch
            </span>
        </p>
        <p><strong>Report View:</strong> {{ ucfirst($viewMode) }}{{ $viewMode !== 'periodic' ? ' (grouped by '.($viewMode === 'monthly' ? 'calendar month' : 'calendar week').')' : '' }}</p>
        @if(!empty($filters['barangay']))
            <p><strong>Barangay:</strong> {{ $filters['barangay'] }}</p>
        @endif
        @if(!empty($filters['agency_id']))
            <p><strong>Agency:</strong> {{ $agencyName ?? 'N/A' }}</p>
        @endif
        @if(!empty($filters['incident_type_id']))
            <p><strong>Incident Type:</strong> {{ $incidents->first()->incidentType->name ?? 'N/A' }}</p>
        @endif
    </div>

    <div class="section-title" style="margin-top:0;">Summary</div>
    <div class="narrative">
        @foreach($narrative as $line)
            <p>{{ $line }}</p>
        @endforeach
    </div>

    @foreach($charts as $key => $chart)
        <div class="section-title">{{ $chartLabels[$key] ?? $key }}</div>
        @if(empty($chart['rows']))
            <p class="no-data">No cases in this period for this section.</p>
        @elseif($key === 'where_to_send')
            <table>
                <thead>
                    <tr><th>Place</th><th>Main type of case</th><th>Cases</th><th>Still open</th></tr>
                </thead>
                <tbody>
                    @foreach($chart['rows'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td>{{ $row['type'] ?? '—' }}</td>
                            <td>{{ $row['count'] }}</td>
                            <td>{{ $row['open'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @elseif($key === 'agency_load')
            <table>
                <thead>
                    <tr><th>Office</th><th>Cases</th><th>Still open</th></tr>
                </thead>
                <tbody>
                    @foreach($chart['rows'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td>{{ $row['count'] }}</td>
                            <td>{{ $row['open'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <table>
                <thead>
                    <tr><th>{{ $key === 'when_reports_arrived' ? 'Period' : 'Measure' }}</th><th>Result</th></tr>
                </thead>
                <tbody>
                    @foreach($chart['rows'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td>{{ ($row['count'] ?? 0) === 0 && !empty($row['hint']) ? $row['hint'] : ($row['hint'] ?? $row['count']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <div style="margin-top:30px; padding-top:15px; border-top:1px solid #e2e8f0; text-align:center; color:#666; font-size:9pt;">
        <p style="margin:0 0 4px;">This report was generated automatically by {{ config('raniag.name') }} — {{ config('raniag.organization') }}</p>
        <p style="margin:0;">Every figure above is a direct count from the incident database for the selected filters — regenerating with the same filters always produces the same numbers.</p>
    </div>
</body>
</html>

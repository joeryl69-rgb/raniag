<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Decision report — {{ config('raniag.organization') }}</title>
    <style>
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; font-size: 11pt; line-height: 1.45; color: #333; margin: 0; padding: 20px 20px 45px; }
        .filters { background: #f8fafc; padding: 10px 15px; margin-bottom: 15px; border: 1px solid #e2e8f0; border-left: 4px solid #1a365d; font-size: 10pt; }
        .filters strong { color: #1a365d; }
        .section-title { font-size: 12pt; font-weight: bold; color: #1a365d; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-top: 22px; margin-bottom: 10px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { padding: 6px 10px; text-align: left; border: 1px solid #e2e8f0; font-size: 9.5pt; }
        th { background: #1a365d; color: #fff; font-size: 9pt; text-transform: uppercase; }
        tr:nth-child(even) { background: #f8fafc; }
        .narrative { background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #0f766e; padding: 12px 15px; margin-bottom: 12px; font-size: 10pt; }
        .narrative p { margin: 0 0 6px; }
        .up { color: #b45309; font-weight: bold; }
        .down { color: #0f766e; font-weight: bold; }
        .no-data { color: #666; font-style: italic; font-size: 9.5pt; }
    </style>
</head>
<body>
    @include('admin.reports.partials._footer')
    @include('admin.reports.partials._letterhead', ['rgLetterheadTitle' => 'Decision report — '.config('raniag.organization')])

    <div style="text-align:right; font-size:9pt; color:#666; margin-bottom:16px;">
        <strong>Generated:</strong> {{ $generated_at->format('M d, Y h:i A') }}
    </div>

    <div class="filters">
        <p><strong>This range:</strong> {{ \Carbon\Carbon::parse($filters['date_from'])->format('F d, Y') }} — {{ \Carbon\Carbon::parse($filters['date_to'])->format('F d, Y') }}</p>
        @if($compareFilters)
            <p><strong>Compared with:</strong> {{ \Carbon\Carbon::parse($compareFilters['date_from'])->format('F d, Y') }} — {{ \Carbon\Carbon::parse($compareFilters['date_to'])->format('F d, Y') }}</p>
        @endif
        @if(!empty($filters['barangay']))<p><strong>Barangay:</strong> {{ $filters['barangay'] }}</p>@endif
        @if(!empty($filters['agency_id']))<p><strong>Office:</strong> {{ $agencyName ?? 'N/A' }}</p>@endif
        <p><strong>Area:</strong>
            @switch($filters['aor_scope'])
                @case('outside_aor_only') Referred outside Pamplona @break
                @case('all') Inside and outside Pamplona @break
                @default Inside Pamplona
            @endswitch
        </p>
    </div>

    @if(in_array('summary', $sections, true))
        <div class="section-title" style="margin-top:0;">Summary</div>
        <div class="narrative">
            @foreach($current['lines'] as $line)
                <p>{{ $line }}</p>
            @endforeach
            <p>Still open: {{ $current['open'] }}. Median time to the first assignment: {{ $current['median_assignment'] }}.</p>
        </div>
    @endif

    @if(in_array('status', $sections, true))
        <div class="section-title">Incident status</div>
        @include('admin.reports.partials._columns', ['rows' => $current['status']])
    @endif

    @if(in_array('priority', $sections, true))
        <div class="section-title">Priority of open incidents</div>
        @include('admin.reports.partials._columns', ['rows' => $current['priority'], 'color' => '#b45309'])
    @endif

    @if(in_array('types', $sections, true))
        <div class="section-title">Open incidents by type</div>
        @include('admin.reports.partials._columns', ['rows' => $current['types'], 'color' => '#0f766e'])
    @endif

    @if(in_array('places', $sections, true))
        <div class="section-title">Open reports by barangay</div>
        <table>
            <thead><tr><th>Barangay</th><th>Main type</th><th>Reports</th><th>Still open</th></tr></thead>
            <tbody>
                @forelse($current['places'] as $place)
                    <tr>
                        <td>{{ $place['label'] }}</td>
                        <td>{{ $place['type'] ?? '—' }}</td>
                        <td>{{ $place['count'] }}</td>
                        <td>{{ $place['open'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No barangay recorded for these reports.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if(in_array('arrivals', $sections, true))
        <div class="section-title">When reports arrived</div>
        @include('admin.reports.partials._columns', ['rows' => $current['arrivals'], 'color' => '#1d4ed8'])
    @endif

    @if(in_array('comparison', $sections, true) && $comparison)
        <div class="section-title">Comparison</div>
        <table>
            <thead><tr><th></th><th>This range</th><th>Comparison range</th><th>Change</th></tr></thead>
            <tbody>
                <tr>
                    <td>Reports</td>
                    <td>{{ $current['total'] }}</td>
                    <td>{{ $comparison['total'] }}</td>
                    <td>{{ $current['total'] - $comparison['total'] }}</td>
                </tr>
                <tr>
                    <td>Still open</td>
                    <td>{{ $current['open'] }}</td>
                    <td>{{ $comparison['open'] }}</td>
                    <td>{{ $current['open'] - $comparison['open'] }}</td>
                </tr>
            </tbody>
        </table>
        @foreach(['status' => 'Status', 'priority' => 'Priority of open incidents', 'types' => 'Open incidents by type'] as $key => $title)
            <p style="font-weight:bold; color:#1a365d; margin:8px 0 4px;">{{ $title }}</p>
            <table>
                <thead><tr><th></th><th>This range</th><th>Comparison range</th><th>Change</th></tr></thead>
                <tbody>
                    @foreach($paired[$key] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td>{{ $row['current'] }}</td>
                            <td>{{ $row['previous'] }}</td>
                            <td>{{ $row['change'] > 0 ? '+' : '' }}{{ $row['change'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
        <p style="font-weight:bold; color:#1a365d;">Arrivals in the comparison range</p>
        @include('admin.reports.partials._columns', ['rows' => $comparison['arrivals'], 'color' => '#64748b'])
    @endif

    @if(in_array('projection', $sections, true) && $projection)
        <div class="section-title">Projection for the next period</div>
        <div class="narrative">
            <p>{{ $projection['note'] }}</p>
        </div>
    @endif
</body>
</html>

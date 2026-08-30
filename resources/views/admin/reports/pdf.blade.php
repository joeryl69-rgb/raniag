<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Report — {{ config('raniag.organization') }}</title>
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
        .filters strong {
            color: #1a365d;
        }
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #e2e8f0;
            font-size: 9.5pt;
        }
        th {
            background-color: #1a365d;
            color: white;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 8pt;
            font-weight: bold;
            color: white;
            display: inline-block;
        }
        .badge-submitted { background-color: #6c757d; }
        .badge-received { background-color: #0d6efd; }
        .badge-assigned { background-color: #ffc107; color: #000; }
        .badge-in_progress { background-color: #fd7e14; }
        .badge-resolved { background-color: #198754; }
        .badge-closed { background-color: #212529; }
        .badge-rejected { background-color: #dc3545; }
        .badge-low { background-color: #198754; }
        .badge-medium { background-color: #ffc107; color: #000; }
        .badge-high { background-color: #fd7e14; }
        .badge-critical { background-color: #dc3545; }
        .summary {
            margin-bottom: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    @include('admin.reports.partials._footer')
    @include('admin.reports.partials._letterhead', ['rgLetterheadTitle' => 'Incident Report — '.config('raniag.organization')])

    <div class="meta-info" style="text-align:right; font-size:9pt; color:#666; margin-bottom:20px;">
        <strong>Generated:</strong> {{ $generated_at->format('M d, Y h:i A') }}
    </div>

    <div class="filters">
        <p><strong>Date Range:</strong> {{ \Carbon\Carbon::parse($filters['date_from'])->format('F d, Y') }} — {{ \Carbon\Carbon::parse($filters['date_to'])->format('F d, Y') }}</p>
        @if(!empty($filters['barangay']))
            <p><strong>Barangay:</strong> {{ $filters['barangay'] }}</p>
        @endif
        @if(!empty($filters['agency_id']))
            <p><strong>Agency:</strong> {{ $agencyName ?? 'N/A' }}</p>
        @endif
        @if(!empty($filters['incident_type_id']))
            <p><strong>Incident Type:</strong> {{ $incidents->first()->incidentType->name ?? 'N/A' }}</p>
        @endif
        <p><strong>Generated:</strong> {{ $generated_at->format('F d, Y g:i A') }}</p>
    </div>

    <div class="summary">
        <div class="section-title" style="margin-top:0;">Summary</div>
        <div class="summary-row">
            <span><strong>Total Incidents:</strong> {{ $incidents->count() }}</span>
        </div>
        <div class="summary-row">
            <span><strong>Resolved:</strong> {{ $incidents->where('status', 'resolved')->count() }}</span>
            <span><strong>In Progress:</strong> {{ $incidents->where('status', 'in_progress')->count() }}</span>
        </div>
        <div class="summary-row">
            <span><strong>Pending:</strong> {{ $incidents->where('status', 'submitted')->count() }}</span>
            <span><strong>Closed:</strong> {{ $incidents->where('status', 'closed')->count() }}</span>
        </div>
    </div>

    <div class="section-title">Incident Records</div>
    @if($incidents->count() > 0)
        <table>
            <thead>
                <tr>
                    <th style="width:14%;">Tracking #</th>
                    <th style="width:12%;">Date Reported</th>
                    <th style="width:16%;">Type</th>
                    <th style="width:14%;">Barangay</th>
                    <th style="width:10%;">Priority</th>
                    <th style="width:12%;">Status</th>
                    <th style="width:22%;">Agency</th>
                </tr>
            </thead>
            <tbody>
                @foreach($incidents as $incident)
                    <tr>
                        <td>{{ $incident->tracking_number }}</td>
                        <td>{{ $incident->reported_at?->format('M d, Y') }}</td>
                        <td>{{ $incident->incidentType->name ?? 'N/A' }}</td>
                        <td>{{ $incident->barangay }}</td>
                        <td>
                            @php
                                $priorityValue = $incident->priority instanceof \UnitEnum ? $incident->priority->value : $incident->priority;
                            @endphp
                            <span class="badge badge-{{ $priorityValue }}">{{ $priorityValue ? ucfirst($priorityValue) : 'N/A' }}</span>
                        </td>
                        <td>
                            @php
                                $statusValue = $incident->status instanceof \UnitEnum ? $incident->status->value : $incident->status;
                            @endphp
                            <span class="badge badge-{{ $statusValue }}">{{ ucfirst(str_replace('_', ' ', $statusValue)) }}</span>
                        </td>
                        <td>{{ $resolvedAgencyNames[$incident->id] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; color: #666; padding: 20px;">No incidents found matching the specified filters.</p>
    @endif

    <div style="margin-top:30px; padding-top:15px; border-top:1px solid #e2e8f0; text-align:center; color:#666; font-size:9pt;">
        <p style="margin:0 0 4px;">This report was generated automatically by {{ config('raniag.name') }} — {{ config('raniag.organization') }}</p>
        <p style="margin:0;">For inquiries, contact MDRRMO Pamplona.</p>
    </div>
</body>
</html>

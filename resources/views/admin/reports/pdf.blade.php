<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Report — {{ config('raniag.organization') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #1a6b3a;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 24px;
            color: #1a6b3a;
            margin: 0 0 5px 0;
        }
        .header p {
            font-size: 14px;
            margin: 0;
            color: #666;
        }
        .filters {
            background-color: #f8f9fa;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-left: 4px solid #1a6b3a;
        }
        .filters strong {
            color: #1a6b3a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #1a6b3a;
            color: white;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
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
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 11px;
        }
        .summary {
            margin-bottom: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dashed #ddd;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('raniag.name') }} — Incident Report</h1>
        <p>{{ config('raniag.organization') }}</p>
    </div>

    <div class="filters">
        <p><strong>Date Range:</strong> {{ \Carbon\Carbon::parse($filters['date_from'])->format('F d, Y') }} — {{ \Carbon\Carbon::parse($filters['date_to'])->format('F d, Y') }}</p>
        @if(!empty($filters['barangay']))
            <p><strong>Barangay:</strong> {{ $filters['barangay'] }}</p>
        @endif
        @if(!empty($filters['agency_id']))
            <p><strong>Agency:</strong> {{ $incidents->first()->agency->name ?? 'N/A' }}</p>
        @endif
        @if(!empty($filters['incident_type_id']))
            <p><strong>Incident Type:</strong> {{ $incidents->first()->incidentType->name ?? 'N/A' }}</p>
        @endif
        <p><strong>Generated:</strong> {{ $generated_at->format('F d, Y g:i A') }}</p>
    </div>

    <div class="summary">
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

    @if($incidents->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Tracking #</th>
                    <th>Date Reported</th>
                    <th>Type</th>
                    <th>Barangay</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Agency</th>
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
<span class="badge badge-{{ $incident->priority instanceof \UnitEnum ? $incident->priority->value : $incident->priority }}">
                                {{ ($incident->priority instanceof \UnitEnum ? $incident->priority->value : $incident->priority) ? ucfirst(($incident->priority instanceof \UnitEnum ? $incident->priority->value : $incident->priority)) : 'N/A' }}
                            </span>
                        </td>
                        <td>
<span class="badge badge-{{ $incident->status instanceof \UnitEnum ? $incident->status->value : $incident->status }}">
{{ ucfirst(str_replace('_', ' ', ($incident->status instanceof \UnitEnum ? $incident->status->value : $incident->status))) }}
                            </span>
                        </td>
                        <td>{{ $incident->agency->name ?? 'Unassigned' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; color: #666; padding: 20px;">No incidents found matching the specified filters.</p>
    @endif

    <div class="footer">
        <p>This report was generated automatically by {{ config('raniag.name') }} — {{ config('raniag.organization') }}</p>
        <p>For inquiries, contact MDRRMO Pamplona.</p>
    </div>
</body>
</html>

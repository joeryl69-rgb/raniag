<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>MDRRMO Summary {{ $month }}</title>
<style>body{font-family:Arial,sans-serif;font-size:10pt;color:#1d2e3b;margin:0;padding:18px 20px 35px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #dce5eb;padding:5px 7px;text-align:left}th{background:#edf3f8;color:#173d66}h2{font-size:12pt;color:#173d66;margin:18px 0 7px}</style>
</head>
<body>
@include('admin.reports.partials._letterhead', ['rgLetterheadTitle' => 'Monthly incident summary'])
<p style="text-align:center;color:#64788a;font-size:9pt;margin:0 0 12px">{{ $month }} · Generated {{ $generatedAt->format('M j, Y g:i A') }} · {{ $incidents->count() }} incidents</p>
<h2>By type</h2>
<ul>@foreach($byType as $type => $count)<li>{{ $type }}: {{ $count }}</li>@endforeach</ul>
<h2>By status</h2>
<ul>@foreach($byStatus as $status => $count)<li>{{ $status }}: {{ $count }}</li>@endforeach</ul>
<h2>Incidents</h2>
<table>
<thead><tr><th>Tracking</th><th>Type</th><th>Status</th><th>Barangay</th><th>Reported</th></tr></thead>
<tbody>
@foreach($incidents as $inc)
<tr>
<td>{{ $inc->tracking_number }}</td>
<td>{{ $inc->incidentType?->name }}</td>
<td>{{ $inc->status instanceof \BackedEnum ? $inc->status->value : $inc->status }}</td>
<td>{{ $inc->barangay }}</td>
<td>{{ optional($inc->reported_at)->format('Y-m-d H:i') }}</td>
</tr>
@endforeach
</tbody>
</table>
</body>
</html>

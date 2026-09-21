<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>MDRRMO Summary {{ $month }}</title>
<style>body{font-family:DejaVu Sans,sans-serif;font-size:12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ccc;padding:4px;text-align:left}h1{font-size:18px}</style>
</head>
<body>
<h1>RANIAG monthly summary — {{ $month }}</h1>
<p>Generated {{ $generatedAt->format('Y-m-d H:i') }} · Total incidents: {{ $incidents->count() }}</p>
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

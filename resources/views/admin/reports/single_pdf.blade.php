<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Official Incident Report - {{ $tracking_number }}</title>
    <style>
        body { 
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; 
            font-size: 11pt; 
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 20px 20px 45px 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1a365d;
            padding-bottom: 15px;
        }
        .header-text {
            font-size: 10pt;
            text-transform: uppercase;
            line-height: 1.3;
        }
        .header-title {
            font-size: 16pt;
            font-weight: bold;
            margin-top: 15px;
            color: #1a365d;
        }
        .meta-info {
            text-align: right;
            font-size: 9pt;
            color: #666;
            margin-bottom: 20px;
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
        table.details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.details-table th {
            width: 30%;
            text-align: left;
            padding: 8px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 10pt;
            color: #475569;
        }
        table.details-table td {
            width: 70%;
            padding: 8px;
            border: 1px solid #e2e8f0;
            font-weight: bold;
            font-size: 10pt;
        }
        .narrative-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            font-size: 10pt;
            min-height: 80px;
            white-space: pre-wrap;
        }
        table.timeline-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9pt;
        }
        table.timeline-table th {
            background-color: #1a365d;
            color: white;
            padding: 8px;
            text-align: left;
            border: 1px solid #1a365d;
        }
        table.timeline-table td {
            padding: 8px;
            border: 1px solid #e2e8f0;
        }
        .evidence-grid {
            margin-top: 15px;
        }
        .evidence-item {
            margin-bottom: 15px;
            text-align: center;
        }
        .evidence-img {
            max-width: 100%;
            max-height: 300px;
            border: 1px solid #cbd5e1;
            padding: 4px;
            background: white;
        }
        .evidence-caption {
            font-size: 8pt;
            color: #64748b;
            margin-top: 5px;
        }
        .status-badge {
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    @include('admin.reports.partials._footer')
    @include('admin.reports.partials._letterhead', ['rgLetterheadTitle' => 'OFFICIAL INCIDENT REPORT'])

    <div class="meta-info">
        <strong>Generated:</strong> {{ $generated_at->format('M d, Y h:i A') }}<br>
        <strong>System Ref:</strong> {{ $incident->id }}
    </div>

    @if(! isset($sections) || in_array('incident_details', $sections))
    <div class="section-title">I. Incident Particulars</div>
    <table class="details-table">
        <tr>
            <th>Tracking Number</th>
            <td style="color: #dc2626; font-size: 12pt;">{{ $incident->tracking_number }}</td>
        </tr>
        <tr>
            <th>Incident Category</th>
            <td>{{ $incident->incidentType->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Current Status</th>
            <td class="status-badge">{{ str_replace('_', ' ', $incident->status->value) }}</td>
        </tr>
        <tr>
            <th>Priority Level</th>
            <td>{{ strtoupper($incident->priority->label() ?? $incident->priority) }}</td>
        </tr>
        <tr>
            <th>Date & Time Reported</th>
            <td>{{ optional($incident->reported_at)->format('F d, Y - h:i A') }}</td>
        </tr>
        <tr>
            <th>Exact Location</th>
            <td>
                {{ $incident->barangay ?? 'N/A' }} 
                @if($incident->location_address)
                    <br><span style="font-weight: normal; font-size: 9pt;">{{ $incident->location_address }}</span>
                @endif
                @if($incident->latitude && $incident->longitude)
                    <br><span style="font-weight: normal; font-size: 8pt; color: #666;">GPS: {{ $incident->latitude }}, {{ $incident->longitude }}</span>
                @endif
            </td>
        </tr>
    </table>
    @endif

    @if(! isset($sections) || in_array('narrative', $sections))
    <div class="section-title">II. Narrative / Description</div>
    @if($incident->title)
        <div style="font-weight: bold; margin-bottom: 5px; font-size: 11pt;">{{ $incident->title }}</div>
    @endif
    <div class="narrative-box">
        {!! nl2br(e($incident->description)) !!}
    </div>
    @endif

    @if(($incident->resolutions && $incident->resolutions->isNotEmpty()) && (! isset($sections) || in_array('resolutions', $sections)))
        <div class="section-title">III. Official Resolutions & Actions Taken</div>
        @foreach($incident->resolutions as $res)
            <div style="margin-bottom: 15px; border: 1px solid #1a365d; padding: 10px;">
                <div style="font-size: 9pt; color: #1a365d; font-weight: bold; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                    Resolution by: {{ $res->resolver?->agency?->code ?? $res->resolver?->display_title ?? 'Agency' }} 
                    <span style="float: right;">{{ $res->created_at->format('M d, Y h:i A') }}</span>
                </div>
                <strong style="font-size: 10pt;">Summary:</strong>
                <p style="font-size: 10pt; margin-top: 2px;">{{ $res->summary }}</p>
                <strong style="font-size: 10pt;">Actions Taken:</strong>
                <p style="font-size: 10pt; margin-top: 2px; margin-bottom: 0;">{{ $res->actions_taken }}</p>
            </div>
        @endforeach
    @endif

    @if(! isset($sections) || in_array('evidence_photos', $sections))
    <div class="section-title">IV. Attached Evidence</div>
    @if($incident->evidence->isEmpty())
        <div style="font-style: italic; color: #666; font-size: 10pt;">No digital evidence attached to this report.</div>
    @else
        <div class="evidence-grid">
            @foreach($incident->evidence as $ev)
                @if(str_starts_with($ev->mime_type, 'image/'))
                    <div class="evidence-item">
                        <img src="{{ storage_path('app/public/'.$ev->file_path) }}" class="evidence-img" alt="Evidence Image">
                        <div class="evidence-caption">
                            {{ $ev->original_filename }} ({{ $ev->is_gps_verified ? 'GPS Tagged & Verified' : 'Standard Upload' }})
                        </div>
                    </div>
                @else
                    <div style="font-size: 9pt; padding: 5px; border: 1px dashed #ccc; margin-bottom: 5px;">
                        [Document/File Attached]: {{ $ev->original_filename }}
                    </div>
                @endif
            @endforeach
        </div>
    @endif
    @endif

    @php
        $docSectionKeys = ['call_taker_form', 'dispatch_form', 'narrative_report', 'endorsement_sheet'];
        $activeDocKeys = isset($sections) ? array_intersect($docSectionKeys, $sections) : $docSectionKeys;
        $docsToShow = ($incident->incidentDocuments ?? collect())->whereIn('document_type', array_map(fn ($k) => \App\Enums\IncidentDocumentType::from($k), $activeDocKeys));
    @endphp
    @if(! empty($activeDocKeys))
    <div class="section-title" style="page-break-before: auto;">V. Case Documents (Repository)</div>
    @if($docsToShow->isEmpty())
        <div style="font-style: italic; color: #666; font-size: 10pt;">No repository documents attached for the selected content.</div>
    @else
        <div class="evidence-grid">
            @foreach($docsToShow as $doc)
                <div class="evidence-item">
                    @if(str_starts_with((string) $doc->mime_type, 'image/'))
                        <img src="{{ storage_path('app/public/'.$doc->file_path) }}" class="evidence-img" alt="{{ $doc->document_type->label() }}">
                    @else
                        <div style="font-size: 9pt; padding: 5px; border: 1px dashed #ccc;">[Document Attached]: {{ $doc->original_filename }}</div>
                    @endif
                    <div class="evidence-caption">{{ $doc->document_type->label() }} — {{ $doc->original_filename }}</div>
                </div>
            @endforeach
        </div>
    @endif
    @endif

    @if(! isset($sections) || in_array('status_timeline', $sections))
    <div class="section-title" style="page-break-before: auto;">VI. Status & Audit Timeline</div>
    @if($incident->statusUpdates->isEmpty())
        <div style="font-style: italic; color: #666; font-size: 10pt;">No status timeline recorded.</div>
    @else
        <table class="timeline-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Date / Time</th>
                    <th style="width: 25%;">Status Transition</th>
                    <th style="width: 55%;">Audit Log / Comments</th>
                </tr>
            </thead>
            <tbody>
                @foreach($incident->statusUpdates as $u)
                    <tr>
                        <td>{{ optional($u->created_at)->format('M d, Y h:i A') }}</td>
                        <td style="text-transform: uppercase;">
                            <span style="color: #666; font-size: 8pt;">FROM:</span> {{ $u->from_status?->value ?? '-' }}<br>
                            <span style="color: #1a365d; font-weight: bold; font-size: 8pt;">TO:</span> {{ $u->to_status?->value ?? $u->to_status }}
                        </td>
                        <td>{{ $u->comment }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    @endif

</body>
</html>


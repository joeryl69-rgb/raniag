<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} — {{ config('raniag.organization') }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #1e293b; margin: 0; padding: 16px 18px 28px; }
        h1 { font-size: 16px; color: #1a365d; margin: 0 0 4px; }
        .intro { margin: 0 0 6px; }
        .meta { color: #475569; margin: 0 0 14px; }
        h2 { font-size: 12px; color: #1a365d; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; margin: 16px 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #e2e8f0; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; }
        .empty, .note { color: #64748b; margin: 0 0 8px; }
    </style>
</head>
<body>
    @include('admin.reports.partials._letterhead', ['rgLetterheadTitle' => $title])
    <p class="intro">{{ $intro }}</p>
    <p class="meta">{{ $from }} to {{ $to }} · Made {{ $generated_at->format('M j, Y g:i a') }}</p>
    @foreach($sections as $section)
        <h2>{{ $section['heading'] }}</h2>
        @if(! empty($section['note']))
            <p class="note">{{ $section['note'] }}</p>
        @endif
        @if($section['rows'] === [])
            <p class="empty">Nothing recorded for these dates.</p>
        @else
            <table>
                <thead>
                    <tr>
                        @foreach($section['columns'] as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($section['rows'] as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
</body>
</html>

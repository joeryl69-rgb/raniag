<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $record->title }} — {{ config('raniag.organization') }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #1e293b; margin: 0; padding: 16px 18px 36px; }
        h1 { font-size: 16px; color: #1a365d; margin: 8px 0 4px; }
        h2 { font-size: 12px; color: #1a365d; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; margin: 16px 0 6px; }
        .meta, .note, .rg-bar-empty { color: #475569; margin: 0 0 8px; }
        .rg-bar { margin: 0 0 4px; }
        .rg-bar-label { display: inline-block; width: 42%; font-size: 9px; vertical-align: middle; }
        .rg-bar-track { display: inline-block; width: 46%; height: 8px; background: #e2e8f0; vertical-align: middle; }
        .rg-bar-fill { display: block; height: 8px; background: #1a365d; }
        .rg-bar-value { display: inline-block; width: 10%; text-align: right; font-size: 9px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #e2e8f0; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #1a365d; color: #fff; }
    </style>
</head>
<body>
    @include('admin.reports.partials._footer')
    @include('admin.reports.partials._letterhead', ['rgLetterheadTitle' => $record->title])
    <p class="meta">{{ $record->date_from->format('F j, Y') }} – {{ $record->date_to->format('F j, Y') }} · Filed {{ $generated_at->format('M j, Y g:i a') }}</p>
    @foreach($chapters as $chapter)
        <h2>{{ $chapter['title'] }}</h2>
        @if($chapter['note'] !== '')
            <p class="note">{{ $chapter['note'] }}</p>
        @endif
        @if(! empty($chapter['bars']))
            @include('admin.reports.partials.bars', ['bars' => $chapter['bars'], 'label' => $chapter['title']])
        @endif
        @if($chapter['rows'] === [])
            <p class="note">Nothing recorded for this chapter.</p>
        @else
            <table>
                <thead>
                    <tr>
                        @foreach($chapter['columns'] as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($chapter['rows'] as $row)
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

@php
    $rgLetterheadTitle = $presentation['title'];
    $letterhead = \App\Services\ReportLetterhead::templateData();
    $margins = $letterhead['page_margins_mm'];
    $fontFamily = $letterhead['font_family'];
    $bodyFontSize = $letterhead['body_font_size_pt'];
    $rows = $presentation['rows'];
    $columns = $presentation['columns'];
    $columnCount = max(1, count($columns));
    $pageName = 'A4';
    $pageOrient = 'portrait';
    $widths = match ($columnCount) {
        7 => [12, 13, 22, 16, 10, 14, 13],
        6 => [16, 17, 22, 16, 10, 19],
        default => array_fill(0, $columnCount, intdiv(100, $columnCount)),
    };
    $widths[$columnCount - 1] += 100 - array_sum($widths);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $presentation['title'] }}</title>
    <style>
        @page { size: {{ $pageName }} {{ $pageOrient }}; margin: 36mm {{ $margins['horizontal'] }}mm 22mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #1c2b36; font-family: {{ $fontFamily }}, sans-serif; font-size: {{ $bodyFontSize }}pt; background: #fff; }
        .rg-print-toolbar { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 12px; }
        .rg-print-toolbar button {
            min-height: 36px; padding: 8px 14px; border: 1px solid #1a365d; border-radius: 4px;
            background: #1a365d; color: #fff; font: 700 13px/1.2 {{ $fontFamily }}, sans-serif; cursor: pointer;
        }
        .rg-pdf .rg-running-header,
        .rg-pdf .rg-running-footer { display: none; }
        @media screen {
            .rg-print-preview .rg-running-footer { display: none; }
        }
        @media screen {
            .rg-print-preview .rg-print-sheet { padding: {{ $margins['top'] }}mm {{ $margins['horizontal'] }}mm {{ $margins['bottom'] }}mm; }
        }
        table.rg-report { width: 100%; max-width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.rg-report thead { display: table-header-group; }
        table.rg-report tr { page-break-inside: avoid; }
        table.rg-report th, table.rg-report td {
            border: 1px solid #b7c5d0; padding: 5px 6px; vertical-align: top; text-align: left;
            word-wrap: break-word; overflow-wrap: break-word; word-break: break-word;
        }
        table.rg-report thead th {
            background-color: #e8eef6;
            color: #1a365d;
            font-size: 9pt;
            font-weight: 700;
            border-bottom: 2px solid #1a365d;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        table.rg-report tbody tr:nth-child(even) td { background-color: #f7fafb; }
        @media print {
            .rg-print-toolbar { display: none !important; }
            .rg-print-sheet { padding: 0; }
            @page { margin-top: 36mm; margin-bottom: 22mm; }
            .rg-running-header { position: fixed; top: 4mm; left: 0; right: 0; height: 28mm; }
            .rg-running-footer { position: fixed; bottom: 4mm; left: 0; right: 0; display: block !important; }
            .rg-running-footer .rg-pdf-footer { position: static !important; bottom: auto !important; }
        }
    </style>
</head>
<body class="{{ $forPdf ? 'rg-pdf' : 'rg-print-preview' }}">
    @unless($forPdf)
        <div class="rg-print-toolbar">
            <button type="button" onclick="window.print()">Print</button>
        </div>
    @endunless

    @unless($forPdf)
        <div class="rg-running-header">
            @include('admin.reports.partials._letterhead', [
                'rgLetterheadTitle' => $presentation['title'],
                'rgPageMargins' => ['top' => 36, 'horizontal' => $margins['horizontal'], 'bottom' => 22],
            ])
        </div>
        <div class="rg-running-footer">
            @include('admin.reports.partials._footer', [
                'rgFooterIncludeTimestamp' => false,
                'rgFooterIncludePageNumbers' => false,
            ])
        </div>
    @endunless

    <main class="rg-print-sheet">
        <table class="rg-report">
            <colgroup>
                @foreach($widths as $width)
                    <col style="width: {{ $width }}%">
                @endforeach
            </colgroup>
            <thead>
                <tr class="rg-repeat">
                    @foreach($columns as $column)
                        <th style="background-color:#e8eef6;color:#1a365d;font-weight:bold;border-bottom:2px solid #1a365d;">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        @foreach($row['cells'] as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $columnCount }}">No records in this selection.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </main>
</body>
</html>

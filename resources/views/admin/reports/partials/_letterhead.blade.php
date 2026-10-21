{{--
    Shared letterhead for all generated PDF reports. Both logos are read from
    disk and inlined as base64 data URIs because dompdf cannot reliably fetch
    remote/public URLs at render time — this keeps the header self-contained
    and identical everywhere it's included.
--}}
@php
    $rgLeftLogo = public_path('images/letterhead/mdrrmo-logo.png');
    $rgRightLogo = public_path('images/letterhead/bayan-logo.png');
    $rgLeftLogoData = file_exists($rgLeftLogo) ? 'data:image/png;base64,'.base64_encode(file_get_contents($rgLeftLogo)) : null;
    $rgRightLogoData = file_exists($rgRightLogo) ? 'data:image/png;base64,'.base64_encode(file_get_contents($rgRightLogo)) : null;
@endphp
@php
    $rgLetterhead = \App\Services\ReportLetterhead::templateData();
    $rgMargins = $rgPageMargins ?? $rgLetterhead['page_margins_mm'];
    $rgColors = $rgLetterhead['colors'];
    $rgColumns = $rgLetterhead['columns'];
    $rgFontSizes = $rgLetterhead['font_sizes_pt'];
    $rgLineHeights = $rgLetterhead['line_heights'];
@endphp
<style>
    @page { margin: {{ $rgMargins['top'] }}mm {{ $rgMargins['horizontal'] }}mm {{ $rgMargins['bottom'] }}mm; }
    body { margin: 0 !important; padding: 0 !important; color: #1c2b36; font-family: {{ $rgLetterhead['font_family'] }}, sans-serif; font-size: {{ $rgLetterhead['body_font_size_pt'] }}pt; }
    table:not(.rg-letterhead-table) {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed;
        border-collapse: collapse;
    }
    table:not(.rg-letterhead-table) th,
    table:not(.rg-letterhead-table) td {
        overflow-wrap: break-word;
        word-wrap: break-word;
        vertical-align: top;
    }
    table.rg-letterhead-table,
    table.rg-letterhead-table tr,
    table.rg-letterhead-table td {
        border: none !important;
        border-width: 0 !important;
        background: transparent !important;
    }
    table.rg-letterhead-table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed;
        border-collapse: collapse;
        margin: 0 0 {{ $rgLetterhead['table_to_rule_gap_px'] }}px;
    }
    table.rg-letterhead-table td { padding: 0 {{ $rgLetterhead['cell_padding_px'] }}px; }
    .rg-letterhead-institution { color: #{{ $rgColors['ink'] }}; font-family: {{ $rgLetterhead['font_family'] }}, sans-serif; font-size: {{ $rgFontSizes['institution'] }}pt; line-height: {{ $rgLineHeights['institution'] }}; }
    .rg-letterhead-institution .rg-letterhead-country { color: #{{ $rgColors['muted'] }}; font-family: {{ $rgLetterhead['font_family'] }}, sans-serif; font-size: {{ $rgFontSizes['country'] }}pt; letter-spacing: .06em; }
    .rg-letterhead-institution .rg-letterhead-locality { font-family: {{ $rgLetterhead['font_family'] }}, sans-serif; font-weight: 700; }
    .rg-letterhead-institution .rg-letterhead-office { color: #{{ $rgColors['office'] }}; font-family: {{ $rgLetterhead['font_family'] }}, sans-serif; font-size: {{ $rgFontSizes['office'] }}pt; font-weight: 700; }
</style>
<table class="rg-letterhead-table" style="width:100%; max-width:100%; table-layout:fixed; border-collapse:collapse; margin-bottom:{{ $rgLetterhead['table_to_rule_gap_px'] }}px; border:none;">
    <colgroup>
        @foreach($rgColumns as $column)
            <col style="width:{{ $column }}%;">
        @endforeach
    </colgroup>
    <tr>
        <td style="width:{{ $rgColumns[0] }}%; vertical-align:middle; border:none; text-align:center;">
            @if($rgLeftLogoData)
                <img src="{{ $rgLeftLogoData }}" alt="MDRRMO Pamplona" style="width:{{ $rgLetterhead['seal_size_px'] }}px; height:{{ $rgLetterhead['seal_size_px'] }}px; object-fit:contain;">
            @endif
        </td>
        <td style="width:{{ $rgColumns[1] }}%; text-align:center; vertical-align:middle; border:none; padding:0 {{ $rgLetterhead['cell_padding_px'] }}px;">
            <div class="rg-letterhead-institution">
                <span class="rg-letterhead-country">{{ $rgLetterhead['country'] }}</span><br>
                <span class="rg-letterhead-locality">{{ $rgLetterhead['province'] }}</span><br>
                <span class="rg-letterhead-locality">{{ $rgLetterhead['municipality'] }}</span><br>
                <span class="rg-letterhead-office">{{ $rgLetterhead['office'] }}</span>
            </div>
            @isset($rgLetterheadTitle)
                <div class="rg-letterhead-title" style="font-family:{{ $rgLetterhead['font_family'] }}, sans-serif; font-size:{{ $rgFontSizes['title'] }}pt; line-height:{{ $rgLineHeights['title'] }}; font-weight:700; margin-top:{{ $rgLetterhead['title_gap_px'] }}px; color:#{{ $rgColors['office'] }};">{{ $rgLetterheadTitle }}</div>
            @endisset
        </td>
        <td style="width:{{ $rgColumns[2] }}%; vertical-align:middle; text-align:center; border:none;">
            @if($rgRightLogoData)
                <img src="{{ $rgRightLogoData }}" alt="Bayan ng Pamplona" style="width:{{ $rgLetterhead['seal_size_px'] }}px; height:{{ $rgLetterhead['seal_size_px'] }}px; object-fit:contain;">
            @endif
        </td>
    </tr>
</table>
<hr class="rg-letterhead-rule" style="border:none; border-top:{{ $rgLetterhead['rule_width_px'] }}px solid #{{ $rgColors['rule'] }}; margin:0 0 {{ $rgLetterhead['rule_gap_px'] }}px;">

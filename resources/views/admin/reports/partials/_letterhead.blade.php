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
<table class="rg-letterhead-table" style="width:100%; border-collapse:collapse; margin-bottom:14px;">
    <tr>
        <td style="width:70px; vertical-align:middle;">
            @if($rgLeftLogoData)
                <img src="{{ $rgLeftLogoData }}" alt="MDRRMO Pamplona" style="width:60px; height:60px;">
            @endif
        </td>
        <td style="text-align:center; vertical-align:middle;">
            <div style="font-size:9.5pt; text-transform:uppercase; line-height:1.35; color:#333;">
                Republic of the Philippines<br>
                Province of Cagayan<br>
                Municipality of Pamplona<br>
                <strong>MDRRMO Pamplona / RANIAG Operations Center</strong>
            </div>
            @isset($rgLetterheadTitle)
                <div style="font-size:15pt; font-weight:bold; margin-top:10px; color:#1a365d;">{{ $rgLetterheadTitle }}</div>
            @endisset
        </td>
        <td style="width:70px; vertical-align:middle; text-align:right;">
            @if($rgRightLogoData)
                <img src="{{ $rgRightLogoData }}" alt="Bayan ng Pamplona" style="width:60px; height:60px;">
            @endif
        </td>
    </tr>
</table>
<hr style="border:none; border-top:2px solid #1a365d; margin:0 0 16px;">

@php
    $columnRows = $rows ?? [];
    $columnMax = max(1, (int) collect($columnRows)->max('count'));
@endphp
@if($columnRows === [])
    <p class="no-data">No cases in this period for this section.</p>
@else
    <table style="width:100%; border-collapse:collapse; margin-bottom:16px;">
        <tr>
            @foreach($columnRows as $row)
                @php $height = max(4, (int) round(((int) $row['count']) / $columnMax * 72)); @endphp
                <td style="border:none; vertical-align:bottom; text-align:center; padding:4px 2px;">
                    <div style="font-size:8pt; font-weight:bold; color:#1a365d;">{{ $row['count'] }}</div>
                    <div style="margin:2px auto 0; width:16px; height:{{ $height }}px; background-color:{{ $color ?? '#1a365d' }};"></div>
                    <div style="font-size:7.5pt; color:#334155; margin-top:3px;">{{ $row['label'] }}</div>
                </td>
            @endforeach
        </tr>
    </table>
@endif

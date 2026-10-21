@if(collect($bars)->sum('count') > 0)
    <div class="rg-bars" role="img" aria-label="{{ $label }}">
        @php $max = max(1, (int) collect($bars)->max('count')); @endphp
        @foreach($bars as $bar)
            <div class="rg-bar">
                <span class="rg-bar-label">{{ $bar['label'] }}</span>
                <span class="rg-bar-track"><span class="rg-bar-fill" style="width: {{ (int) round($bar['count'] / $max * 100) }}%"></span></span>
                <span class="rg-bar-value">{{ $bar['count'] }}</span>
            </div>
        @endforeach
    </div>
@else
    <p class="rg-bar-empty mb-0">Nothing recorded for these dates.</p>
@endif

@if(!empty($quickReport['map']))
    @php $topMapArea = collect($quickReport['map'])->sortByDesc('count')->first(); @endphp
    <div class="quick-map-frame">
        <svg class="quick-map-svg" viewBox="0 0 760 520" role="img" aria-labelledby="quick-map-title quick-map-description">
            <title id="quick-map-title">Incident hotspot map of Pamplona</title>
            <desc id="quick-map-description">Barangay boundaries shaded by the number of reports during {{ $quickReport['period_label'] }}.</desc>
            @foreach($quickReport['map'] as $area)
                @php
                    $fill = match (true) {
                        $area['count'] >= 7 => '#e66c59',
                        $area['count'] >= 4 => '#f2b35e',
                        $area['count'] >= 2 => '#f5df91',
                        $area['count'] >= 1 => '#b8d7c7',
                        default => '#e9eff2',
                    };
                @endphp
                <path d="{{ $area['path'] }}" fill="{{ $fill }}" stroke="#607b87" stroke-width="1.2" fill-rule="evenodd">
                    <title>{{ $area['name'] }}: {{ $area['count'] }} reports</title>
                </path>
                @if($topMapArea && $area['name'] === $topMapArea['name'] && $area['count'] > 0)
                    <text x="{{ $area['label_x'] }}" y="{{ $area['label_y'] }}" text-anchor="middle" class="quick-map-label">{{ $area['name'] }} · {{ $area['count'] }}</text>
                @endif
            @endforeach
        </svg>
    </div>
    <div class="quick-map-legend" aria-label="Map intensity">
        <span><i class="legend-low"></i>None</span>
        <span><i class="legend-watch"></i>1</span>
        <span><i class="legend-elevated"></i>2–3</span>
        <span><i class="legend-high"></i>4–6</span>
        <span><i class="legend-critical"></i>7+</span>
    </div>
@else
    <div class="quick-map-unavailable">
        <i class="bi bi-map"></i>
        <span>Barangay boundary data is not available, so the map could not be drawn. The ranked hotspot details are still included below.</span>
    </div>
@endif

<section class="quick-report-summary">
    <p class="quick-report-reading">{{ $quickReport['reading'] }}</p>
    <div class="quick-report-metrics">
        <div><span>Reports this period</span><strong>{{ number_format($quickReport['metrics']['reports']) }}</strong></div>
        <div><span>Still open</span><strong>{{ number_format($quickReport['metrics']['open']) }}</strong></div>
        <div><span>Closed or resolved</span><strong>{{ number_format($quickReport['metrics']['resolved']) }}</strong></div>
        <div><span>Repeat hotspot areas</span><strong>{{ number_format($quickReport['metrics']['hotspots']) }}</strong></div>
        <div><span>Median time to first assignment</span><strong>{{ $quickReport['metrics']['first_assignment'] }}</strong></div>
        <div><span>People sheltered now</span><strong>{{ number_format($quickReport['metrics']['shelter_total']) }}</strong><small>{{ number_format($quickReport['metrics']['shelter_inside']) }} from Pamplona · {{ number_format($quickReport['metrics']['shelter_outside']) }} from outside</small></div>
    </div>
</section>

<section class="quick-report-section">
    <div class="quick-report-section-heading">
        <div><span class="quick-report-kicker">Geographic situation</span><h2>Hotspot map by barangay</h2></div>
        <span class="quick-report-section-note">{{ $quickReport['period_label'] }}</span>
    </div>
    @include('admin.reports.partials._quick_map', ['quickReport' => $quickReport])
    <p class="quick-report-method">{{ $quickReport['data_note'] }}</p>
</section>

<section class="quick-report-section">
    <div class="quick-report-section-heading">
        <div><span class="quick-report-kicker">Repeat incident signals</span><h2>Areas to review</h2></div>
        <span class="quick-report-count">{{ count($quickReport['hotspots']) }} areas</span>
    </div>
    @forelse($quickReport['hotspots'] as $hotspot)
        <article class="quick-hotspot">
            <div class="quick-hotspot-heading">
                <span class="quick-hotspot-rank">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                <div class="quick-hotspot-title">
                    <h3>{{ $hotspot['barangay'] }}</h3>
                    <span>{{ $hotspot['type'] }} · {{ $hotspot['count'] }} reports</span>
                </div>
                <span class="quick-risk-band band-{{ strtolower($hotspot['band']) }}">{{ $hotspot['band'] }} signal</span>
            </div>
            <p class="quick-hotspot-description">{{ $hotspot['description'] }}</p>
            <div class="quick-hotspot-facts">
                <span><strong>{{ $hotspot['count'] }}</strong> reports in period</span>
                <span><strong>{{ $hotspot['open'] }}</strong> still open</span>
                <span><strong>{{ $hotspot['latest_at'] ?? '—' }}</strong> latest report</span>
            </div>
            @if(!empty($hotspot['cases']))
                <div class="table-responsive quick-hotspot-cases">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Tracking number</th><th>Reported</th><th>Status</th><th>Report detail</th></tr></thead>
                        <tbody>
                            @foreach($hotspot['cases'] as $case)
                                <tr>
                                    <td>{{ $case['tracking'] }}</td>
                                    <td>{{ $case['reported_at'] }}</td>
                                    <td>{{ $case['status'] }}</td>
                                    <td>{{ $case['description'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($hotspot['count'] > count($hotspot['cases']))
                    <p class="quick-case-note">Showing {{ count($hotspot['cases']) }} case references from {{ $hotspot['count'] }} reports in this hotspot.</p>
                @endif
            @endif
        </article>
    @empty
        <div class="quick-no-hotspots">
            <i class="bi bi-check2-circle"></i>
            <div><strong>No repeat hotspots found in this period.</strong><p>There are no barangay and incident-type combinations with two or more reports.</p></div>
        </div>
    @endforelse
</section>

<div class="quick-report-bottom-grid">
    <section class="quick-report-section">
        <div class="quick-report-section-heading"><div><span class="quick-report-kicker">Incident mix</span><h2>Reports by type</h2></div></div>
        @include('admin.reports.partials.bars', ['bars' => $quickReport['type_counts'], 'label' => 'Reports by type'])
    </section>
    <section class="quick-report-section">
        <div class="quick-report-section-heading"><div><span class="quick-report-kicker">Geographic distribution</span><h2>Reports by barangay</h2></div></div>
        @include('admin.reports.partials.bars', ['bars' => $quickReport['barangay_counts'], 'label' => 'Reports by barangay'])
    </section>
</div>

<p class="quick-report-footnote">Prepared from RANIAG operational records · Generated {{ $quickReport['generated_at'] }} · Internal operational report</p>

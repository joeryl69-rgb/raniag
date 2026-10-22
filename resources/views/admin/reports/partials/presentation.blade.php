@php
    $exportQuery = request()->except('page');
    $columnCount = $presentation ? count($presentation['columns']) : 1;
@endphp

<section class="report-panel report-presentation-filters" aria-labelledby="presentation-title">
    <div class="report-panel-heading">
        <div>
            <span class="report-section-kicker">Gathered reports</span>
            <h2 id="presentation-title">Gathered reports</h2>
        </div>
    </div>
    <p>Choose a record set and refine it by place, incident type, dates, or search. Apply filters to show the matching rows.</p>

    <x-filters.toolbar
        search-placeholder="Search tracking number, name, or place"
        :action="route('admin.reports.index')"
        :clear-url="route('admin.reports.index')"
        :compact="true"
        :search-after-fields="true"
    >
        <input type="hidden" name="applied" value="1">
        <label class="report-presentation-filter">
            <span>Records</span>
            <select name="dataset" class="form-select" data-filter-default="incidents" data-presentation-dataset>
                <option value="incidents" @selected(request('dataset', 'incidents') !== 'evacuees')>Incident reports</option>
                <option value="evacuees" @selected(request('dataset') === 'evacuees')>Evacuee registry</option>
            </select>
        </label>
        <label class="report-presentation-filter">
            <span>Barangay</span>
            <select name="barangay" class="form-select" data-filter-default="">
                <x-place-filter-options :selected="request('barangay')" all-value="" />
            </select>
        </label>
        <label class="report-presentation-filter" data-presentation-type @class(['d-none' => request('dataset') === 'evacuees'])>
            <span>Incident type</span>
            <select name="incident_type_id" class="form-select" data-filter-default="" @disabled(request('dataset') === 'evacuees')>
                <option value="">All incident types</option>
                @foreach($incidentTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) request('incident_type_id') === (string) $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </label>
    </x-filters.toolbar>
</section>

@if($presentation)
    @php $page = $presentation['page']; @endphp
    <section class="report-panel" aria-labelledby="presentation-results" data-view-mode-root="incident-presentation" data-view-mode-user="{{ auth()->id() }}">
        <div class="report-panel-heading">
            <div>
                <span class="report-section-kicker">{{ $presentation['period'] }}</span>
                <h2 id="presentation-results">{{ $presentation['title'] }}</h2>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <x-view-mode-toggle register="incident-presentation" label="{{ $presentation['title'] }}" />
            </div>
        </div>
        <p class="mb-3">{{ $presentation['summary'] }}. Exports include all matching records, not only the rows on this page.</p>
        <section class="report-metrics report-selection-metrics" aria-label="Selection summary" data-report-metrics>
            @foreach($presentation['metrics'] as $index => $metric)
                @php
                    $metricIcons = ['bi-collection', 'bi-hourglass-split', 'bi-exclamation-triangle', 'bi-geo-alt'];
                    $metricTones = ['tone-blue', 'tone-amber', 'tone-rose', 'tone-green'];
                @endphp
                <div class="report-metric" data-report-metric="{{ $metric['label'] }}" data-report-metric-value="{{ $metric['value'] }}">
                    <span class="report-metric-icon {{ $metricTones[$index] ?? 'tone-blue' }}"><i class="bi {{ $metricIcons[$index] ?? 'bi-graph-up' }}"></i></span>
                    <div><span class="report-metric-label">{{ $metric['label'] }}</span><strong>{{ number_format($metric['value']) }}</strong><span class="report-metric-note">{{ $presentation['filters']['dataset'] === 'evacuees' ? 'Filtered shelter registry' : 'Filtered incident reports' }}</span></div>
                </div>
            @endforeach
        </section>

        <div class="report-export-area" aria-label="Download this selection">
            <div class="report-export-heading">
                <div><span class="report-section-kicker">Take the data with you</span><h3>Download a file</h3></div>
                <span class="report-export-note">Each export contains the full selection.</span>
            </div>
            <div class="report-export-grid" data-report-exports>
                <a class="report-export-card report-export-card-primary" href="{{ route('admin.reports.presentation.excel', array_merge($exportQuery, ['charts' => '0'])) }}">
                    <span class="report-export-icon"><i class="bi bi-file-earmark-spreadsheet"></i></span>
                    <span><strong>Excel table</strong><small>Sortable rows, without charts</small></span>
                    <i class="bi bi-arrow-down-circle report-export-arrow" aria-hidden="true"></i>
                </a>
                <a class="report-export-card" href="{{ route('admin.reports.presentation.excel', array_merge($exportQuery, ['charts' => '1'])) }}">
                    <span class="report-export-icon"><i class="bi bi-bar-chart-line"></i></span>
                    <span><strong>Excel + charts</strong><small>Rows and a chart summary sheet</small></span>
                    <i class="bi bi-arrow-down-circle report-export-arrow" aria-hidden="true"></i>
                </a>
                <a class="report-export-link" href="{{ route('admin.reports.presentation.word', $exportQuery) }}"><i class="bi bi-file-earmark-word"></i> Word document</a>
                <a class="report-export-link" href="{{ route('admin.reports.presentation.pdf', $exportQuery) }}"><i class="bi bi-file-earmark-pdf"></i> PDF document</a>
            </div>
        </div>

        <section class="report-presentation-area" aria-labelledby="report-presentation-heading">
            <div>
                <span class="report-section-kicker">Review &amp; present</span>
                <h3 id="report-presentation-heading">A clean, portrait-ready report</h3>
                <p>Open a print preview, check the pages, then print or save as PDF.</p>
            </div>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#report-print-modal"><i class="bi bi-printer me-1"></i>Open print preview</button>
        </section>
        <div class="modal fade" id="report-print-modal" tabindex="-1" aria-labelledby="report-print-title" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="report-print-title">Review and print report</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <iframe id="report-print-frame" title="Report preview" data-preview-base="{{ route('admin.reports.presentation.pdf', array_merge($exportQuery, ['inline' => '1'])) }}" style="width:100%; height:70vh; border:0; background:#fff;"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="report-print-now" disabled><i class="bi bi-printer me-1"></i>Print report</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive rg-data-table-wrap">
            <table class="table table-sm align-middle mb-0 rg-data-table">
                <thead>
                    <tr>
                        @foreach($presentation['columns'] as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($presentation['pageRows'] as $row)
                        <tr>
                            @foreach($row['cells'] as $index => $cell)
                                <td>
                                    @if($index === 0 && $row['url'])
                                        <a href="{{ $row['url'] }}">{{ $cell }}</a>
                                    @else
                                        {{ $cell }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $columnCount }}">No records in this selection.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($page->hasPages())
            <div class="mt-3">{{ $page->links('pagination::bootstrap-5') }}</div>
        @endif
    </section>
@endif

<script>
    document.querySelector('[data-presentation-dataset]')?.addEventListener('change', (event) => {
        const typeField = document.querySelector('[data-presentation-type]');
        const select = typeField?.querySelector('select');
        const evacuees = event.target.value === 'evacuees';
        typeField?.classList.toggle('d-none', evacuees);
        if (select) {
            select.disabled = evacuees;
        }
        select?.dispatchEvent(new Event('change', { bubbles: true }));
    });

    (() => {
        const frame = document.getElementById('report-print-frame');
        const modal = document.getElementById('report-print-modal');
        if (!frame || !modal) return;

        const printNow = document.getElementById('report-print-now');
        const loadPreview = () => {
            const src = frame.dataset.previewBase + '#toolbar=1&navpanes=0';
            if (frame.dataset.current !== src) {
                frame.dataset.current = src;
                if (printNow) printNow.disabled = true;
                frame.src = src;
            }
        };
        frame.addEventListener('load', () => {
            if (printNow) printNow.disabled = false;
        });

        modal.addEventListener('show.bs.modal', loadPreview);
        document.getElementById('report-print-now')?.addEventListener('click', () => {
            frame.contentWindow?.focus();
            frame.contentWindow?.print();
        });
    })();
</script>

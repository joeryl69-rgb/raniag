<x-app-layout>
    <x-slot name="header">{{ __('Operations reports') }}</x-slot>

@php
    $locked = $record?->isFiled() ?? false;
    $isBuilder = ! $record && $view === 'builder';
    $isOperationsTemplate = $template === 'operations';
    $periodWords = \Carbon\Carbon::parse($from)->format('F j, Y').' – '.\Carbon\Carbon::parse($to)->format('F j, Y');
    $chapterDescriptions = [
        'open_cases' => 'Incident reports received within the selected period.',
        'response' => 'Case volume, open workload, and first-assignment response time.',
        'repeats' => 'Barangays where the same incident type has occurred more than once.',
        'shelter' => 'People currently staying, or people who checked in during the period.',
        'map' => 'Current hazard areas and evacuation centers, independent of report dates.',
        'papers' => 'Closed cases and their required documents, including missing forms.',
        'texts' => 'SMS delivery outcomes from the office notification log.',
        'support' => 'Requests received through the public and staff support desks.',
        'notices' => 'Public notices and their publication status.',
        'activity' => 'A dated, attributable audit trail of system activity.',
        'one_case' => 'A focused case summary found by its existing tracking number.',
    ];
    $chapterGroups = [
        'Incident response' => ['open_cases', 'response', 'repeats', 'one_case'],
        'Shelter and readiness' => ['shelter', 'map'],
        'Case records' => ['papers'],
        'Public communication' => ['texts', 'notices', 'support'],
        'Office accountability' => ['activity'],
    ];
@endphp

<div class="report-hub">
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="status">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
    @endif

    <div class="report-hub-head">
        <div>
            <p class="report-eyebrow">{{ $record ? 'Official record · MDRRMO Pamplona' : 'RANIAG · REPORTS STUDIO' }}</p>
            <h1>{{ $record ? ($locked ? 'Filed report' : 'Report draft') : ($isBuilder ? ($isOperationsTemplate ? 'Daily operations and service report' : 'Build a report for a specific need') : 'Reports and field intelligence') }}</h1>
            <p class="report-intro">
                @if($record)
                    {{ $locked ? 'This filed copy is a fixed record of the data and rows used when it was filed.' : 'Set the reporting period and include only the operational records this document needs.' }}
                @elseif($isBuilder)
                    @if($isOperationsTemplate)
                        Review a ready-made collection of cases, shelter, missing papers, failed texts, support requests, public notices, and staff activity. Change filters only when needed.
                    @else
                        Start with the registers you need. You can leave the suggested settings as they are, save a draft, and file a PDF when it is ready.
                    @endif
                @else
                    Build a clear view of incident activity or shelter occupancy. Choose a dataset, refine the scope, then review or export the complete matching selection.
                @endif
            </p>
        </div>
        @if(! $record && ! $isBuilder)
            <div class="report-hub-mark" aria-hidden="true"><i class="bi bi-bar-chart-line"></i></div>
        @endif
    </div>

    @if($view === 'home' && ! $record)
        @include('admin.reports.partials.presentation')

    @elseif($view === 'overview' && ! $record)
        <section class="report-period-panel" aria-labelledby="period-title">
            <div class="report-period-copy">
                <div class="report-section-kicker">Operations picture</div>
                <h2 id="period-title">{{ $periodLabel }}</h2>
                <p>{{ $periodWords }} <span aria-hidden="true">·</span> {{ $picture['reading'] }}</p>
            </div>
            <div class="report-period-actions">
                <div class="btn-group report-period-switch" role="group" aria-label="Select reporting period">
                    @foreach(['week' => 'This week', 'month' => 'This month', 'year' => 'This year'] as $key => $label)
                        <a class="btn {{ $periodKey === $key ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.reports.index', ['period' => $key]) }}">{{ $label }}</a>
                    @endforeach
                </div>
                <a class="btn btn-outline-primary" href="{{ route('admin.reports.quick', ['period' => $periodKey]) }}">
                    <i class="bi bi-file-earmark-text me-1"></i>Review situation report
                </a>
            </div>
        </section>

        <section class="report-metrics" aria-label="Incident and shelter summary">
            <div class="report-metric">
                <span class="report-metric-icon tone-blue"><i class="bi bi-clipboard2-pulse"></i></span>
                <div><span class="report-metric-label">Reports received</span><strong>{{ number_format($picture['reports']) }}</strong><span class="report-metric-note">{{ number_format($picture['outside']) }} referred outside Pamplona</span></div>
            </div>
            <div class="report-metric">
                <span class="report-metric-icon tone-amber"><i class="bi bi-hourglass-split"></i></span>
                <div><span class="report-metric-label">Still open</span><strong>{{ number_format($picture['open']) }}</strong><span class="report-metric-note">Cases needing follow-up</span></div>
            </div>
            <div class="report-metric">
                <span class="report-metric-icon tone-green"><i class="bi bi-stopwatch"></i></span>
                <div><span class="report-metric-label">First team assigned</span><strong>{{ str_starts_with($picture['median'], 'Not enough') ? '—' : $picture['median'] }}</strong><span class="report-metric-note">{{ str_starts_with($picture['median'], 'Not enough') ? 'No assignment recorded yet' : 'Median response time' }}</span></div>
            </div>
            <div class="report-metric">
                <span class="report-metric-icon tone-rose"><i class="bi bi-house-heart"></i></span>
                <div><span class="report-metric-label">Shelter guests now</span><strong>{{ number_format($picture['shelter_inside'] + $picture['shelter_outside']) }}</strong><span class="report-metric-note">{{ number_format($picture['shelter_outside']) }} from outside Pamplona</span></div>
            </div>
        </section>

        <section class="report-overview-grid" aria-label="Incident trends">
            <article class="report-panel">
                <div class="report-panel-heading"><div><span class="report-section-kicker">Incident demand</span><h2>What came in</h2></div><span class="report-panel-icon"><i class="bi bi-bar-chart-line"></i></span></div>
                <p>Reports grouped by incident type during {{ strtolower($periodLabel) }}.</p>
                @include('admin.reports.partials.bars', ['bars' => $picture['by_type'], 'label' => 'Incidents by type'])
            </article>
            <article class="report-panel">
                <div class="report-panel-heading"><div><span class="report-section-kicker">Geographic activity</span><h2>Where reports occurred</h2></div><span class="report-panel-icon"><i class="bi bi-geo-alt"></i></span></div>
                <p>Barangays with the most reported incidents in this period.</p>
                @include('admin.reports.partials.bars', ['bars' => $picture['by_place'], 'label' => 'Reports by barangay'])
            </article>
            <article class="report-panel">
                <div class="report-panel-heading"><div><span class="report-section-kicker">Response workload</span><h2>How the office answered</h2></div><span class="report-panel-icon"><i class="bi bi-arrow-repeat"></i></span></div>
                <p>Open cases compared with all incident reports received.</p>
                @include('admin.reports.partials.bars', ['bars' => $picture['response'], 'label' => 'Open and total reports'])
            </article>
            <article class="report-panel">
                <div class="report-panel-heading"><div><span class="report-section-kicker">Evacuation support</span><h2>People staying in shelters</h2></div><span class="report-panel-icon"><i class="bi bi-house-check"></i></span></div>
                <p>Current roll, including people whose home is outside Pamplona.</p>
                @include('admin.reports.partials.bars', ['bars' => [
                    ['label' => 'From Pamplona', 'count' => $picture['shelter_inside']],
                    ['label' => 'From outside Pamplona', 'count' => $picture['shelter_outside']],
                ], 'label' => 'People in shelters'])
            </article>
        </section>

        <section class="report-panel report-streams">
            <div class="report-panel-heading">
                <div><span class="report-section-kicker">Beyond the dashboard</span><h2>What the system is recording</h2></div>
                <a href="{{ route('admin.reports.create', ['template' => 'operations']) }}" class="report-inline-link">Prepare an operations record <i class="bi bi-arrow-right"></i></a>
            </div>
            <p>Each count comes from its own operational register. Add any of these records to a filed document when they matter.</p>
            <div class="report-stream-table">
                @foreach($sources as $source)
                    <div class="report-stream-row">
                        <span class="report-stream-icon"><i class="bi {{ $source['icon'] }}"></i></span>
                        <span class="report-stream-label"><strong>{{ $source['label'] }}</strong><small>{{ $source['detail'] }}</small></span>
                        <strong class="report-stream-total">{{ number_format($source['total']) }}</strong>
                        <span class="report-stream-period">{{ $source['type'] === 'map' || $source['type'] === 'shelter' ? 'Current' : $periodWords }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="report-panel report-recent">
            <div class="report-panel-heading">
                <div><span class="report-section-kicker">Working documents</span><h2>Recent reports</h2></div>
            </div>
            @if($records->isEmpty())
                <div class="report-empty-state"><i class="bi bi-inbox"></i><div><strong>No reports saved yet</strong><p>Choose a situation report or an operations and services record from the report home.</p></div></div>
            @else
                <div class="table-responsive">
                    <table class="table report-table align-middle mb-0">
                        <thead><tr><th>Document</th><th>Reporting period</th><th>Prepared by</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @foreach($records->take(6) as $item)
                                <tr>
                                    <td><strong>{{ $item->title }}</strong><small>{{ count($item->chapters ?? []) }} sections · {{ $item->created_at?->format('M j, Y g:i a') }}</small></td>
                                    <td>{{ $item->date_from->format('M j, Y') }} – {{ $item->date_to->format('M j, Y') }}</td>
                                    <td>{{ $item->user?->name ?? 'Former account' }}</td>
                                    <td><span class="report-status {{ $item->isFiled() ? 'status-filed' : 'status-draft' }}">{{ $item->isFiled() ? 'Filed' : 'Draft' }}</span></td>
                                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ $item->isFiled() ? route('admin.reports.records.show', $item) : route('admin.reports.records.edit', $item) }}">{{ $item->isFiled() ? 'View' : 'Edit' }}</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

    @elseif($isBuilder || ($record && ! $locked))
        <form method="POST" action="{{ $record ? route('admin.reports.records.save', $record) : route('admin.reports.store') }}" class="report-builder-form" data-report-builder>
            @csrf

            @if($errors->any())
                <div class="alert alert-danger report-error-summary" role="alert" tabindex="-1">
                    <strong>We could not save this report. Correct the issues below and try again.</strong>
                    <ul class="mb-0 mt-2">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                </div>
            @endif

            <section class="report-panel report-details-panel">
                <div class="report-panel-heading">
                    <div><span class="report-section-kicker">1 · Document details</span><h2>Identify the report</h2></div>
                    <span class="report-step-mark">Step 1 of 2</span>
                </div>
                <div class="row g-3">
                    <div class="col-lg-7">
                        <label for="report-title" class="form-label">Report title <span class="text-danger">*</span></label>
                        <input id="report-title" name="title" value="{{ $title ?: ($record ? '' : old('title')) }}" maxlength="120" minlength="3" class="form-control form-control-lg @error('title') is-invalid @enderror" placeholder="For example: Weekly operations report" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label for="report-from" class="form-label">Dates covered · from <span class="text-danger">*</span></label>
                        <input id="report-from" type="date" name="date_from" value="{{ $from }}" max="{{ now()->toDateString() }}" class="form-control @error('date_from') is-invalid @enderror" required>
                        @error('date_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label for="report-to" class="form-label">To <span class="text-danger">*</span></label>
                        <input id="report-to" type="date" name="date_to" value="{{ $to }}" max="{{ now()->toDateString() }}" class="form-control @error('date_to') is-invalid @enderror" required>
                        @error('date_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <p class="report-form-help"><i class="bi bi-info-circle me-1"></i>Use dates up to one year apart. Data is read from the selected system registers; map and current shelter sections show their current state.</p>
            </section>

            <section class="report-panel report-sections-panel">
                <div class="report-panel-heading">
                <div><span class="report-section-kicker">2 · Contents</span><h2>Sections in this report</h2></div>
                    <span class="report-step-mark"><span data-chapter-count>{{ count($chapters) }}</span> / 12 sections</span>
                </div>
                <p class="report-panel-intro">
                    @if($isOperationsTemplate)
                        This ready-made report covers case follow-up, shelter, missing papers, failed messages, support, notices, and staff activity. The suggested filters are optional.
                    @else
                        Open cases, the shelter roll, and missing case papers are included to start. Add or remove sections to match the request; suggested filters can be left unchanged.
                    @endif
                </p>
                @if($record)
                    <div class="report-preview-hint"><i class="bi bi-info-circle me-1"></i>Rows below reflect the last saved settings. Save your changes to refresh the preview.</div>
                @else
                    <div class="report-preview-hint"><i class="bi bi-info-circle me-1"></i>Suggested sections and filters are already selected. Open “Optional filters” only if you need to narrow a section.</div>
                @endif

                <div class="report-add-row">
                    <div>
                        <label for="chapter-type" class="form-label">Add a section</label>
                        <select id="chapter-type" class="form-select">
                            @foreach($chapterGroups as $group => $types)
                                <optgroup label="{{ $group }}">
                                    @foreach($types as $type)
                                        <option value="{{ $type }}">{{ \App\Support\ReportChapterCatalog::title($type) }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-outline-primary" type="button" data-add-chapter><i class="bi bi-plus-lg me-1"></i>Add section</button>
                    <span class="small text-muted ms-auto" data-chapter-capacity></span>
                </div>

                <div class="report-chapter-list" data-chapter-list>
                    @foreach($chapters as $index => $chapter)
                        @include('admin.reports.partials._chapter_editor', [
                            'chapter' => $chapter,
                            'index' => $index,
                            'locked' => false,
                            'renderedChapter' => $rendered[$index] ?? null,
                        ])
                    @endforeach
                </div>
                @error('chapters')<div class="text-danger small mt-2">{{ $message }}</div>@enderror

                @if(! $record)
                    <div class="report-builder-actions">
                        <span><i class="bi bi-info-circle me-1"></i>Save a draft to keep editing later, or file now to create a locked PDF.</span>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-outline-primary" type="submit" name="action" value="save">
                                <i class="bi bi-floppy me-1"></i>Save draft
                            </button>
                            <button class="btn btn-primary btn-lg" type="submit" name="action" value="file">
                                <i class="bi bi-file-earmark-check me-1"></i>File report and save PDF
                            </button>
                        </div>
                    </div>
                @else
                    <div class="report-builder-actions">
                        <span><i class="bi bi-info-circle me-1"></i>Save changes to keep editing. Filing saves a locked PDF of this copy.</span>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-floppy me-1"></i>Save changes</button>
                            <button class="btn btn-primary" type="submit" name="action" value="file" formaction="{{ route('admin.reports.file', $record) }}"><i class="bi bi-file-earmark-check me-1"></i>File report and save PDF</button>
                        </div>
                    </div>
                    <div class="report-destructive-action">
                        <span>Do not need this draft?</span>
                        <button class="btn btn-link text-danger p-0" type="submit" form="delete-report-draft"><i class="bi bi-trash me-1"></i>Discard draft</button>
                    </div>
                @endif
            </section>
        </form>

        @if($record)
            <form id="delete-report-draft" method="POST" action="{{ route('admin.reports.records.destroy', $record) }}" onsubmit="return confirm('Delete this draft permanently?')">
                @csrf
                @method('DELETE')
            </form>
        @endif

        @foreach($chapterGroups as $group => $types)
            @foreach($types as $type)
                @php
                    $templateChapter = ['key' => '__KEY__', 'type' => $type, 'options' => \App\Support\ReportChapterCatalog::defaults($type)];
                    $templateIndex = '__INDEX__';
                @endphp
                <template data-chapter-template="{{ $type }}">
                    @include('admin.reports.partials._chapter_editor', [
                        'chapter' => $templateChapter,
                        'index' => $templateIndex,
                        'locked' => false,
                        'renderedChapter' => null,
                    ])
                </template>
            @endforeach
        @endforeach

    @elseif($record && $locked && $record->report_kind === 'quick')
        <article class="report-filed-document quick-report-document">
            <header class="quick-document-cover">
                <div class="quick-letterhead">
                    <span class="quick-mark"><i class="bi bi-shield-fill-check"></i></span>
                    <div><strong>MDRRMO PAMPLONA</strong><small>Operations and incident coordination</small></div>
                    <span class="quick-document-type">SITUATION REPORT · FILED</span>
                </div>
                <h2>{{ $record->title }}</h2>
                <p>{{ $periodWords }} <span>·</span> Filed {{ $record->filed_at?->format('M j, Y g:i a') }}</p>
                <div class="report-filed-actions">
                    <a class="btn btn-primary" href="{{ route('admin.reports.download', $record) }}"><i class="bi bi-download me-2"></i>Download PDF</a>
                    <a class="btn btn-outline-primary" href="{{ route('admin.reports.quick', ['period' => 'month']) }}"><i class="bi bi-lightning-charge me-2"></i>Prepare another quick report</a>
                </div>
            </header>
            @include('admin.reports.partials._quick_report_content', ['quickReport' => $quickReport])
            <footer class="report-filed-footer"><span><i class="bi bi-lock me-1"></i>Filed quick reports preserve the metrics, map, hotspot details, and cases from the time of filing.</span></footer>
        </article>

    @elseif($record && $locked)
        <article class="report-filed-document">
            <header class="report-filed-heading">
                <div class="report-filed-brand"><i class="bi bi-shield-check"></i><span>MDRRMO <b>PAMPLONA</b></span></div>
                <div class="report-filed-meta">
                    <span class="report-status status-filed">Filed</span>
                    <span>Filed {{ $record->filed_at?->format('M j, Y g:i a') }}</span>
                    <span>Prepared by {{ $record->user?->name ?? 'Former account' }}</span>
                </div>
                <h2>{{ $record->title }}</h2>
                <p>{{ $periodWords }}</p>
                <div class="report-filed-actions">
                    <a class="btn btn-primary" href="{{ route('admin.reports.download', $record) }}"><i class="bi bi-download me-2"></i>Download filed PDF</a>
                    <form method="POST" action="{{ route('admin.reports.duplicate', $record) }}">
                        @csrf
                        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-copy me-2"></i>Copy into a new draft</button>
                    </form>
                </div>
            </header>
            <div class="report-filed-body">
                @foreach($rendered as $chapter)
                    <section class="report-filed-chapter">
                        <div class="report-filed-chapter-title">
                            <div><span class="report-section-kicker">Section {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h3>{{ $chapter['title'] }}</h3></div>
                            <span class="report-row-count">{{ number_format($chapter['total']) }} {{ $chapter['total'] === 1 ? 'row' : 'rows' }}</span>
                        </div>
                        @if($chapter['note'] !== '')<p class="small text-muted">{{ $chapter['note'] }}</p>@endif
                        @if(! empty($chapter['bars']))
                            @include('admin.reports.partials.bars', ['bars' => $chapter['bars'], 'label' => $chapter['title']])
                        @endif
                        @if(! empty($chapter['rows']))
                            <div class="table-responsive">
                                <table class="table table-sm report-table align-middle mb-0">
                                    <thead><tr>@foreach($chapter['columns'] as $column)<th>{{ $column }}</th>@endforeach</tr></thead>
                                    <tbody>@foreach($chapter['preview'] ?? $chapter['rows'] as $row)<tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody>
                                </table>
                                @if($chapter['total'] > count($chapter['preview'] ?? []))
                                    <p class="small text-muted mt-2 mb-0">The filed PDF contains all {{ number_format($chapter['total']) }} rows.</p>
                                @endif
                            </div>
                        @else
                            <p class="report-no-rows">No rows were recorded for this section when the report was filed.</p>
                        @endif
                        @if(in_array($chapter['type'], \App\Support\ReportChapterCatalog::EXPORTABLE, true))
                            <form method="POST" action="{{ route('admin.reports.export', [$record, $chapter['key']]) }}" class="mt-3">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Export these rows</button>
                            </form>
                        @endif
                    </section>
                @endforeach
            </div>
            <footer class="report-filed-footer">
                <span><i class="bi bi-info-circle me-1"></i>This is the filed snapshot. Later system changes do not alter this document.</span>
                @if($record->chapters === [])
                    <span>No sections in this record.</span>
                @endif
            </footer>
        </article>
    @endif
</div>

@push('styles')
@if($quickReport)
<link rel="stylesheet" href="{{ asset('css/quick-reports.css') }}?v={{ @filemtime(public_path('css/quick-reports.css')) }}">
@endif
<style>
.report-hub{--report-ink:var(--raniag-ink,#172b3a);--report-muted:var(--raniag-muted,#627486);--report-border:var(--raniag-border,#dce5eb);--report-surface:var(--raniag-card,#fff);--report-blue:var(--raniag-primary,#0d5fba);color:var(--report-ink);max-width:1440px;margin:0 auto}
.report-hub h1{font-size:1.55rem;font-weight:750;letter-spacing:-.025em;margin:0}
.report-hub h2{font-size:1.08rem;font-weight:700;margin:0}
.report-hub p{color:var(--report-muted)}
.report-hub-head{display:flex;justify-content:space-between;align-items:flex-end;gap:1rem;padding:.3rem 0 1.15rem}
.report-hub-head h1{font-size:clamp(1.55rem,2.5vw,2.15rem);letter-spacing:-.045em;font-weight:800;color:var(--report-ink);margin:.08rem 0 .35rem}
.report-hub-head .report-intro{max-width:47rem;margin:0;color:var(--report-muted);font-size:.86rem;line-height:1.55}
.report-hub-mark{width:3.2rem;height:3.2rem;display:grid;place-items:center;flex:0 0 auto;border:1px solid var(--report-border);border-radius:1rem;background:color-mix(in srgb,var(--report-blue) 8%,var(--report-surface));color:var(--report-blue);font-size:1.35rem}
.report-eyebrow,.report-section-kicker{font-size:.68rem;letter-spacing:.1em;font-weight:800;text-transform:uppercase;color:var(--report-blue);margin:0 0 .35rem}
.report-intro{margin:.45rem 0 0;max-width:48rem;font-size:.92rem}
.report-primary-action{white-space:nowrap;padding:.68rem 1rem}
.report-choice-intro{margin:1.1rem 0 1rem}.report-choice-intro h2{font-size:1.25rem;margin:.2rem 0}.report-choice-intro p{font-size:.87rem;margin:.35rem 0 0;max-width:48rem}
.report-choice-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
.report-choice-card{display:flex;flex-direction:column;align-items:flex-start;min-height:300px;padding:1.35rem;text-decoration:none;color:var(--report-ink);background:var(--report-surface);border:1px solid var(--report-border);border-radius:15px;box-shadow:0 2px 8px rgba(17,45,68,.035);transition:transform .15s ease,border-color .15s ease,box-shadow .15s ease}
.report-choice-card:hover{transform:translateY(-2px);border-color:var(--report-blue);box-shadow:0 10px 24px rgba(17,45,68,.09);color:var(--report-ink)}
.report-choice-quick{border-color:color-mix(in srgb,var(--report-blue) 45%,var(--report-border));background:linear-gradient(150deg,color-mix(in srgb,var(--report-blue) 5%,var(--report-surface)),var(--report-surface) 60%)}
.report-choice-icon{display:grid;place-items:center;width:46px;height:46px;border-radius:12px;background:var(--report-blue);color:#fff;font-size:1.2rem;margin-bottom:.9rem}
.report-choice-custom-icon{background:color-mix(in srgb,var(--report-blue) 12%,var(--report-surface));color:var(--report-blue)}
.report-choice-kicker{font-size:.68rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--report-blue)}
.report-choice-card h2{font-size:1.25rem;margin:.32rem 0}.report-choice-card>p{font-size:.85rem;max-width:36rem;margin:0 0 1rem}
.report-choice-includes{font-size:.78rem;color:var(--report-muted);margin:.2rem 0}.report-choice-includes i{color:#188052;margin-right:.35rem}
.report-choice-link{display:flex;align-items:center;gap:.55rem;margin-top:auto;padding-top:1.1rem;color:var(--report-blue);font-size:.85rem;font-weight:750}
.report-choice-archive{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin:1rem 0;padding:1rem 1.2rem;border:1px solid var(--report-border);border-radius:13px;background:var(--report-surface)}
.report-choice-archive h2{font-size:.95rem}.report-choice-archive p{font-size:.78rem;margin:.2rem 0 0}
.report-tabs{margin-bottom:1.2rem}
.report-tabs .nav-link.is-active{animation:rg-workspace-tab-enter .2s ease both}
.report-tab-count{font-size:.72rem;border-radius:999px;background:var(--report-border);padding:.1rem .45rem;color:var(--report-ink)}
.report-period-panel,.report-panel{background:var(--report-surface);border:1px solid var(--report-border);border-radius:14px;box-shadow:0 2px 8px rgba(17,45,68,.035)}
.report-period-panel{display:flex;justify-content:space-between;align-items:center;gap:1.2rem;padding:1rem 1.15rem;margin-bottom:1rem}
.report-period-copy h2{font-size:1.12rem}.report-period-copy p{font-size:.82rem;margin:.25rem 0 0}
.report-period-actions{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap;justify-content:flex-end}
.report-period-switch .btn{font-size:.77rem;padding:.36rem .58rem}
.report-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;margin-bottom:1rem}
.report-metric{display:flex;gap:.75rem;align-items:flex-start;min-height:110px;padding:.9rem;background:var(--report-surface);border:1px solid var(--report-border);border-radius:13px}
.report-metric-icon,.report-panel-icon{width:36px;height:36px;display:grid;place-items:center;border-radius:10px;flex:0 0 auto;font-size:1rem}
.tone-blue{background:#e7f1ff;color:#145da0}.tone-amber{background:#fff3dd;color:#a45706}.tone-green{background:#e6f7ee;color:#18794e}.tone-rose{background:#ffeaee;color:#b8324a}
.report-metric>div{min-width:0;display:flex;flex-direction:column;gap:.08rem}
.report-metric-label{font-size:.74rem;color:var(--report-muted);font-weight:650}
.report-metric strong{font-size:1.45rem;line-height:1.45;font-variant-numeric:tabular-nums}
.report-selection-metrics .report-metric{min-height:88px;padding:.72rem .8rem;gap:.65rem}
.report-selection-metrics .report-metric-icon{width:32px;height:32px}
.report-selection-metrics .report-metric strong{font-size:1.28rem;line-height:1.3}
.report-metric-note{font-size:.68rem;color:var(--report-muted)}
.report-export-area,.report-presentation-area{border:1px solid var(--report-border);border-radius:14px;background:var(--report-surface);padding:1rem 1.05rem;margin:0 0 .85rem}
.report-export-heading{display:flex;justify-content:space-between;align-items:end;gap:.8rem;margin-bottom:.7rem}
.report-export-heading h3,.report-presentation-area h3{font-size:.96rem;font-weight:750;margin:0;color:var(--report-ink)}
.report-export-note,.report-presentation-area p{font-size:.73rem;color:var(--report-muted);margin:0}
.report-export-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.55rem}
.report-export-card{display:flex;align-items:center;gap:.7rem;min-height:68px;padding:.65rem .8rem;border:1px solid var(--report-border);border-radius:10px;color:var(--report-ink);text-decoration:none;transition:border-color .15s ease,background .15s ease}
.report-export-card:hover{border-color:var(--report-blue);background:color-mix(in srgb,var(--report-blue) 4%,var(--report-surface));color:var(--report-ink)}
.report-export-card-primary{border-color:color-mix(in srgb,var(--report-blue) 50%,var(--report-border));background:color-mix(in srgb,var(--report-blue) 4%,var(--report-surface))}
.report-export-icon{display:grid;place-items:center;width:36px;height:36px;flex:0 0 auto;border-radius:9px;background:color-mix(in srgb,var(--report-blue) 11%,var(--report-surface));color:var(--report-blue);font-size:1.05rem}
.report-export-card>span:nth-child(2){display:flex;flex-direction:column;gap:.1rem;min-width:0}
.report-export-card strong{font-size:.8rem}.report-export-card small{font-size:.68rem;color:var(--report-muted)}
.report-export-arrow{margin-left:auto;color:var(--report-blue);font-size:1rem}
.report-presentation-area{display:flex;justify-content:space-between;align-items:center;gap:1rem;background:color-mix(in srgb,var(--report-blue) 3%,var(--report-surface))}
.report-presentation-area p{margin-top:.2rem}.report-presentation-area .btn{white-space:nowrap}
.report-metric-note{font-size:.7rem;color:var(--report-muted)}
.report-overview-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem;margin-bottom:.9rem}
.report-panel{padding:1rem 1.05rem;margin-bottom:.9rem}
.report-overview-grid .report-panel{margin:0;min-height:215px}
.report-panel-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:.8rem;margin-bottom:.55rem}
.report-panel-heading h2{margin-top:.1rem}
.report-panel>p,.report-panel-intro{font-size:.78rem;margin:0 0 .85rem}
.report-preview-hint{font-size:.75rem;color:var(--report-muted);padding:.55rem .7rem;border-left:3px solid var(--report-blue);background:color-mix(in srgb,var(--report-blue) 5%,transparent);margin:.7rem 0}
.report-panel-icon{background:color-mix(in srgb,var(--report-blue) 9%,transparent);color:var(--report-blue)}
.report-streams{padding-bottom:.45rem}
.report-inline-link{font-size:.8rem;font-weight:650;text-decoration:none;white-space:nowrap}
.report-stream-table{border-top:1px solid var(--report-border)}
.report-stream-row{display:grid;grid-template-columns:34px minmax(180px,1fr) 70px minmax(110px,.55fr);align-items:center;gap:.75rem;padding:.62rem .15rem;border-bottom:1px solid var(--report-border)}
.report-stream-icon{width:30px;height:30px;display:grid;place-items:center;border-radius:9px;background:color-mix(in srgb,var(--report-blue) 8%,transparent);color:var(--report-blue)}
.report-stream-label{display:flex;flex-direction:column;gap:.08rem}.report-stream-label strong{font-size:.82rem}.report-stream-label small,.report-stream-period{font-size:.7rem;color:var(--report-muted)}
.report-stream-total{text-align:right;font-size:1rem;font-variant-numeric:tabular-nums}.report-stream-period{text-align:right}
.report-empty-state{display:flex;align-items:center;gap:1rem;padding:1.1rem .2rem;color:var(--report-muted)}
.report-empty-state>i{font-size:1.5rem;color:var(--report-blue)}.report-empty-state p{font-size:.8rem;margin:.15rem 0 0}.report-empty-state .btn{margin-left:auto}
.report-table{font-size:.8rem}.report-table thead th{font-size:.67rem;letter-spacing:.06em;text-transform:uppercase;color:var(--report-muted);background:color-mix(in srgb,var(--report-border) 30%,transparent);white-space:nowrap}
.report-table td small{display:block;color:var(--report-muted);font-size:.7rem;margin-top:.15rem}.report-status{display:inline-flex;align-items:center;border-radius:100px;padding:.22rem .58rem;font-size:.7rem;font-weight:750;white-space:nowrap}
.status-filed{background:#e7f6ee;color:#176a42}.status-draft{background:#fff2d8;color:#88530a}
.report-archive-filters{display:flex;align-items:end;gap:.6rem;margin-bottom:1rem}.report-archive-filters>div:not(.report-search-field){min-width:150px}
.report-search-field{flex:1;position:relative}.report-search-field>i{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--report-muted)}.report-search-field input{padding-left:2.25rem}
.report-pagination{padding-top:1rem}
.report-details-panel,.report-sections-panel{padding:1.2rem;margin-bottom:1rem}
.report-step-mark{font-size:.72rem;font-weight:700;color:var(--report-muted);white-space:nowrap}
.report-form-help{margin:.8rem 0 0;font-size:.76rem}
.report-add-row{display:flex;align-items:end;gap:.65rem;padding:.8rem;background:color-mix(in srgb,var(--report-border) 24%,transparent);border:1px solid var(--report-border);border-radius:11px;margin:.95rem 0}
.report-add-row>div{width:min(100%,27rem)}.report-add-row .form-label{font-size:.75rem;font-weight:700;margin-bottom:.25rem}
.report-chapter-list{display:flex;flex-direction:column;gap:.7rem}
.report-chapter{border:1px solid var(--report-border);border-radius:12px;background:var(--report-surface);overflow:hidden}
.report-chapter-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 1rem;background:color-mix(in srgb,var(--report-border) 18%,transparent)}
.report-chapter-controls{display:flex;align-items:center;gap:.3rem;flex-shrink:0}.report-chapter-controls .btn{font-size:.73rem}
.report-chapter-heading h3{margin:0}.report-chapter-number{display:grid;place-items:center;width:29px;height:29px;background:var(--report-blue);color:#fff;border-radius:8px;font-size:.72rem;font-weight:800}
.report-chapter-settings{padding:0;border-top:1px solid var(--report-border)}
.report-chapter-settings>summary{display:flex;align-items:center;gap:.5rem;padding:.75rem 1rem;cursor:pointer;list-style:none;color:var(--report-blue);font-size:.77rem;font-weight:700}
.report-chapter-settings>summary::-webkit-details-marker{display:none}
.report-chapter-settings>summary:before{content:"+";display:grid;place-items:center;width:19px;height:19px;border:1px solid var(--report-border);border-radius:50%;font-size:.9rem;line-height:1}
.report-chapter-settings[open]>summary:before{content:"−"}
.report-chapter-settings>summary span{color:var(--report-muted);font-size:.7rem;font-weight:400}
.report-chapter-settings-fields{padding:.1rem 1rem .9rem}
.report-destructive-action{display:flex;justify-content:flex-end;align-items:center;gap:.5rem;margin-top:.6rem;font-size:.73rem;color:var(--report-muted)}
.report-specific-link{margin:1rem 0 1.25rem;text-align:center;font-size:.83rem;color:var(--report-muted)}
.report-specific-link a{font-weight:700}
.report-chapter-errors{padding:.65rem 1rem;background:#fff0f0;color:#9b1c1c;font-size:.76rem;border-top:1px solid #f1c0c0}
.report-chapter-errors ul{padding-left:1.2rem}
.report-chapter-settings .form-label{font-size:.73rem;font-weight:700;color:var(--report-muted)}
.report-preview{border-top:1px solid var(--report-border);padding:.75rem 1rem}
.report-preview-heading{display:flex;justify-content:space-between;gap:.8rem;margin-bottom:.45rem}.report-preview .table{font-size:.75rem}
.report-preview .table th{font-size:.66rem;color:var(--report-muted);text-transform:uppercase;letter-spacing:.04em}
.report-builder-actions{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--report-border)}
.report-builder-actions>span{font-size:.75rem;color:var(--report-muted)}.report-error-summary{margin-bottom:1rem}
.report-filed-document{background:var(--report-surface);border:1px solid var(--report-border);border-radius:15px;overflow:hidden}
.report-filed-heading{padding:1.5rem 1.6rem 1.2rem;border-bottom:3px solid #173d66;background:linear-gradient(180deg,color-mix(in srgb,var(--report-blue) 5%,var(--report-surface)),var(--report-surface))}
.report-filed-brand{display:flex;align-items:center;gap:.5rem;color:#173d66;font-size:.7rem;letter-spacing:.1em;font-weight:700}
.report-filed-brand i{font-size:1.3rem}.report-filed-meta{display:flex;flex-wrap:wrap;align-items:center;gap:.65rem;margin:.9rem 0}
.report-filed-meta>span:not(.report-status){font-size:.72rem;color:var(--report-muted)}
.report-filed-heading h2{font-size:1.5rem;margin:.6rem 0 .15rem}.report-filed-heading>p{margin:0;font-weight:650}
.report-filed-actions{display:flex;gap:.55rem;flex-wrap:wrap;margin-top:1rem}
.report-filed-body{padding:0 1.6rem}.report-filed-chapter{padding:1.15rem 0;border-bottom:1px solid var(--report-border)}
.report-filed-chapter-title{display:flex;justify-content:space-between;align-items:center;gap:.8rem;margin-bottom:.6rem}.report-filed-chapter-title h3{font-size:1rem;margin:0}
.report-row-count{font-size:.72rem;font-weight:700;color:var(--report-muted);white-space:nowrap}
.report-no-rows{font-size:.8rem;color:var(--report-muted);font-style:italic;margin:0}
.report-filed-footer{display:flex;justify-content:space-between;gap:1rem;padding:.9rem 1.6rem;background:color-mix(in srgb,var(--report-border) 20%,transparent);font-size:.72rem;color:var(--report-muted)}
@media(max-width:900px){.report-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.report-period-panel{align-items:flex-start;flex-direction:column}.report-period-actions{justify-content:flex-start}}
@media(max-width:620px){.report-hub-head{align-items:flex-start;flex-direction:column}.report-overview-grid{grid-template-columns:1fr}.report-stream-row{grid-template-columns:30px 1fr 50px;gap:.55rem}.report-stream-period{display:none}.report-archive-filters{align-items:stretch;flex-direction:column}.report-archive-filters>div:not(.report-search-field){width:100%}.report-add-row{align-items:stretch;flex-wrap:wrap}.report-add-row>div{width:100%}.report-add-row .ms-auto{width:100%;margin:0!important}.report-builder-actions{align-items:stretch;flex-direction:column}.report-builder-actions>div{width:100%}.report-builder-actions button{flex:1}.report-filed-heading,.report-filed-body{padding-left:1rem;padding-right:1rem}.report-filed-footer{padding:.85rem 1rem;flex-direction:column}.report-preview-heading{flex-direction:column;gap:.15rem}.report-tabs a{padding:.65rem .55rem;font-size:.77rem}.report-metric{padding:.72rem;gap:.55rem}.report-metric-icon{width:30px;height:30px}.report-metric strong{font-size:1.2rem}.report-export-heading{align-items:flex-start;flex-direction:column}.report-export-grid{grid-template-columns:1fr}.report-presentation-area{align-items:flex-start;flex-direction:column}.report-presentation-area .btn{width:100%}}
@media(max-width:700px){.report-choice-grid{grid-template-columns:1fr}.report-choice-card{min-height:260px}.report-choice-archive{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@push('scripts')
@if($isBuilder || ($record && ! $locked))
<script>
(function () {
    const form = document.querySelector('[data-report-builder]');
    if (!form) return;
    const list = form.querySelector('[data-chapter-list]');
    const picker = form.querySelector('#chapter-type');
    const addButton = form.querySelector('[data-add-chapter]');
    const countOutput = form.querySelector('[data-chapter-count]');
    const capacityOutput = form.querySelector('[data-chapter-capacity]');
    let nextIndex = list.querySelectorAll('[data-chapter]').length;

    function updateChapters() {
        const sections = Array.from(list.querySelectorAll('[data-chapter]'));
        sections.forEach((section, index) => {
            const number = section.querySelector('.report-chapter-number');
            if (number) number.textContent = String(index + 1).padStart(2, '0');
            section.querySelector('[data-move-chapter="up"]').disabled = index === 0;
            section.querySelector('[data-move-chapter="down"]').disabled = index === sections.length - 1;
            const compare = section.querySelector('[data-compare-toggle]');
            if (compare) {
                section.querySelectorAll('[data-compare-fields] input').forEach((input) => {
                    input.disabled = !compare.checked;
                });
            }
        });
        countOutput.textContent = String(sections.length);
        const full = sections.length >= 12;
        addButton.disabled = full;
        capacityOutput.textContent = full ? 'This report has reached the 12-section limit.' : (12 - sections.length) + ' section' + (12 - sections.length === 1 ? '' : 's') + ' available';
    }

    function makeKey() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (digit) {
            const random = Math.random() * 16 | 0;
            return (digit === 'x' ? random : (random & 0x3 | 0x8)).toString(16);
        });
    }

    addButton.addEventListener('click', function () {
        if (list.querySelectorAll('[data-chapter]').length >= 12) return;
        const template = document.querySelector('template[data-chapter-template="' + picker.value + '"]');
        if (!template) return;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML
            .replaceAll('__INDEX__', String(nextIndex++))
            .replaceAll('__KEY__', makeKey())
            .replaceAll('__NUMBER__', String(list.querySelectorAll('[data-chapter]').length + 1).padStart(2, '0'));
        list.appendChild(wrapper.firstElementChild);
        updateChapters();
    });

    list.addEventListener('click', function (event) {
        const move = event.target.closest('[data-move-chapter]');
        if (move) {
            const section = move.closest('[data-chapter]');
            if (move.dataset.moveChapter === 'up' && section.previousElementSibling) {
                list.insertBefore(section, section.previousElementSibling);
            } else if (move.dataset.moveChapter === 'down' && section.nextElementSibling) {
                list.insertBefore(section.nextElementSibling, section);
            }
            updateChapters();
            return;
        }
        const button = event.target.closest('[data-remove-chapter]');
        if (button) {
            button.closest('[data-chapter]').remove();
            updateChapters();
        }
    });
    list.addEventListener('change', function (event) {
        if (!event.target.matches('[data-compare-toggle]')) return;
        const chapter = event.target.closest('[data-chapter]');
        const active = event.target.checked;
        chapter.querySelectorAll('[data-compare-fields] input').forEach((input) => {
            input.disabled = !active;
        });
    });
    list.querySelectorAll('[data-compare-toggle]').forEach((toggle) => {
        const chapter = toggle.closest('[data-chapter]');
        chapter.querySelectorAll('[data-compare-fields] input').forEach((input) => {
            input.disabled = !toggle.checked;
        });
    });
    updateChapters();
})();
</script>
@endif
@endpush
</x-app-layout>

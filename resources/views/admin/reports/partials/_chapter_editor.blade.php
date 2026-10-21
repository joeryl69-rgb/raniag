@php
    $chapterType = (string) ($chapter['type'] ?? '');
    $chapterKey = (string) ($chapter['key'] ?? '');
    $preview = $renderedChapter ?? null;
@endphp
<section class="report-chapter" data-chapter>
    <input type="hidden" name="chapters[{{ $index }}][key]" value="{{ $chapterKey }}">
    <input type="hidden" name="chapters[{{ $index }}][type]" value="{{ $chapterType }}">
    <div class="report-chapter-heading">
        <div class="d-flex align-items-start gap-3">
            <span class="report-chapter-number">{{ is_numeric($index) ? str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) : '__NUMBER__' }}</span>
            <div>
                <h3 class="h6 fw-bold mb-1">{{ \App\Support\ReportChapterCatalog::title($chapterType) }}</h3>
                <p class="small text-muted mb-0">{{ $chapterDescriptions[$chapterType] ?? 'A verified snapshot of this system record.' }}</p>
            </div>
        </div>
        @if(! $locked)
            <div class="report-chapter-controls">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-move-chapter="up" aria-label="Move {{ \App\Support\ReportChapterCatalog::title($chapterType) }} up" title="Move up"><i class="bi bi-arrow-up"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-move-chapter="down" aria-label="Move {{ \App\Support\ReportChapterCatalog::title($chapterType) }} down" title="Move down"><i class="bi bi-arrow-down"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-remove-chapter aria-label="Remove {{ \App\Support\ReportChapterCatalog::title($chapterType) }}">
                    <i class="bi bi-x-lg me-1"></i>Remove
                </button>
            </div>
        @endif
    </div>

    @php
        $chapterErrors = collect($errors->getMessages())
            ->filter(fn ($messages, $field) => \Illuminate\Support\Str::startsWith($field, "chapters.$index."));
    @endphp
    @if($chapterErrors->isNotEmpty())
        <div class="report-chapter-errors" role="alert">
            <strong>Fix this section before saving:</strong>
            <ul class="mb-0 mt-1">
                @foreach($chapterErrors->flatten() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <details class="report-chapter-settings" @if($chapterErrors->isNotEmpty()) open @endif>
        <summary>Optional filters <span>Leave the suggested settings unless you need a narrower report.</span></summary>
        <div class="report-chapter-settings-fields">
            @include('admin.reports.partials.chapter_options', [
                'chapter' => $chapter,
                'index' => $index,
                'locked' => $locked,
                'barangays' => $barangays,
                'agencies' => $agencies,
                'incidentTypes' => $incidentTypes,
                'supportCategories' => $supportCategories,
                'logNames' => $logNames,
            ])
        </div>
    </details>

    @if($preview)
        <div class="report-preview">
            <div class="report-preview-heading">
                <span class="fw-semibold">{{ number_format($preview['total']) }} {{ $preview['total'] === 1 ? 'row' : 'rows' }}</span>
                <span class="small text-muted">{{ $preview['note'] }}</span>
            </div>
            @if(! empty($preview['preview']))
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                @foreach($preview['columns'] as $column)
                                    <th>{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview['preview'] as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($preview['total'] > count($preview['preview']))
                        <p class="small text-muted px-2 pt-2 mb-0">The document includes all {{ number_format($preview['total']) }} rows. This preview shows the first {{ count($preview['preview']) }}.</p>
                    @endif
                </div>
            @else
                <p class="small text-muted mb-0">No matching rows for this section and period.</p>
            @endif
        </div>
    @endif
</section>

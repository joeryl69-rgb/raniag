<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportRecordRequest;
use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\FeedbackSubmission;
use App\Models\IncidentType;
use App\Models\ReportRecord;
use App\Services\QuickOperationalReport;
use App\Services\ReportChapterRenderer;
use App\Services\ReportPdfPageFurniture;
use App\Services\SimpleXlsxWriter;
use App\Support\ReportChapterCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReportRecordController extends Controller
{
    public function __construct(
        private ReportChapterRenderer $chapters,
        private QuickOperationalReport $quickReports,
    ) {}

    public function create(Request $request): View
    {
        $request->merge(['view' => 'builder']);

        return $this->index($request);
    }

    public function show(Request $request, ReportRecord $record): View|\Illuminate\Http\RedirectResponse
    {
        if (! $record->isFiled()) {
            return redirect()->route('admin.reports.records.edit', $record);
        }
        $request->merge(['record' => $record->id]);

        return $this->index($request);
    }

    public function edit(Request $request, ReportRecord $record): View|\Illuminate\Http\RedirectResponse
    {
        if ($record->isFiled()) {
            return redirect()->route('admin.reports.records.show', $record);
        }
        $request->merge(['record' => $record->id]);

        return $this->index($request);
    }

    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        if ($request->query('view') === 'archive') {
            return redirect()->route('admin.reports.index');
        }

        $recordsQuery = ReportRecord::query()->with('user:id,name')->latest();
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $recordsQuery->where('title', 'like', '%'.$term.'%');
        }
        if (in_array($request->query('status'), ['draft', 'filed'], true)) {
            $recordsQuery->where('status', $request->query('status'));
        }
        $records = $recordsQuery->paginate(15)->withQueryString();
        $record = $request->filled('record')
            ? ReportRecord::query()->findOrFail($request->integer('record'))
            : null;
        $window = $this->periodWindow($request);
        $view = $record ? 'record' : (string) $request->query('view', 'home');
        if (! in_array($view, ['home', 'overview', 'builder', 'record'], true)) {
            $view = 'home';
        }

        $template = $view === 'builder' && $request->query('template') === 'operations' ? 'operations' : null;
        $chapters = old('chapters', $record?->chapters ?? (
            $template === 'operations'
                ? ReportChapterCatalog::operationsStarter()
                : ReportChapterCatalog::starter()
        ));
        $chapters = is_array($chapters) ? array_values($chapters) : [];
        $from = (string) old('date_from', $record?->date_from?->toDateString() ?? $window['from']);
        $to = (string) old('date_to', $record?->date_to?->toDateString() ?? $window['to']);
        $errorBag = $request->session()->get('errors');
        $hasValidationErrors = $errorBag instanceof \Illuminate\Support\ViewErrorBag
            && $errorBag->getBag('default')->isNotEmpty();
        $rendered = [];
        if ($record?->isFiled() && $record->report_kind !== 'quick' && is_array($record->snapshot)) {
            $rendered = $record->snapshot;
        } elseif (! $hasValidationErrors && ($view === 'builder' || ($record && ! $record->isFiled()))) {
            foreach ($chapters as $chapter) {
                if (is_array($chapter)) {
                    $rendered[] = $this->chapters->render($chapter, $from, $to);
                }
            }
        }
        $needsBuilderOptions = $view === 'builder' || ($record && ! $record->isFiled());
        $presentation = null;
        if ($view === 'home' && ! $record && $request->query('applied') === '1') {
            $presentation = app(\App\Services\IncidentPresentation::class)->build($request);
        }

        $sources = [];
        if ($view === 'overview') {
            $sourceTypes = [
                'open_cases' => ['Open incident cases', 'Open cases received in this period', 'bi-exclamation-octagon'],
                'shelter' => ['Shelter roll', 'People currently checked in', 'bi-house-heart'],
                'papers' => ['Case papers', 'Closed cases missing required forms', 'bi-folder2-open'],
                'texts' => ['SMS delivery', 'Failed messages in this period', 'bi-chat-dots'],
                'support' => ['Support desk', 'New requests in this period', 'bi-life-preserver'],
                'notices' => ['Public notices', 'Published in this period', 'bi-megaphone'],
                'activity' => ['Staff activity', 'Audit entries in this period', 'bi-clock-history'],
                'map' => ['Hazard map', 'Hazard areas and shelters on file', 'bi-map'],
            ];
            foreach ($sourceTypes as $type => [$label, $detail, $icon]) {
                $snapshot = $this->chapters->render(
                    ['key' => $type, 'type' => $type, 'options' => ReportChapterCatalog::defaults($type)],
                    $window['from'],
                    $window['to']
                );
                $sources[] = [
                    'type' => $type,
                    'label' => $label,
                    'detail' => $detail,
                    'icon' => $icon,
                    'total' => $snapshot['total'],
                ];
            }
        }

        return view('admin.reports.record', [
            'records' => $records,
            'record' => $record,
            'rendered' => $rendered,
            'chapters' => is_array($chapters) ? array_values($chapters) : ReportChapterCatalog::starter(),
            'from' => $from,
            'to' => $to,
            'title' => (string) old('title', $record?->title ?? (
                $template === 'operations' ? 'Daily operations and service report' : ''
            )),
            'template' => $template,
            'picture' => $view === 'overview' ? $this->chapters->picture($window['from'], $window['to']) : null,
            'quickReport' => $record?->report_kind === 'quick'
                ? ($record->snapshot['quick_report'] ?? null)
                : null,
            'sources' => $sources,
            'view' => $view,
            'periodKey' => $window['key'],
            'periodLabel' => $window['label'],
            'incidentTypes' => ($needsBuilderOptions || $view === 'home') ? IncidentType::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'presentation' => $presentation,
            'agencies' => $needsBuilderOptions ? Agency::query()->orderBy('name')->get(['id', 'name', 'code']) : collect(),
            'barangays' => config('raniag.barangays'),
            'supportCategories' => $needsBuilderOptions ? FeedbackSubmission::filterableCategories() : [],
            'logNames' => $needsBuilderOptions ? ActivityLog::query()->distinct()->orderBy('log_name')->pluck('log_name')->all() : [],
        ]);
    }

    public function quick(Request $request): View
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', Rule::in(['week', 'month', 'year'])],
        ]);
        $period = $validated['period'] ?? 'month';
        $quickReport = $this->quickReports->build($period);

        return view('admin.reports.quick', [
            'quickReport' => $quickReport,
            'periodKey' => $period,
            'records' => ReportRecord::query()->where('report_kind', 'quick')->latest()->limit(5)->get(),
        ]);
    }

    public function fileQuick(Request $request)
    {
        $validated = $request->validate([
            'period' => ['required', 'string', Rule::in(['week', 'month', 'year'])],
        ]);
        $quickReport = $this->quickReports->build($validated['period']);
        $title = 'Operations situation — '.$quickReport['period_label'];
        $record = new ReportRecord([
            'user_id' => $request->user()->id,
            'title' => $title,
            'date_from' => $quickReport['from'],
            'date_to' => today()->toDateString(),
            'status' => 'filed',
            'report_kind' => 'quick',
            'chapters' => [],
            'snapshot' => ['quick_report' => $quickReport],
            'filed_at' => now(),
        ]);
        $pdf = Pdf::loadView('admin.reports.quick_pdf', [
            'record' => $record,
            'quickReport' => $quickReport,
        ])->setPaper('a4', 'portrait');
        app(ReportPdfPageFurniture::class)->add($pdf, $record->title);
        $binary = $pdf->output();
        $path = 'report-archive/'.now()->format('Y/m').'/'.now()->format('Ymd-His').'-quick-'.Str::uuid().'.pdf';

        if (! Storage::disk('local')->put($path, $binary)) {
            throw new \RuntimeException('The quick report PDF could not be saved to the report archive.');
        }

        $record->filed_path = $path;
        $record->save();

        return redirect()
            ->route('admin.reports.records.show', $record)
            ->with('status', 'Quick operations report filed and added to the archive.');
    }

    public function store(Request $request)
    {
        $form = $this->validatedForm($request);
        $record = ReportRecord::query()->create(array_merge(
            $this->recordPayload($form),
            ['user_id' => $request->user()->id, 'status' => 'draft']
        ));

        if ($request->input('action') === 'file') {
            return $this->lockRecord($record);
        }

        return redirect()->route('admin.reports.index', ['record' => $record->id])
            ->with('status', 'Draft saved. Review the rows, then file when it is ready.');
    }

    public function update(ReportRecordRequest $request, ReportRecord $record)
    {
        $this->guardDraft($record);
        $record->update($this->recordPayload($request));

        return redirect()
            ->route('admin.reports.records.edit', $record)
            ->with('status', 'Draft saved.');
    }

    public function pictureFile(Request $request)
    {
        $window = $this->periodWindow($request);
        $viewMode = match ($window['key']) {
            'year' => 'yearly',
            'month' => 'monthly',
            default => 'weekly',
        };
        $record = ReportRecord::query()->create([
            'user_id' => $request->user()->id,
            'title' => 'Situation — '.$window['label'],
            'date_from' => $window['from'],
            'date_to' => $window['to'],
            'status' => 'draft',
            'chapters' => [
                ['key' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'open_cases', 'options' => ReportChapterCatalog::defaults('open_cases')],
                ['key' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'response', 'options' => ['view_mode' => $viewMode, 'compare' => false, 'compare_from' => null, 'compare_to' => null]],
                ReportChapterCatalog::chapter('repeats'),
                ReportChapterCatalog::chapter('shelter'),
            ],
        ]);

        return $this->lockRecord($record);
    }

    public function pictureSheet(Request $request)
    {
        $window = $this->periodWindow($request);
        $rendered = $this->chapters->render([
            'key' => 'picture',
            'type' => 'open_cases',
            'options' => ReportChapterCatalog::defaults('open_cases'),
        ], $window['from'], $window['to']);

        return $this->sheetResponse($rendered, 'Situation — '.$window['label'], $window['from'], $window['to'], 'situation-'.$window['to'].'.xlsx');
    }

    public function file(Request $request, ReportRecord $record)
    {
        $this->guardDraft($record);
        $form = $this->validatedForm($request);
        $record->fill($this->recordPayload($form));

        return $this->lockRecord($record);
    }

    private function lockRecord(ReportRecord $record)
    {
        $from = $record->date_from->toDateString();
        $to = $record->date_to->toDateString();
        $rendered = [];
        foreach ($record->chapters as $chapter) {
            $rendered[] = $this->chapters->render($chapter, $from, $to);
        }

        $pdf = Pdf::loadView('admin.reports.record_pdf', [
            'record' => $record,
            'chapters' => $rendered,
            'generated_at' => now(),
        ])->setPaper('a4', 'portrait');
        $binary = $pdf->output();
        $path = 'report-archive/'.now()->format('Y/m').'/'.now()->format('Ymd-His').'-record-'.$record->id.'.pdf';
        Storage::disk('local')->put($path, $binary);
        $record->filed_path = $path;
        $record->filed_at = now();
        $record->status = 'filed';
        $record->snapshot = $rendered;
        $record->save();

        return redirect()
            ->route('admin.reports.records.show', $record)
            ->with('status', 'Record filed. The copy is ready to open.');
    }

    public function duplicate(ReportRecord $record)
    {
        if ($record->report_kind === 'quick') {
            return redirect()->route('admin.reports.quick', ['period' => 'month'])
                ->with('status', 'Quick reports are generated directly from current dashboard data. Choose the period to prepare another copy.');
        }

        if (! $record->isFiled()) {
            throw ValidationException::withMessages([
                'title' => 'File the record before copying it to the next period.',
            ]);
        }

        $span = (int) $record->date_from->startOfDay()->diffInDays($record->date_to->startOfDay());
        $from = $record->date_to->copy()->addDay()->startOfDay();
        $to = $from->copy()->addDays($span)->startOfDay();
        $today = now()->startOfDay();
        if ($to->greaterThan($today)) {
            $to = $today->copy();
        }
        if ($from->greaterThan($to)) {
            $from = $to->copy();
        }

        $copy = ReportRecord::query()->create([
            'user_id' => request()->user()->id,
            'title' => $record->title,
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'status' => 'draft',
            'chapters' => $record->chapters,
        ]);

        return redirect()->route('admin.reports.records.edit', $copy)
            ->with('status', 'Copied into a new draft.');
    }

    public function download(ReportRecord $record)
    {
        if (! $record->isFiled() || ! $record->filed_path || ! Storage::disk('local')->exists($record->filed_path) || ! str_starts_with($record->filed_path, 'report-archive/')) {
            return redirect()->route('admin.reports.index', ['record' => $record->id])
                ->with('warning', 'That filed copy is no longer on this system.');
        }

        return Storage::disk('local')->download($record->filed_path, $this->filename($record));
    }

    public function export(ReportRecord $record, string $key)
    {
        $chapter = collect($record->chapters)->firstWhere('key', $key);
        abort_if(! is_array($chapter), 404);
        if (! in_array($chapter['type'] ?? '', ReportChapterCatalog::EXPORTABLE, true)) {
            throw ValidationException::withMessages([
                'chapter' => 'This chapter has no spreadsheet.',
            ]);
        }

        $rendered = $record->isFiled() && is_array($record->snapshot)
            ? collect($record->snapshot)->firstWhere('key', $key)
            : null;
        $rendered ??= $this->chapters->render($chapter, $record->date_from->toDateString(), $record->date_to->toDateString());

        return $this->sheetResponse(
            $rendered,
            $record->title,
            $record->date_from->toDateString(),
            $record->date_to->toDateString(),
            'raniag-'.($chapter['type']).'-'.$record->date_to->format('Y-m-d').'.xlsx'
        );
    }

    public function destroy(ReportRecord $record)
    {
        if ($record->isFiled()) {
            throw ValidationException::withMessages([
                'title' => 'A filed record stays in the archive.',
            ]);
        }
        $record->delete();

        return redirect()->route('admin.reports.index')->with('status', 'Draft deleted.');
    }

    private function recordPayload(ReportRecordRequest $request): array
    {
        return [
            'title' => $request->validated('title'),
            'date_from' => $request->validated('date_from'),
            'date_to' => $request->validated('date_to'),
            'chapters' => collect($request->input('chapters'))->map(fn (array $chapter) => [
                'key' => $chapter['key'],
                'type' => $chapter['type'],
                'options' => $chapter['options'] ?? [],
            ])->values()->all(),
        ];
    }

    private function validatedForm(Request $request): ReportRecordRequest
    {
        $form = ReportRecordRequest::createFrom($request);
        $form->setContainer(app())->setRedirector(app('redirect'));
        $form->validateResolved();

        return $form;
    }

    /**
     * @param  array{title: string, columns: list<string>, rows: list<list<string>>}  $rendered
     */
    private function sheetResponse(array $rendered, string $title, string $from, string $to, string $filename)
    {
        $writer = new SimpleXlsxWriter;
        $width = max(1, count($rendered['columns']));
        $writer->setColumnWidths(array_fill(0, $width, 22));
        $writer->addRow([$rendered['title']], SimpleXlsxWriter::STYLE_TITLE);
        $writer->mergeRow(0, $width - 1);
        $writer->addRow([$title.' · '.\Carbon\Carbon::parse($from)->format('M j, Y').' – '.\Carbon\Carbon::parse($to)->format('M j, Y')], SimpleXlsxWriter::STYLE_SUBTITLE);
        $writer->mergeRow(0, $width - 1);
        $writer->addRow($rendered['columns'], SimpleXlsxWriter::STYLE_HEADER);
        foreach ($rendered['rows'] as $row) {
            $writer->addRow($row, SimpleXlsxWriter::STYLE_BODY);
        }
        if ($rendered['rows'] === []) {
            $writer->addRow(['Nothing recorded for these dates.'], SimpleXlsxWriter::STYLE_BODY);
        }

        return response($writer->toBinary(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** @return array{key: string, label: string, from: string, to: string} */
    private function periodWindow(Request $request): array
    {
        $key = $request->input('period', $request->query('period', 'week'));
        $today = now()->startOfDay();
        [$from, $label] = match ($key) {
            'month' => [$today->copy()->startOfMonth(), $today->format('F Y')],
            'year' => [$today->copy()->startOfYear(), $today->format('Y')],
            default => [$today->copy()->startOfWeek(), 'week of '.$today->copy()->startOfWeek()->format('F j')],
        };
        if (! in_array($key, ['week', 'month', 'year'], true)) {
            $key = 'week';
        }

        return [
            'key' => $key,
            'label' => $label,
            'from' => $from->toDateString(),
            'to' => $today->toDateString(),
        ];
    }

    private function guardDraft(ReportRecord $record): void
    {
        if ($record->isFiled()) {
            throw ValidationException::withMessages([
                'title' => 'This record is filed. Duplicate it to change the next period.',
            ]);
        }
    }

    private function filename(ReportRecord $record): string
    {
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', strtolower($record->title)) ?: 'record';

        return trim($slug, '-').'-'.$record->date_to->format('Y-m-d').'.pdf';
    }
}

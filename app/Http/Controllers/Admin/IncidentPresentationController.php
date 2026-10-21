<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\IncidentPresentation;
use App\Services\PresentationDocument;
use App\Services\PresentationWorkbook;
use App\Services\ReportPdfPageFurniture;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncidentPresentationController extends Controller
{
    public function __construct(
        private IncidentPresentation $presentation,
        private PresentationWorkbook $workbook,
        private PresentationDocument $document,
    ) {}

    public function excel(Request $request)
    {
        $built = $this->presentation->build($request);

        return $this->download(
            $this->workbook->binary($built, $request->query('charts', '1') !== '0', 'a4', 'portrait'),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $this->filename($built, 'xlsx'),
        );
    }

    public function word(Request $request)
    {
        $built = $this->presentation->build($request);

        return $this->download(
            $this->document->binary($built, 'a4', 'portrait'),
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $this->filename($built, 'docx'),
        );
    }

    public function pdf(Request $request)
    {
        $built = $this->presentation->build($request);
        $pdf = Pdf::loadView('admin.reports.presentation_document', [
            'presentation' => $built,
            'forPdf' => true,
            'paper' => 'a4',
            'orientation' => 'portrait',
        ])->setPaper('a4', 'portrait');
        app(ReportPdfPageFurniture::class)->add($pdf, $built['title']);
        $binary = $pdf->output();

        return $this->download($binary, 'application/pdf', $this->filename($built, 'pdf'), $request->boolean('inline'));
    }

    public function print(Request $request): View
    {
        return view('admin.reports.presentation_document', [
            'presentation' => $this->presentation->build($request),
            'forPdf' => false,
            'paper' => 'a4',
            'orientation' => 'portrait',
        ]);
    }

    /**
     * @param  array<string, mixed>  $built
     */
    private function filename(array $built, string $extension): string
    {
        $prefix = ($built['filters']['dataset'] ?? 'incidents') === 'evacuees'
            ? 'raniag-evacuee-registry-'
            : 'raniag-incident-report-';
        $stamp = $built['filters']['date_from'] ?: ($built['filters']['date_to'] ?: now()->toDateString());

        return $prefix.$stamp.'.'.$extension;
    }

    private function download(string $binary, string $type, string $filename, bool $inline = false)
    {
        return response($binary, 200, [
            'Content-Type' => $type,
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
        ]);
    }
}

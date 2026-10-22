<?php

use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;

function presentationIncident(IncidentType $type, string $tracking, string $barangay): Incident
{
    return Incident::query()->create([
        'tracking_number' => $tracking,
        'tracking_pin' => bcrypt('secret'),
        'incident_type_id' => $type->id,
        'status' => 'submitted',
        'priority' => 'high',
        'description' => 'Water on the road in '.$barangay.'.',
        'barangay' => $barangay,
        'latitude' => 18.5,
        'longitude' => 121.3,
        'is_anonymous' => true,
        'reported_at' => now(),
    ]);
}

function presentationTestArtifactPath(string $extension): string
{
    $directory = storage_path('framework/testing');
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    return $directory.'/presentation-'.bin2hex(random_bytes(8)).'.'.$extension;
}

test('the report page shows the filtered rows and can switch the same rows to cards', function () {
    $admin = User::factory()->administrator()->create();
    $flood = IncidentType::factory()->create(['name' => 'Flood']);
    $fire = IncidentType::factory()->create(['name' => 'Fire']);
    presentationIncident($flood, 'RAN-PRES-CURVA', 'Curva');
    presentationIncident($fire, 'RAN-PRES-CENTRO', 'Centro');

    $query = [
        'applied' => '1',
        'barangay' => 'Curva',
        'incident_type_id' => $flood->id,
        'date_from' => now()->startOfMonth()->toDateString(),
        'date_to' => now()->toDateString(),
    ];

    $this->actingAs($admin)
        ->get(route('admin.reports.index', $query))
        ->assertOk()
        ->assertSee('RAN-PRES-CURVA', false)
        ->assertSee('Flood', false)
        ->assertSee('data-view-mode-root="incident-presentation"', false)
        ->assertSee('name="barangay"', false)
        ->assertSee('data-auto-submit="false"', false)
        ->assertSee('js/filter-select.js', false)
        ->assertSee('js/filter-date-picker.js', false)
        ->assertDontSee('data-searchable-select-input', false)
        ->assertSee('RANIAG Incident Report', false)
        ->assertSee('data-view-mode-choice="cards"', false)
        ->assertSee('/admin/reports/presentation/excel', false)
        ->assertSee('charts=0', false)
        ->assertSee('charts=1', false)
        ->assertSee('Excel table', false)
        ->assertSee('Excel + charts', false)
        ->assertSee('data-report-metrics', false)
        ->assertSee('data-report-metric="Reports in selection" data-report-metric-value="1"', false)
        ->assertSee('data-report-metric="Open / in progress" data-report-metric-value="1"', false)
        ->assertSee('data-report-metric="High or critical" data-report-metric-value="1"', false)
        ->assertSee('data-report-metric="Areas represented" data-report-metric-value="1"', false)
        ->assertSee('/admin/reports/presentation/word', false)
        ->assertSee('/admin/reports/presentation/pdf', false)
        ->assertSee('id="report-print-modal"', false)
        ->assertSee('Open print preview', false)
        ->assertDontSee('data-report-paper', false)
        ->assertDontSee('data-report-orientation', false)
        ->assertDontSee('Folio (long)', false)
        ->assertDontSee('Summary</th>', false)
        ->assertDontSee('Water on the road', false)
        ->assertSee('barangay=Curva', false)
        ->assertDontSee('RAN-PRES-CENTRO', false);
});

test('the document-request single-incident PDF renders the shared masthead', function () {
    $type = IncidentType::factory()->create(['name' => 'Flood']);
    $incident = presentationIncident($type, 'RAN-PRES-LETTERHEAD', 'Curva');

    $html = view('admin.reports.single_pdf', [
        'incident' => $incident,
        'tracking_number' => $incident->tracking_number,
        'generated_at' => now(),
        'sections' => ['incident_details'],
    ])->render();

    expect($html)
        ->toContain('class="rg-letterhead-table"')
        ->toContain('REPUBLIC OF THE PHILIPPINES')
        ->toContain('MDRRMO Pamplona')
        ->toContain('RANIAG Incident Report')
        ->not->toContain('RANIAG Operations Center')
        ->toContain('<col style="width:16%;">')
        ->toContain('<col style="width:68%;">')
        ->toContain('<col style="width:16%;">')
        ->toContain('width:58px; height:58px')
        ->toContain('border-top:1px solid #173D66')
        ->toContain('@page { margin: 12mm 15mm 16mm; }');

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.single_pdf', [
        'incident' => $incident,
        'tracking_number' => $incident->tracking_number,
        'generated_at' => now(),
        'sections' => ['incident_details'],
    ])->setPaper('a4', 'portrait');
    $pdf->render();
    $pageStyle = $pdf->getDomPDF()->getCss()->get_page_styles()['base'];
    expect((int) round($pageStyle->margin_top))->toBe(34)
        ->and((int) round($pageStyle->margin_bottom))->toBe(45)
        ->and(substr($pdf->output(), 0, 4))->toBe('%PDF');
});

test('presentation exports share one masthead and omit the redundant metadata block', function () {
    $admin = User::factory()->administrator()->create();
    $type = IncidentType::factory()->create(['name' => 'Flood']);
    for ($index = 1; $index <= 60; $index++) {
        presentationIncident($type, 'RAN-PRES-MASTHEAD-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'Curva');
    }

    $query = ['applied' => '1'];
    $template = \App\Services\ReportLetterhead::templateData();
    $metadata = ['All dates', 'Incident reports · All barangays · All incident types · 60 records', 'Generated'];
    $html = $this->actingAs($admin)
        ->get(route('admin.reports.presentation.print', $query))
        ->assertOk()
        ->getContent();
    foreach ([$template['country'], $template['province'], $template['municipality'], $template['office'], 'RANIAG Incident Report'] as $text) {
        expect($html)->toContain($text);
    }
    expect($html)
        ->toContain('<col style="width:16%;">')
        ->toContain('<col style="width:68%;">')
        ->toContain('<col style="width:16%;">')
        ->toContain('width:58px; height:58px')
        ->toContain('border-top:1px solid #173D66')
        ->toContain('padding: 12mm 15mm 16mm;')
        ->toContain('.rg-letterhead-institution .rg-letterhead-office { color: #173D66; font-family: Arial, sans-serif; font-size: 11pt; font-weight: 700; }')
        ->toContain('font-family: Arial, sans-serif; font-size: 10pt;')
        ->toContain('@page { size: A4 portrait; margin: 36mm 15mm 22mm; }')
        ->toContain('.rg-running-header { position: fixed; top: 4mm; left: 0; right: 0; height: 28mm; }')
        ->toContain('table.rg-report thead { display: table-header-group; }')
        ->toContain('.rg-running-footer { position: fixed; bottom: 4mm; left: 0; right: 0; display: block !important; }')
        ->not->toContain('Page <span class="rg-page-number">');
    foreach ($metadata as $text) {
        expect($html)->not->toContain($text);
    }

    $pdf = $this->get(route('admin.reports.presentation.pdf', $query))->assertOk();
    $pdfBinary = $pdf->getContent();
    $pageCount = preg_match_all('/\/Type\s*\/Page\b/', $pdfBinary);
    expect(substr($pdfBinary, 0, 4))->toBe('%PDF')
        ->and($pageCount)->toBeGreaterThan(1);

    $word = $this->get(route('admin.reports.presentation.word', $query))->assertOk();
    $path = presentationTestArtifactPath('docx');
    file_put_contents($path, $word->getContent());
    $zip = new ZipArchive;
    try {
        expect($zip->open($path))->toBeTrue();
        $xml = $zip->getFromName('word/document.xml');
        $stylesXml = $zip->getFromName('word/styles.xml');
        $document = new DOMDocument;
        expect($document->loadXML($xml))->toBeTrue();
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $headerDocument = new DOMDocument;
        expect($headerDocument->loadXML($zip->getFromName('word/header1.xml')))->toBeTrue();
        $headerXpath = new DOMXPath($headerDocument);
        $headerXpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $masthead = $headerXpath->query('//w:hdr/w:tbl[not(w:tblGrid)]')->item(0);
        $mastheadWidths = [];
        foreach ($headerXpath->query('./w:tr/w:tc/w:tcPr/w:tcW', $masthead) as $cellWidth) {
            $mastheadWidths[] = (int) $cellWidth->getAttributeNS(
                'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
                'w'
            );
        }
        $pageMargins = $xpath->query('//w:sectPr/w:pgMar')->item(0);
        $mastheadXml = $headerDocument->saveXML($masthead);
        $officeRunFonts = $headerXpath->query('//w:hdr/w:tbl[not(w:tblGrid)]//w:r[w:t="MDRRMO Pamplona"]/w:rPr/w:rFonts')->item(0);
        $contentWidth = 11906
            - (2 * \App\Services\ReportLetterhead::millimetersToTwips($template['page_margins_mm']['horizontal']));
        expect($masthead)->not->toBeNull()
            ->and($mastheadWidths)->toHaveCount(3)
            ->and($mastheadWidths)->toBe([
                (int) round($contentWidth * $template['columns'][0] / 100),
                (int) round($contentWidth * $template['columns'][1] / 100),
                $contentWidth
                    - (int) round($contentWidth * $template['columns'][0] / 100)
                    - (int) round($contentWidth * $template['columns'][1] / 100),
            ])
            ->and($mastheadXml)->toContain($template['country'])
            ->and($mastheadXml)->toContain($template['office'])
            ->and($mastheadXml)->toContain('RANIAG Incident Report')
            ->and($mastheadXml)->not->toContain('RANIAG Operations Center')
            ->and($officeRunFonts)->not->toBeNull()
            ->and($officeRunFonts->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'ascii'))
                ->toBe($template['font_family'])
            ->and($officeRunFonts->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'hAnsi'))
                ->toBe($template['font_family'])
            ->and($zip->getFromName('word/header1.xml'))->toContain('w:sz="6"')
            ->and($zip->getFromName('word/header1.xml'))->toContain('w:color="173D66"')
            ->and($stylesXml)->toContain('w:ascii="'.$template['font_family'].'"')
            ->and($stylesXml)->toContain('w:hAnsi="'.$template['font_family'].'"')
            ->and($stylesXml)->toContain('w:eastAsia="'.$template['font_family'].'"')
            ->and($stylesXml)->toContain('w:cs="'.$template['font_family'].'"')
            ->and($stylesXml)->toContain('w:sz w:val="'.($template['body_font_size_pt'] * 2).'"')
            ->and($zip->getFromName('word/header1.xml'))->toContain((string) round($template['seal_size_px'] * 9525))
            ->and((int) $pageMargins->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'top'))
                ->toBe(\App\Services\ReportLetterhead::millimetersToTwips(36))
            ->and((int) $pageMargins->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'bottom'))
                ->toBe(\App\Services\ReportLetterhead::millimetersToTwips(22))
            ->and((int) $pageMargins->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'left'))
                ->toBe(\App\Services\ReportLetterhead::millimetersToTwips($template['page_margins_mm']['horizontal']))
            ->and((int) $pageMargins->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'right'))
                ->toBe(\App\Services\ReportLetterhead::millimetersToTwips($template['page_margins_mm']['horizontal']));
        foreach ($metadata as $text) {
            expect($xml)->not->toContain($text);
        }
    } finally {
        $zip->close();
        if (is_file($path)) {
            unlink($path);
        }
    }
});

test('Dompdf reserves page furniture space and writes the real page count', function () {
    $presentation = [
        'title' => 'Pagination regression',
        'columns' => ['Tracking', 'Type', 'Barangay', 'Status', 'Priority', 'Reported'],
        'rows' => array_map(
            fn (int $index) => ['cells' => ['RAN-PAGE-'.$index, 'Flood', 'Curva', 'Submitted', 'High', 'Oct 21, 2026']],
            range(1, 60)
        ),
    ];
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.presentation_document', [
        'presentation' => $presentation,
        'forPdf' => true,
    ])->setPaper('a4', 'portrait');

    app(\App\Services\ReportPdfPageFurniture::class)->add($pdf, $presentation['title']);
    $pageStyle = $pdf->getDomPDF()->getCss()->get_page_styles()['base'];
    $pageCount = $pdf->getDomPDF()->getCanvas()->get_page_count();
    $uncompressedPdf = $pdf->output(['compress' => false]);

    expect((int) round($pageStyle->margin_top))->toBe(102)
        ->and((int) round($pageStyle->margin_bottom))->toBe(62)
        ->and($pageCount)->toBeGreaterThan(1)
        ->and($uncompressedPdf)->toContain('Page 1 of '.$pageCount)
        ->and($uncompressedPdf)->toContain('Page '.$pageCount.' of '.$pageCount)
        ->and($uncompressedPdf)->not->toContain('Page 1 of 0');
});

test('excel word pdf and print use the same filtered incident rows', function () {
    $admin = User::factory()->administrator()->create();
    $flood = IncidentType::factory()->create(['name' => 'Flood']);
    presentationIncident($flood, 'RAN-PRES-FILE', 'Curva');

    $query = [
        'applied' => '1',
        'barangay' => 'Curva',
        'incident_type_id' => $flood->id,
        'date_from' => now()->startOfMonth()->toDateString(),
        'date_to' => now()->toDateString(),
    ];

    $excel = $this->actingAs($admin)
        ->get(route('admin.reports.presentation.excel', $query))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $tmp = presentationTestArtifactPath('xlsx');
    file_put_contents($tmp, $excel->getContent());
    $zip = new ZipArchive;
    expect($zip->open($tmp))->toBeTrue();
    $sheet1 = $zip->getFromName('xl/worksheets/sheet1.xml');
    $sheet2 = $zip->getFromName('xl/worksheets/sheet2.xml');
    expect($zip->locateName('xl/charts/chart1.xml'))->not->toBeFalse()
        ->and($zip->getFromName('xl/workbook.xml'))->toContain('name="Reports"')
        ->and($zip->getFromName('xl/workbook.xml'))->toContain('name="Charts"')
        ->and($sheet1)->toContain('RAN-PRES-FILE')
        ->and($sheet1)->not->toContain('Water on the road')
        ->and($sheet1)->toContain('MDRRMO Pamplona')
        ->and($sheet2)->toContain('Reports by incident type')
        ->and($sheet2)->toContain('Flood')
        ->and(strpos($sheet2, '<drawing '))->toBeGreaterThan(strpos($sheet2, '</headerFooter>'));
    expect((new DOMDocument)->loadXML($sheet1))->toBeTrue()
        ->and((new DOMDocument)->loadXML($sheet2))->toBeTrue();
    $zip->close();
    unlink($tmp);

    $word = $this->actingAs($admin)
        ->get(route('admin.reports.presentation.word', $query))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    $doc = presentationTestArtifactPath('docx');
    file_put_contents($doc, $word->getContent());
    $wordZip = new ZipArchive;
    expect($wordZip->open($doc))->toBeTrue();
    $document = $wordZip->getFromName('word/document.xml');
    $header = $wordZip->getFromName('word/header1.xml');
    $footer = $wordZip->getFromName('word/footer1.xml');
    expect($document)->toContain('RAN-PRES-FILE')
        ->and($document)->toContain('w:headerReference')
        ->and($document)->toContain('w:footerReference')
        ->and($document)->toContain('w:tblHeader')
        ->and($header)->toContain('REPUBLIC OF THE PHILIPPINES')
        ->and($header)->toContain('MDRRMO Pamplona')
        ->and($footer)->toContain('PAGE')
        ->and($footer)->toContain('NUMPAGES');
    $wordZip->close();
    unlink($doc);

    $this->actingAs($admin)
        ->get(route('admin.reports.presentation.print', array_merge($query, ['paper' => 'legal', 'orientation' => 'landscape'])))
        ->assertOk()
        ->assertSee('REPUBLIC OF THE PHILIPPINES', false)
        ->assertSee('MDRRMO Pamplona', false)
        ->assertDontSee('RANIAG Operations Center', false)
        ->assertSee('RAN-PRES-FILE', false)
        ->assertSee('rg-repeat', false)
        ->assertSee('color:#1a365d', false)
        ->assertSee('font-family: Arial, sans-serif; font-size: 10pt;', false)
        ->assertSee('size: A4 portrait', false)
        ->assertSee('width:58px; height:58px', false)
        ->assertDontSee('Water on the road', false)
        ->assertDontSee('sidebar-nav', false);

    $pdf = $this->actingAs($admin)
        ->get(route('admin.reports.presentation.pdf', $query))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
    expect(substr($pdf->getContent(), 0, 4))->toBe('%PDF');

    $tableOnly = $this->actingAs($admin)
        ->get(route('admin.reports.presentation.excel', array_merge($query, ['charts' => '0'])))
        ->assertOk();
    $plain = presentationTestArtifactPath('xlsx');
    file_put_contents($plain, $tableOnly->getContent());
    $plainZip = new ZipArchive;
    expect($plainZip->open($plain))->toBeTrue();
    expect($plainZip->locateName('xl/worksheets/sheet2.xml'))->toBeFalse()
        ->and($plainZip->getFromName('xl/worksheets/sheet1.xml'))->toContain('RAN-PRES-FILE')
        ->and($plainZip->getFromName('xl/workbook.xml'))->not->toContain('name="Charts"');
    $plainZip->close();
    unlink($plain);
});

test('word and pdf presentation layouts fit the a4 portrait content width', function () {
    $admin = User::factory()->administrator()->create();
    $type = IncidentType::factory()->create(['name' => 'Flood']);
    presentationIncident($type, 'RAN-PRES-LAYOUT', 'Curva');
    $query = [
        'applied' => '1',
        'date_from' => now()->startOfMonth()->toDateString(),
        'date_to' => now()->toDateString(),
    ];

    $word = $this->actingAs($admin)
        ->get(route('admin.reports.presentation.word', $query))
        ->assertOk();
    $docPath = presentationTestArtifactPath('docx');
    file_put_contents($docPath, $word->getContent());
    $zip = new ZipArchive;

    try {
        expect($zip->open($docPath))->toBeTrue();
        $document = new DOMDocument;
        expect($document->loadXML($zip->getFromName('word/document.xml')))->toBeTrue();
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $table = $xpath->query('//w:tbl[w:tblGrid]')->item(0);
        $headerDocument = new DOMDocument;
        expect($headerDocument->loadXML($zip->getFromName('word/header1.xml')))->toBeTrue();
        $headerXpath = new DOMXPath($headerDocument);
        $headerXpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $letterhead = $headerXpath->query('//w:hdr/w:tbl[not(w:tblGrid)]')->item(0);
        expect($table)->not->toBeNull();
        expect($letterhead)->not->toBeNull();

        $gridWidths = [];
        foreach ($xpath->query('./w:tblGrid/w:gridCol', $table) as $column) {
            $gridWidths[] = (int) $column->getAttributeNS(
                'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
                'w'
            );
        }
        $tableWidth = (int) $xpath->query('./w:tblPr/w:tblW', $table)->item(0)->getAttributeNS(
            'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
            'w'
        );
        $pageWidth = (int) $xpath->query('//w:sectPr/w:pgSz')->item(0)->getAttributeNS(
            'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
            'w'
        );
        $marginLeft = (int) $xpath->query('//w:sectPr/w:pgMar')->item(0)->getAttributeNS(
            'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
            'left'
        );
        $marginRight = (int) $xpath->query('//w:sectPr/w:pgMar')->item(0)->getAttributeNS(
            'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
            'right'
        );
        $letterheadWidth = (int) $headerXpath->query('./w:tblPr/w:tblW', $letterhead)->item(0)->getAttributeNS(
            'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
            'w'
        );
        $letterheadCellWidths = [];
        foreach ($headerXpath->query('./w:tr/w:tc/w:tcPr/w:tcW', $letterhead) as $cellWidth) {
            $letterheadCellWidths[] = (int) $cellWidth->getAttributeNS(
                'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
                'w'
            );
        }

        expect(count($gridWidths))->toBe(6)
            ->and($tableWidth)->toBe(array_sum($gridWidths))
            ->and($tableWidth)->toBeLessThanOrEqual($pageWidth - $marginLeft - $marginRight)
            ->and(count($letterheadCellWidths))->toBe(3)
            ->and($letterheadWidth)->toBe(array_sum($letterheadCellWidths))
            ->and($letterheadWidth)->toBeLessThanOrEqual($pageWidth - $marginLeft - $marginRight);
        foreach ($xpath->query('./w:tr', $table) as $row) {
            $rowWidths = [];
            foreach ($xpath->query('./w:tc/w:tcPr/w:tcW', $row) as $cellWidth) {
                $rowWidths[] = (int) $cellWidth->getAttributeNS(
                    'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
                    'w'
                );
            }
            expect($xpath->query('./w:tc', $row)->length)->toBe(6)
                ->and($rowWidths)->toBe($gridWidths);
        }
        expect($zip->getFromName('word/document.xml'))->toContain('RAN-PRES-LAYOUT');
    } finally {
        $zip->close();
        if (is_file($docPath)) {
            unlink($docPath);
        }
    }

    $this->get(route('admin.reports.presentation.print', $query))
        ->assertOk()
        ->assertSee('MDRRMO Pamplona', false)
        ->assertDontSee('RANIAG Operations Center', false)
        ->assertSee('table-layout: fixed', false)
        ->assertSee('overflow-wrap: break-word', false);

    $pdf = $this->get(route('admin.reports.presentation.pdf', $query))->assertOk();
    expect(preg_match('/\/MediaBox\s*\[\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*\]/', $pdf->getContent(), $page))
        ->toBe(1)
        ->and((float) $page[3] - (float) $page[1])->toBeGreaterThan(594)
        ->toBeLessThan(597)
        ->and((float) $page[4] - (float) $page[2])->toBeGreaterThan(840)
        ->toBeLessThan(844)
        ->and((float) $page[4] - (float) $page[2])->toBeGreaterThan((float) $page[3] - (float) $page[1]);
});

test('exports contain the complete selection even when requested from a later table page', function () {
    $admin = User::factory()->administrator()->create();
    $flood = IncidentType::factory()->create(['name' => 'Flood']);
    $flood->update(['name' => str_repeat('Flood event ', 14)]);

    for ($index = 1; $index <= 27; $index++) {
        presentationIncident($flood, 'RAN-PRES-PAGE-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'Curva');
    }

    $query = [
        'dataset' => 'incidents',
        'barangay' => 'Curva',
        'date_from' => now()->startOfMonth()->toDateString(),
        'date_to' => now()->toDateString(),
        'page' => 2,
    ];
    $this->actingAs($admin)
        ->get(route('admin.reports.index', array_merge($query, ['applied' => '1'])))
        ->assertOk()
        ->assertSee('RAN-PRES-PAGE-01', false)
        ->assertDontSee('RAN-PRES-PAGE-27', false);

    $excel = $this->get(route('admin.reports.presentation.excel', $query))->assertOk();
    $path = presentationTestArtifactPath('xlsx');
    file_put_contents($path, $excel->getContent());
    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    expect($sheet)->toContain('RAN-PRES-PAGE-01')
        ->and($sheet)->toContain('RAN-PRES-PAGE-27');
    $zip->close();
    unlink($path);

    $pdf = $this->get(route('admin.reports.presentation.pdf', $query))->assertOk()->getContent();
    expect(preg_match_all('/\/Type\s*\/Page\b/', $pdf))->toBeGreaterThan(1);

    $print = $this->get(route('admin.reports.presentation.print', $query))->assertOk();
    $print->assertSee('table.rg-report thead { display: table-header-group; }', false)
    ->assertSee('@page { margin-top: 36mm; margin-bottom: 22mm; }', false)
    ->assertSee('.rg-running-header { position: fixed; top: 4mm; left: 0; right: 0; height: 28mm; }', false)
    ->assertSee('.rg-running-footer { position: fixed; bottom: 4mm; left: 0; right: 0; display: block !important; }', false)
    ->assertSee('.rg-running-footer .rg-pdf-footer { position: static !important; bottom: auto !important; }', false)
    ->assertDontSee('bottom: -20px', false)
    ->assertDontSee('Page <span class="rg-page-number">', false)
    ->assertSee('RAN-PRES-PAGE-27', false);
});

test('the report page opens without a date already selected', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertSee('name="date_from"', false)
        ->assertSee('name="date_to"', false)
        ->assertDontSee('value="'.now()->startOfMonth()->toDateString().'"', false)
        ->assertDontSee('value="'.now()->toDateString().'"', false)
        ->assertDontSee('data-view-mode-root="incident-presentation"', false);
});

test('outside reports stay available beside a barangay selection', function () {
    $admin = User::factory()->administrator()->create();
    $flood = IncidentType::factory()->create(['name' => 'Flood']);
    presentationIncident($flood, 'RAN-PRES-INSIDE', 'Curva');
    $outside = presentationIncident($flood, 'RAN-PRES-OUT', 'Curva');
    $outside->update([
        'barangay' => null,
        'location_address' => 'Abulug',
        'meta' => ['within_jurisdiction' => false],
    ]);
    $referred = presentationIncident($flood, 'RAN-PRES-REF', 'Curva');
    $referred->update(['status' => 'outside_aor']);

    $this->actingAs($admin)
        ->get(route('admin.reports.index', [
            'applied' => '1',
            'barangay' => 'outside',
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertSee('RAN-PRES-OUT', false)
        ->assertSee('RAN-PRES-REF', false)
        ->assertSee('Outside Pamplona', false)
        ->assertDontSee('RAN-PRES-INSIDE', false);

    $this->get(route('admin.reports.index', [
        'applied' => '1',
        'date_from' => now()->startOfMonth()->toDateString(),
        'date_to' => now()->toDateString(),
    ]))
        ->assertOk()
        ->assertSee('RAN-PRES-INSIDE', false)
        ->assertSee('RAN-PRES-OUT', false)
        ->assertSee('RAN-PRES-REF', false);
});

test('the evacuee registry uses the same filters and exports', function () {
    $admin = User::factory()->administrator()->create();
    $center = \App\Models\EvacuationCenter::query()->create([
        'name' => 'Pamplona Municipal Gymnasium',
        'latitude' => 18.45,
        'longitude' => 121.34,
        'is_open' => true,
        'capacity' => 80,
    ]);
    \App\Models\Evacuee::query()->create([
        'evacuation_center_id' => $center->id,
        'full_name' => 'Maria Cruz',
        'origin_scope' => 'outside',
        'origin_place' => 'Abulug',
        'barangay' => 'Centro',
        'age' => 34,
        'sex' => 'Female',
        'checked_in_at' => now(),
    ]);
    \App\Models\Evacuee::query()->create([
        'evacuation_center_id' => $center->id,
        'full_name' => 'Juan Ramos',
        'origin_scope' => 'inside',
        'barangay' => 'Curva',
        'checked_in_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reports.index', [
            'applied' => '1',
            'dataset' => 'evacuees',
            'barangay' => 'outside',
        ]))
        ->assertOk()
        ->assertSee('Maria Cruz', false)
        ->assertSee('Centro, Abulug', false)
        ->assertSee('Pamplona Municipal Gymnasium', false)
        ->assertSee('data-view-mode-root="incident-presentation"', false)
        ->assertDontSee('Juan Ramos', false);

    $excel = $this->get(route('admin.reports.presentation.excel', [
        'applied' => '1',
        'dataset' => 'evacuees',
        'barangay' => 'outside',
    ]))->assertOk();

    $tmp = presentationTestArtifactPath('xlsx');
    file_put_contents($tmp, $excel->getContent());
    $zip = new ZipArchive;
    expect($zip->open($tmp))->toBeTrue();
    $sheet1 = $zip->getFromName('xl/worksheets/sheet1.xml');
    $sheet2 = $zip->getFromName('xl/worksheets/sheet2.xml');
    expect($sheet1)->toContain('Maria Cruz')
        ->and($sheet1)->toContain('Evacuee registry')
        ->and($sheet2)->toContain('Evacuees by shelter')
        ->and($sheet2)->toContain('Pamplona Municipal Gymnasium');
    $zip->close();
    unlink($tmp);
});

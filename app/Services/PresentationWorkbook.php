<?php

namespace App\Services;

use ZipArchive;

/**
 * Two-sheet workbook for the incident presentation.
 * Sheet 1 is the filtered table. Sheet 2 is the chart data and bar charts.
 */
class PresentationWorkbook
{
    /**
     * @param  array<string, mixed>  $built
     */
    public function binary(array $built, bool $withCharts = true, string $paper = 'a4', string $orientation = 'portrait'): string
    {
        $sheet1 = $this->reportSheet($built, $paper, $orientation);
        $chartLayout = $withCharts ? $this->chartLayout($built['charts']) : ['rows' => [], 'charts' => []];
        $chartCount = count($chartLayout['charts']);

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypes($chartCount));
        $zip->addFromString('_rels/.rels', $this->packageRels());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($withCharts));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels($withCharts));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet1);
        if ($withCharts) {
            $zip->addFromString('xl/worksheets/sheet2.xml', $this->chartSheet($chartLayout['rows'], $paper, $orientation));
            $zip->addFromString('xl/worksheets/_rels/sheet2.xml.rels', $this->sheetDrawingRel());
            $zip->addFromString('xl/drawings/drawing1.xml', $this->drawingXml($chartLayout['charts']));
            $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', $this->drawingRels($chartCount));
            foreach ($chartLayout['charts'] as $index => $chart) {
                $zip->addFromString('xl/charts/chart'.($index + 1).'.xml', $this->chartXml($chart, $index));
            }
        }
        $zip->close();

        $binary = file_get_contents($tmp);
        unlink($tmp);

        return $binary === false ? '' : $binary;
    }

    /**
     * @param  array<string, mixed>  $built
     */
    private function reportSheet(array $built, string $paper, string $orientation): string
    {
        $columns = $built['columns'];
        $last = $this->column(count($columns) - 1);
        $widths = count($columns) === 7
            ? [18, 22, 18, 22, 14, 22, 42]
            : array_fill(0, max(1, count($columns)), 24);
        $rows = [
            [['RANIAG '.$built['title']], 1],
            [['MDRRMO Pamplona  |  '.$built['period'].'  |  Generated '.$built['generated_at']], 2],
            [[$built['summary']], 2],
            [[], 0],
            [$columns, 4],
        ];
        foreach ($built['rows'] as $row) {
            $rows[] = [$row['cells'], 5];
        }
        if ($built['rows'] === []) {
            $empty = array_fill(0, count($columns), '');
            $empty[0] = 'No records in this selection';
            $rows[] = [$empty, 5];
        }

        $xml = $this->sheetOpen($widths, 5);
        $xml .= $this->sheetRows($rows);
        $xml .= '<mergeCells count="3">'
            .'<mergeCell ref="A1:'.$last.'1"/>'
            .'<mergeCell ref="A2:'.$last.'2"/>'
            .'<mergeCell ref="A3:'.$last.'3"/>'
            .'</mergeCells>';
        $xml .= '<printOptions headings="false"/>';
        $xml .= '<pageMargins left="0.4" right="0.4" top="0.6" bottom="0.5" header="0.25" footer="0.25"/>';
        $xml .= $this->pageSetup($paper, $orientation);
        $xml .= '<headerFooter>'
            .'<oddHeader>&amp;C&amp;&amp;"Arial,Bold"MDRRMO Pamplona — '.$this->xml($built['title']).'</oddHeader>'
            .'<oddFooter>&amp;L'.$this->xml($built['period']).'</oddFooter>'
            .'</headerFooter>';
        $xml .= '</worksheet>';

        return $xml;
    }

    /**
     * @param  list<array{title: string, rows: list<array{label: string, count: int}>}>  $charts
     * @return array{rows: list<array{0: array<int, mixed>, 1: int}>, charts: list<array{title: string, start: int, end: int, anchor: int}>}
     */
    private function chartLayout(array $charts): array
    {
        $rows = [];
        $placed = [];
        $cursor = 1;
        foreach ($charts as $chart) {
            $titleRow = $cursor;
            $rows[] = [[$chart['title']], 3];
            $rows[] = [['Category', 'Reports'], 4];
            $start = $titleRow + 2;
            foreach ($chart['rows'] as $point) {
                $rows[] = [[$point['label'], $point['count']], 5];
            }
            $end = $start + count($chart['rows']) - 1;
            $cursor = $end + 1;
            $used = $end - $titleRow + 1;
            for ($pad = $used; $pad < 14; $pad++) {
                $rows[] = [[], 0];
                $cursor++;
            }
            $placed[] = [
                'title' => $chart['title'],
                'start' => $start,
                'end' => $end,
                'anchor' => $titleRow - 1,
            ];
        }

        return ['rows' => $rows, 'charts' => $placed];
    }

    /**
     * @param  list<array{0: array<int, mixed>, 1: int}>  $rows
     */
    private function chartSheet(array $rows, string $paper, string $orientation): string
    {
        $xml = $this->sheetOpen([36, 14], 0);
        $xml .= $this->sheetRows($rows);
        $xml .= '<pageMargins left="0.4" right="0.4" top="0.6" bottom="0.5" header="0.25" footer="0.25"/>';
        $xml .= $this->pageSetup($paper, $orientation);
        $xml .= '<headerFooter>'
            .'<oddHeader>&amp;C&amp;&amp;"Arial,Bold"MDRRMO Pamplona — Charts</oddHeader>'
            .'<oddFooter>&amp;LCharts</oddFooter>'
            .'</headerFooter>';
        $xml .= '<drawing r:id="rId1"/>';
        $xml .= '</worksheet>';

        return $xml;
    }

    private function pageSetup(string $paper, string $orientation): string
    {
        $sizes = ['letter' => 1, 'legal' => 5, 'a4' => 9, 'folio' => 14];
        $size = $sizes[$paper] ?? 9;
        $orient = $orientation === 'portrait' ? 'portrait' : 'landscape';

        return '<pageSetup orientation="'.$orient.'" fitToWidth="1" paperSize="'.$size.'"/>';
    }

    /**
     * @param  array<int, float>  $widths
     */
    private function sheetOpen(array $widths, int $frozenRows): string
    {
        $cols = '<cols>';
        foreach ($widths as $index => $width) {
            $column = $index + 1;
            $cols .= '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>';
        }
        $cols .= '</cols>';
        $pane = '';
        if ($frozenRows > 0) {
            $next = $frozenRows + 1;
            $pane = '<pane ySplit="'.$frozenRows.'" topLeftCell="A'.$next.'" activePane="bottomLeft" state="frozen"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetViews><sheetView workbookViewId="0">'.$pane.'</sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .$cols
            .'<sheetData>';
    }

    /**
     * @param  list<array{0: array<int, mixed>, 1: int}>  $rows
     */
    private function sheetRows(array $rows): string
    {
        $xml = '';
        foreach ($rows as $rowIndex => $row) {
            $number = $rowIndex + 1;
            $height = $row[1] === 1 ? ' ht="22" customHeight="1"' : '';
            $xml .= '<row r="'.$number.'"'.$height.'>';
            foreach ($row[0] as $column => $value) {
                $ref = $this->column($column).$number;
                $style = $row[1];
                if ($value === null || $value === '') {
                    $xml .= '<c r="'.$ref.'" s="'.$style.'"/>';
                } elseif (is_int($value) || (is_numeric($value) && ! preg_match('/^0\d/', (string) $value))) {
                    $xml .= '<c r="'.$ref.'" s="'.$style.'"><v>'.$value.'</v></c>';
                } else {
                    $xml .= '<c r="'.$ref.'" t="inlineStr" s="'.$style.'"><is><t xml:space="preserve">'.$this->xml((string) $value).'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        return $xml.'</sheetData>';
    }

    /**
     * @param  list<array{title: string, start: int, end: int, anchor: int}>  $charts
     */
    private function drawingXml(array $charts): string
    {
        $anchors = '';
        foreach ($charts as $index => $chart) {
            $fromRow = $chart['anchor'];
            $toRow = $fromRow + 13;
            $id = $index + 1;
            $anchors .= '<xdr:twoCellAnchor>'
                .'<xdr:from><xdr:col>3</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>'.$fromRow.'</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>'
                .'<xdr:to><xdr:col>10</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>'.$toRow.'</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to>'
                .'<xdr:graphicFrame macro="">'
                .'<xdr:nvGraphicFramePr><xdr:cNvPr id="'.$id.'" name="Chart '.$id.'"/><xdr:cNvGraphicFramePr/></xdr:nvGraphicFramePr>'
                .'<xdr:xfrm><a:off x="0" y="0"/><a:ext cx="7315200" cy="3657600"/></xdr:xfrm>'
                .'<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/chart">'
                .'<c:chart xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:id="rId'.$id.'"/>'
                .'</a:graphicData></a:graphic>'
                .'</xdr:graphicFrame><xdr:clientData/>'
                .'</xdr:twoCellAnchor>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            .$anchors
            .'</xdr:wsDr>';
    }

    /**
     * @param  array{title: string, start: int, end: int, anchor: int}  $chart
     */
    private function chartXml(array $chart, int $index): string
    {
        $start = $chart['start'];
        $end = $chart['end'];
        $cat = 'Charts!$A$'.$start.':$A$'.$end;
        $val = 'Charts!$B$'.$start.':$B$'.$end;
        $catAxis = 100 + ($index * 2);
        $valAxis = $catAxis + 1;
        $title = $this->xml($chart['title']);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<c:chartSpace xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            .'<c:chart>'
            .'<c:title><c:tx><c:rich><a:bodyPr/><a:lstStyle/><a:p><a:pPr><a:defRPr/></a:pPr><a:r><a:rPr lang="en-US" sz="1400" b="1"/><a:t>'.$title.'</a:t></a:r></a:p></c:rich></c:tx></c:title>'
            .'<c:plotArea><c:layout/>'
            .'<c:barChart>'
            .'<c:barDir val="col"/>'
            .'<c:grouping val="clustered"/>'
            .'<c:varyColors val="1"/>'
            .'<c:ser>'
            .'<c:idx val="0"/><c:order val="0"/>'
            .'<c:tx><c:v>Reports</c:v></c:tx>'
            .'<c:cat><c:strRef><c:f>'.$cat.'</c:f></c:strRef></c:cat>'
            .'<c:val><c:numRef><c:f>'.$val.'</c:f></c:numRef></c:val>'
            .'</c:ser>'
            .'<c:gapWidth val="70"/>'
            .'<c:axId val="'.$catAxis.'"/><c:axId val="'.$valAxis.'"/>'
            .'</c:barChart>'
            .'<c:catAx><c:axId val="'.$catAxis.'"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/><c:axPos val="b"/><c:majorTickMark val="out"/><c:minorTickMark val="none"/><c:tickLblPos val="nextTo"/><c:crossAx val="'.$valAxis.'"/><c:crosses val="autoZero"/><c:auto val="1"/><c:lblAlgn val="ctr"/><c:lblOffset val="100"/><c:noMultiLvlLbl val="0"/></c:catAx>'
            .'<c:valAx><c:axId val="'.$valAxis.'"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/><c:axPos val="l"/><c:majorGridlines/><c:numFmt formatCode="General" sourceLinked="1"/><c:majorTickMark val="out"/><c:minorTickMark val="none"/><c:tickLblPos val="nextTo"/><c:crossAx val="'.$catAxis.'"/><c:crosses val="autoZero"/><c:crossBetween val="between"/></c:valAx>'
            .'</c:plotArea>'
            .'<c:legend><c:legendPos val="b"/></c:legend>'
            .'<c:plotVisOnly val="1"/>'
            .'</c:chart>'
            .'</c:chartSpace>';
    }

    private function contentTypes(int $charts): string
    {
        $overrides = '';
        if ($charts > 0) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>';
            for ($i = 1; $i <= $charts; $i++) {
                $overrides .= '<Override PartName="/xl/charts/chart'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.drawingml.chart+xml"/>';
            }
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$overrides
            .'</Types>';
    }

    private function packageRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(bool $withCharts): string
    {
        $charts = $withCharts ? '<sheet name="Charts" sheetId="2" r:id="rId2"/>' : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'
            .'<sheet name="Reports" sheetId="1" r:id="rId1"/>'
            .$charts
            .'</sheets>'
            .'<definedNames>'
            .'<definedName name="_xlnm.Print_Titles" localSheetId="0">Reports!$1:$5</definedName>'
            .'</definedNames>'
            .'</workbook>';
    }

    private function workbookRels(bool $withCharts): string
    {
        $charts = $withCharts
            ? '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
            : '';
        $stylesId = $withCharts ? 'rId3' : 'rId2';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .$charts
            .'<Relationship Id="'.$stylesId.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function sheetDrawingRel(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>'
            .'</Relationships>';
    }

    private function drawingRels(int $charts): string
    {
        $rels = '';
        for ($i = 1; $i <= $charts; $i++) {
            $rels .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart" Target="../charts/chart'.$i.'.xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$rels
            .'</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="5">'
            .'<font><sz val="11"/><name val="Arial"/></font>'
            .'<font><b/><sz val="11"/><name val="Arial"/></font>'
            .'<font><b/><sz val="14"/><color rgb="FFFFFFFF"/><name val="Arial"/></font>'
            .'<font><i/><sz val="10"/><color rgb="FF666666"/><name val="Arial"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Arial"/></font>'
            .'</fonts>'
            .'<fills count="4">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFDCEEE1"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF1A365D"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left style="thin"><color rgb="FFD9D9D9"/></left><right style="thin"><color rgb="FFD9D9D9"/></right><top style="thin"><color rgb="FFD9D9D9"/></top><bottom style="thin"><color rgb="FFD9D9D9"/></bottom><diagonal/></border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="6">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            .'<xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment wrapText="1" vertical="top"/></xf>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    private function column(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}

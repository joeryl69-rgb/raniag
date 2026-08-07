<?php

namespace App\Services;

use ZipArchive;

/**
 * Builds a genuine .xlsx file with no external packages (Composer/Packagist
 * isn't reachable in this environment, so libraries like PhpSpreadsheet
 * can't be installed). Supports one sheet, plain values, column widths,
 * horizontal cell merges, and a small palette of named styles.
 */
class SimpleXlsxWriter
{
    /** @var array<int, array{cells: array<int, mixed>, style: int}> */
    protected array $rows = [];

    /** @var array<int, array{row:int, first:int, last:int}> */
    protected array $merges = [];

    /** @var array<int, float> */
    protected array $columnWidths = [];

    public const STYLE_NORMAL = 0;

    public const STYLE_TITLE = 1;    // large bold, dark fill, white text

    public const STYLE_SUBTITLE = 2; // italic gray

    public const STYLE_SECTION = 3;  // bold + light green fill

    public const STYLE_HEADER = 4;   // bold white on dark-green fill, border

    public const STYLE_BODY = 5;     // normal with light border

    /**
     * @param  array<int, mixed>  $cells
     */
    public function addRow(array $cells, int $style = self::STYLE_NORMAL): void
    {
        $this->rows[] = ['cells' => $cells, 'style' => $style];
    }

    /**
     * Merge the cells of the most recently added row from column $first to $last (0-indexed).
     */
    public function mergeRow(int $first, int $last): void
    {
        $this->merges[] = ['row' => count($this->rows), 'first' => $first, 'last' => $last];
    }

    /**
     * @param  array<int, float>  $widths  column widths in Excel character units, 0-indexed
     */
    public function setColumnWidths(array $widths): void
    {
        $this->columnWidths = $widths;
    }

    public function toBinary(): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->relsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml());

        $zip->close();
        $binary = file_get_contents($tmp);
        unlink($tmp);

        return $binary;
    }

    protected function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    protected function relsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    protected function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Report" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    protected function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    protected function stylesXml(): string
    {
        // Fonts: 0 normal, 1 bold, 2 title (white,14,bold), 3 italic gray, 4 header (white,bold)
        $fonts = '<fonts count="5">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="14"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            .'<font><i/><sz val="10"/><color rgb="FF666666"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            .'</fonts>';

        // Fills: 0 none, 1 gray125(reserved), 2 light green (section), 3 dark green (title/header)
        $fills = '<fills count="4">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFDCEEE1"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF1F6F4F"/></patternFill></fill>'
            .'</fills>';

        // Borders: 0 none, 1 thin all around (light gray)
        $borders = '<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left style="thin"><color rgb="FFD9D9D9"/></left><right style="thin"><color rgb="FFD9D9D9"/></right>'
            .'<top style="thin"><color rgb="FFD9D9D9"/></top><bottom style="thin"><color rgb="FFD9D9D9"/></bottom><diagonal/></border>'
            .'</borders>';

        $cellStyleXfs = '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';

        // cellXfs indices must match the STYLE_* constants above
        $cellXfs = '<cellXfs count="6">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' // 0 NORMAL
            .'<xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment vertical="center"/></xf>' // 1 TITLE
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>' // 2 SUBTITLE
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>' // 3 SECTION
            .'<xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>' // 4 HEADER
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>' // 5 BODY
            .'</cellXfs>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .$fonts.$fills.$borders.$cellStyleXfs.$cellXfs
            .'</styleSheet>';
    }

    protected function sheetXml(): string
    {
        $cols = '';
        if (! empty($this->columnWidths)) {
            $cols = '<cols>';
            foreach ($this->columnWidths as $i => $width) {
                $col = $i + 1;
                $cols .= '<col min="'.$col.'" max="'.$col.'" width="'.$width.'" customWidth="1"/>';
            }
            $cols .= '</cols>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .$cols
            .'<sheetData>';

        foreach ($this->rows as $rowIndex => $row) {
            $r = $rowIndex + 1;
            $rowHeight = $row['style'] === self::STYLE_TITLE ? ' ht="22" customHeight="1"' : '';
            $xml .= '<row r="'.$r.'"'.$rowHeight.'>';
            foreach ($row['cells'] as $colIndex => $value) {
                $ref = $this->colLetter($colIndex).$r;
                $style = $row['style'];
                if ($value === null || $value === '') {
                    $xml .= '<c r="'.$ref.'" s="'.$style.'"/>';
                } elseif (is_numeric($value) && ! preg_match('/^0\d/', (string) $value)) {
                    $xml .= '<c r="'.$ref.'" s="'.$style.'"><v>'.$value.'</v></c>';
                } else {
                    $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                    $xml .= '<c r="'.$ref.'" t="inlineStr" s="'.$style.'"><is><t xml:space="preserve">'.$escaped.'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData>';

        if (! empty($this->merges)) {
            $xml .= '<mergeCells count="'.count($this->merges).'">';
            foreach ($this->merges as $merge) {
                $r = $merge['row'];
                $xml .= '<mergeCell ref="'.$this->colLetter($merge['first']).$r.':'.$this->colLetter($merge['last']).$r.'"/>';
            }
            $xml .= '</mergeCells>';
        }

        $xml .= '</worksheet>';

        return $xml;
    }

    protected function colLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $index = intdiv($index - $mod, 26);
        }

        return $letter;
    }
}

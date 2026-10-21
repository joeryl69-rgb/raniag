<?php

namespace App\Services;

use ZipArchive;

class PresentationDocument
{
    /**
     * @param  array<string, mixed>  $built
     */
    public function binary(array $built, string $paper = 'a4', string $orientation = 'portrait'): string
    {
        $left = public_path('images/letterhead/mdrrmo-logo.png');
        $right = public_path('images/letterhead/bayan-logo.png');
        $hasLeft = is_file($left);
        $hasRight = is_file($right);

        $cacheDirectory = storage_path('framework/cache');
        if (! is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0775, true);
        }
        $tmp = tempnam($cacheDirectory, 'docx');
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypes($hasLeft || $hasRight));
        $zip->addFromString('_rels/.rels', $this->packageRels());
        $zip->addFromString('word/document.xml', $this->documentXml($built, $paper, $orientation));
        $zip->addFromString('word/header1.xml', $this->headerXml($built['title'], $hasLeft, $hasRight, $this->contentWidth($paper, $orientation)));
        $zip->addFromString('word/footer1.xml', $this->footerXml());
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRels());
        $zip->addFromString('word/_rels/header1.xml.rels', $this->headerRels($hasLeft, $hasRight));
        $zip->addFromString('word/styles.xml', $this->stylesXml());
        if ($hasLeft) {
            $zip->addFromString('word/media/mdrrmo.png', (string) file_get_contents($left));
        }
        if ($hasRight) {
            $zip->addFromString('word/media/bayan.png', (string) file_get_contents($right));
        }
        $zip->close();

        $binary = file_get_contents($tmp);
        unlink($tmp);

        return $binary === false ? '' : $binary;
    }

    /**
     * @param  array<string, mixed>  $built
     */
    private function documentXml(array $built, string $paper, string $orientation): string
    {
        $span = max(1, count($built['columns']));
        $tableWidth = $this->contentWidth($paper, $orientation);
        $columnWidths = $this->columnWidths($span, $tableWidth);
        $letterhead = ReportLetterhead::templateData();
        $rows = '';
        foreach ($built['rows'] as $index => $row) {
            $shade = $index % 2 === 1 ? '<w:shd w:val="clear" w:color="auto" w:fill="F4F7F8"/>' : '';
            $rows .= $this->dataRow($row['cells'], $shade, $columnWidths);
        }
        if ($built['rows'] === []) {
            $rows .= '<w:tr><w:trPr><w:cantSplit/></w:trPr><w:tc><w:tcPr><w:tcW w:w="'.$tableWidth.'" w:type="dxa"/><w:gridSpan w:val="'.$span.'"/><w:vAlign w:val="top"/></w:tcPr><w:p><w:r><w:t>No records in this selection.</w:t></w:r></w:p></w:tc></w:tr>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<w:body>'
            .'<w:tbl>'
            .'<w:tblPr><w:tblW w:w="'.$tableWidth.'" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblCellMar><w:top w:w="70" w:type="dxa"/><w:left w:w="70" w:type="dxa"/><w:bottom w:w="70" w:type="dxa"/><w:right w:w="70" w:type="dxa"/></w:tblCellMar><w:tblBorders>'
            .'<w:top w:val="single" w:sz="4" w:space="0" w:color="C5D0D6"/>'
            .'<w:left w:val="single" w:sz="4" w:space="0" w:color="C5D0D6"/>'
            .'<w:bottom w:val="single" w:sz="4" w:space="0" w:color="C5D0D6"/>'
            .'<w:right w:val="single" w:sz="4" w:space="0" w:color="C5D0D6"/>'
            .'<w:insideH w:val="single" w:sz="4" w:space="0" w:color="C5D0D6"/>'
            .'<w:insideV w:val="single" w:sz="4" w:space="0" w:color="C5D0D6"/>'
            .'</w:tblBorders></w:tblPr>'
            .'<w:tblGrid>'.$this->grid($columnWidths).'</w:tblGrid>'
            .$this->headerRow($built['columns'], $columnWidths)
            .$rows
            .'</w:tbl>'
            .'<w:sectPr>'
            .$this->pageSize($paper, $orientation)
            .'<w:headerReference w:type="default" r:id="rIdHeader"/>'
            .'<w:footerReference w:type="default" r:id="rIdFooter"/>'
            .'<w:pgMar w:top="'.ReportLetterhead::millimetersToTwips(36).'" w:right="'.ReportLetterhead::millimetersToTwips($letterhead['page_margins_mm']['horizontal']).'" w:bottom="'.ReportLetterhead::millimetersToTwips(22).'" w:left="'.ReportLetterhead::millimetersToTwips($letterhead['page_margins_mm']['horizontal']).'" w:header="240" w:footer="240"/>'
            .'</w:sectPr>'
            .'</w:body></w:document>';
    }

    private function headerXml(string $title, bool $hasLeft, bool $hasRight, int $tableWidth): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .$this->letterhead($hasLeft, $hasRight, $tableWidth, $title)
            .'</w:hdr>';
    }

    private function footerXml(): string
    {
        $font = $this->xml(ReportLetterhead::templateData()['font_family']);
        $text = $this->xml(config('raniag.name', 'RANIAG - MDRRMO Pamplona').' · Official Document');
        $field = static fn (string $name): string => '<w:fldSimple w:instr="'.$name.'"><w:r><w:rPr><w:sz w:val="16"/></w:rPr><w:t>1</w:t></w:r></w:fldSimple>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:p><w:pPr><w:jc w:val="center"/><w:pBdr><w:top w:val="single" w:sz="4" w:space="5" w:color="DCE5EB"/></w:pBdr><w:spacing w:before="80" w:after="0"/></w:pPr>'
            .'<w:r><w:rPr><w:rFonts w:ascii="'.$font.'" w:hAnsi="'.$font.'"/><w:color w:val="64788A"/><w:sz w:val="16"/></w:rPr><w:t>'.$text.' · Page </w:t></w:r>'
            .$field('PAGE')
            .'<w:r><w:rPr><w:rFonts w:ascii="'.$font.'" w:hAnsi="'.$font.'"/><w:color w:val="64788A"/><w:sz w:val="16"/></w:rPr><w:t> of </w:t></w:r>'
            .$field('NUMPAGES')
            .'</w:p></w:ftr>';
    }

    private function letterhead(bool $hasLeft, bool $hasRight, int $tableWidth, string $title): string
    {
        $template = ReportLetterhead::templateData();
        $fontSizes = $template['font_sizes_pt'];
        $lineHeights = $template['line_heights'];
        $colors = $template['colors'];
        $fontFamily = $this->xml($template['font_family']);
        $columns = $template['columns'];
        $left = $hasLeft ? $this->image('rIdLeft', 1) : '';
        $right = $hasRight ? $this->image('rIdRight', 2) : '';
        $text = $this->mastheadParagraph($template['country'], (int) round($fontSizes['country'] * 2), $colors['muted'], true, false, $lineHeights['institution'], $fontFamily)
            .$this->mastheadParagraph($template['province'], $fontSizes['institution'] * 2, $colors['ink'], false, false, $lineHeights['institution'], $fontFamily)
            .$this->mastheadParagraph($template['municipality'], $fontSizes['institution'] * 2, $colors['ink'], false, false, $lineHeights['institution'], $fontFamily)
            .$this->mastheadParagraph($template['office'], $fontSizes['office'] * 2, $colors['office'], false, true, $lineHeights['institution'], $fontFamily)
            .'<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="'.(int) round($template['title_gap_px'] * 1440 / 96).'" w:after="0" w:line="'.(int) round($lineHeights['title'] * 240).'" w:lineRule="auto"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="'.$fontFamily.'" w:hAnsi="'.$fontFamily.'" w:eastAsia="'.$fontFamily.'" w:cs="'.$fontFamily.'"/><w:b/><w:sz w:val="'.($fontSizes['title'] * 2).'"/><w:color w:val="'.$colors['office'].'"/></w:rPr><w:t>'.$this->xml($title).'</w:t></w:r></w:p>';
        $leftWidth = (int) round($tableWidth * $columns[0] / 100);
        $centerWidth = (int) round($tableWidth * $columns[1] / 100);
        $rightWidth = $tableWidth - $leftWidth - $centerWidth;
        $cellPadding = (int) round($template['cell_padding_px'] * 1440 / 96);
        $tableToRuleGap = (int) round($template['table_to_rule_gap_px'] * 1440 / 96);
        $ruleSize = max(1, (int) round($template['rule_width_px'] * 6));

        return '<w:tbl><w:tblPr><w:tblW w:w="'.$tableWidth.'" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblCellMar><w:top w:w="0" w:type="dxa"/><w:left w:w="'.$cellPadding.'" w:type="dxa"/><w:bottom w:w="0" w:type="dxa"/><w:right w:w="'.$cellPadding.'" w:type="dxa"/></w:tblCellMar><w:tblBorders>'
            .'<w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/><w:insideH w:val="nil"/><w:insideV w:val="nil"/>'
            .'</w:tblBorders></w:tblPr>'
            .'<w:tr><w:trPr><w:cantSplit/></w:trPr>'
            .'<w:tc><w:tcPr><w:tcW w:w="'.$leftWidth.'" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr>'.$left.'</w:p></w:tc>'
            .'<w:tc><w:tcPr><w:tcW w:w="'.$centerWidth.'" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr>'.$text.'</w:tc>'
            .'<w:tc><w:tcPr><w:tcW w:w="'.$rightWidth.'" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr>'.$right.'</w:p></w:tc>'
            .'</w:tr></w:tbl>'
            .'<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="'.$ruleSize.'" w:space="1" w:color="'.$colors['rule'].'"/></w:pBdr><w:spacing w:line="'.$tableToRuleGap.'" w:lineRule="exact" w:after="'.$template['content_gap_twips'].'"/></w:pPr></w:p>';
    }

    private function mastheadParagraph(string $text, int $fontSize, string $color, bool $uppercase, bool $bold, float $lineHeight, string $fontFamily): string
    {
        $formatting = ($bold ? '<w:b/>' : '').($uppercase ? '<w:caps/>' : '');

        return '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="0" w:line="'.(int) round($lineHeight * 240).'" w:lineRule="auto"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="'.$fontFamily.'" w:hAnsi="'.$fontFamily.'" w:eastAsia="'.$fontFamily.'" w:cs="'.$fontFamily.'"/>'.$formatting.'<w:sz w:val="'.$fontSize.'"/><w:color w:val="'.$color.'"/></w:rPr><w:t>'.$this->xml($text).'</w:t></w:r></w:p>';
    }

    private function image(string $relation, int $id): string
    {
        $extent = (int) round(ReportLetterhead::templateData()['seal_size_px'] * 9525);

        return '<w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
            .'<wp:extent cx="'.$extent.'" cy="'.$extent.'"/><wp:effectExtent l="0" t="0" r="0" b="0"/>'
            .'<wp:docPr id="'.$id.'" name="Logo '.$id.'"/><wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            .'<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<pic:pic><pic:nvPicPr><pic:cNvPr id="'.$id.'" name="logo'.$id.'.png"/><pic:cNvPicPr/></pic:nvPicPr>'
            .'<pic:blipFill><a:blip r:embed="'.$relation.'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            .'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$extent.'" cy="'.$extent.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            .'</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r>';
    }

    /**
     * @param  list<string>  $labels
     */
    private function headerRow(array $labels, array $widths): string
    {
        $cells = '';
        foreach ($labels as $index => $label) {
            $cells .= '<w:tc><w:tcPr><w:tcW w:w="'.$widths[$index].'" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="E8EEF6"/>'
                .'<w:tcBorders><w:bottom w:val="single" w:sz="12" w:space="0" w:color="1A365D"/></w:tcBorders>'
                .'<w:vAlign w:val="center"/></w:tcPr>'
                .'<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="1A365D"/><w:sz w:val="16"/></w:rPr><w:t>'.$this->xml($label).'</w:t></w:r></w:p></w:tc>';
        }

        return '<w:tr><w:trPr><w:tblHeader/><w:cantSplit/></w:trPr>'.$cells.'</w:tr>';
    }

    /**
     * @param  list<string>  $values
     */
    private function dataRow(array $values, string $shade, array $widths): string
    {
        $cells = '';
        foreach ($values as $index => $value) {
            $cells .= '<w:tc><w:tcPr><w:tcW w:w="'.$widths[$index].'" w:type="dxa"/>'.$shade.'<w:vAlign w:val="top"/></w:tcPr><w:p><w:pPr><w:spacing w:after="40"/></w:pPr><w:r><w:rPr><w:sz w:val="16"/></w:rPr><w:t>'.$this->xml((string) $value).'</w:t></w:r></w:p></w:tc>';
        }

        return '<w:tr><w:trPr><w:cantSplit/></w:trPr>'.$cells.'</w:tr>';
    }

    private function grid(array $widths): string
    {
        $grid = '';
        foreach ($widths as $width) {
            $grid .= '<w:gridCol w:w="'.$width.'"/>';
        }

        return $grid;
    }

    /**
     * @return list<int>
     */
    private function columnWidths(int $count, int $tableWidth): array
    {
        $percentages = match ($count) {
            7 => [12, 13, 22, 16, 10, 14, 13],
            6 => [16, 17, 22, 16, 10, 19],
            default => array_fill(0, $count, intdiv(100, $count)),
        };
        $widths = array_map(
            fn (int $percentage) => intdiv($tableWidth * $percentage, 100),
            $percentages
        );
        $widths[$count - 1] += $tableWidth - array_sum($widths);

        return $widths;
    }

    private function contentWidth(string $paper, string $orientation): int
    {
        $portrait = [
            'a4' => [11906, 16838],
            'letter' => [12240, 15840],
            'legal' => [12240, 20160],
            'folio' => [12240, 18720],
        ];
        [$width, $height] = $portrait[$paper] ?? $portrait['a4'];
        if ($orientation === 'landscape') {
            $width = $height;
        }

        $margin = ReportLetterhead::millimetersToTwips(ReportLetterhead::templateData()['page_margins_mm']['horizontal']);

        return $width - $margin - $margin;
    }

    private function pageSize(string $paper, string $orientation): string
    {
        $portrait = [
            'a4' => [11906, 16838],
            'letter' => [12240, 15840],
            'legal' => [12240, 20160],
            'folio' => [12240, 18720],
        ];
        [$width, $height] = $portrait[$paper] ?? $portrait['a4'];
        if ($orientation === 'landscape') {
            [$width, $height] = [$height, $width];
        }
        $orient = $orientation === 'portrait' ? 'portrait' : 'landscape';

        return '<w:pgSz w:w="'.$width.'" w:h="'.$height.'" w:orient="'.$orient.'"/>';
    }

    private function contentTypes(bool $png): string
    {
        $pngDefault = $png
            ? '<Default Extension="png" ContentType="image/png"/>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .$pngDefault
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/header1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/>'
            .'<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'</Types>';
    }

    private function packageRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>';
    }

    private function documentRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'<Relationship Id="rIdHeader" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="header1.xml"/>'
            .'<Relationship Id="rIdFooter" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/>'
            .'</Relationships>';
    }

    private function headerRels(bool $hasLeft, bool $hasRight): string
    {
        $images = '';
        if ($hasLeft) {
            $images .= '<Relationship Id="rIdLeft" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/mdrrmo.png"/>';
        }
        if ($hasRight) {
            $images .= '<Relationship Id="rIdRight" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/bayan.png"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$images.'</Relationships>';
    }

    private function stylesXml(): string
    {
        $template = ReportLetterhead::templateData();
        $fontFamily = $this->xml($template['font_family']);
        $fontSize = $template['body_font_size_pt'] * 2;

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="'.$fontFamily.'" w:hAnsi="'.$fontFamily.'" w:eastAsia="'.$fontFamily.'" w:cs="'.$fontFamily.'"/><w:sz w:val="'.$fontSize.'"/></w:rPr></w:rPrDefault></w:docDefaults>'
            .'</w:styles>';
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}

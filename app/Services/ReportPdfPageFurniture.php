<?php

namespace App\Services;

use Barryvdh\DomPDF\PDF;
use Dompdf\Canvas;
use Dompdf\FontMetrics;

class ReportPdfPageFurniture
{
    public function add(PDF $pdf, string $title): void
    {
        $pdf->render();

        $template = ReportLetterhead::templateData();
        $leftLogo = public_path('images/letterhead/mdrrmo-logo.png');
        $rightLogo = public_path('images/letterhead/bayan-logo.png');
        $organization = config('raniag.name', 'RANIAG - MDRRMO Pamplona');
        $footer = $organization.' | Official Document - Unauthorized reproduction or alteration is prohibited | Page {PAGE_NUM} of {PAGE_COUNT}';

        $pdf->getDomPDF()->getCanvas()->page_script(
            function (int $pageNumber, int $pageCount, Canvas $canvas, FontMetrics $fontMetrics) use (
                $template,
                $leftLogo,
                $rightLogo,
                $title,
                $footer
            ): void {
                $pointPerMillimeter = 72 / 25.4;
                $pageWidth = $canvas->get_width();
                $pageHeight = $canvas->get_height();
                $horizontalMargin = $template['page_margins_mm']['horizontal'] * $pointPerMillimeter;
                $contentWidth = $pageWidth - 2 * $horizontalMargin;
                $leftColumnWidth = $contentWidth * $template['columns'][0] / 100;
                $middleColumnWidth = $contentWidth * $template['columns'][1] / 100;
                $rightColumnWidth = $contentWidth - $leftColumnWidth - $middleColumnWidth;
                $sealSize = $template['seal_size_px'] * 0.75;
                $sealTop = 7 * $pointPerMillimeter;
                $colors = $template['colors'];

                if (is_file($leftLogo)) {
                    $canvas->image(
                        $leftLogo,
                        $horizontalMargin + ($leftColumnWidth - $sealSize) / 2,
                        $sealTop,
                        $sealSize,
                        $sealSize,
                        'normal'
                    );
                }
                if (is_file($rightLogo)) {
                    $canvas->image(
                        $rightLogo,
                        $horizontalMargin + $leftColumnWidth + $middleColumnWidth + ($rightColumnWidth - $sealSize) / 2,
                        $sealTop,
                        $sealSize,
                        $sealSize,
                        'normal'
                    );
                }

                $centerX = $horizontalMargin + $leftColumnWidth;
                $textRows = [
                    [$template['country'], $template['font_sizes_pt']['country'], $colors['muted'], 5],
                    [$template['province'], $template['font_sizes_pt']['institution'], $colors['ink'], 10],
                    [$template['municipality'], $template['font_sizes_pt']['institution'], $colors['ink'], 15],
                    [$template['office'], $template['font_sizes_pt']['office'], $colors['office'], 20],
                    [$title, $template['font_sizes_pt']['title'], $colors['office'], 26],
                ];

                foreach ($textRows as $index => [$text, $size, $color, $topMillimeters]) {
                    $textFont = $fontMetrics->getFont($template['font_family'], $index >= 3 ? 'bold' : 'normal');
                    $textWidth = $fontMetrics->getTextWidth($text, $textFont, $size);
                    $textX = $centerX + ($middleColumnWidth - $textWidth) / 2;
                    $textY = $topMillimeters * $pointPerMillimeter;
                    $canvas->text($textX, $textY, $text, $textFont, $size, $this->rgb($color));
                }

                $ruleY = 34 * $pointPerMillimeter;
                $canvas->line(
                    $horizontalMargin,
                    $ruleY,
                    $pageWidth - $horizontalMargin,
                    $ruleY,
                    $this->rgb($colors['rule']),
                    max(0.5, $template['rule_width_px'] * 0.75)
                );

                $footerFont = $fontMetrics->getFont($template['font_family'], 'normal');
                $footerSize = 7;
                $footerWidth = $fontMetrics->getTextWidth($footer, $footerFont, $footerSize);
                $footerX = max(0, ($pageWidth - $footerWidth) / 2);
                $footerY = $pageHeight - 13 * $pointPerMillimeter;
                $footerColor = $this->rgb($colors['muted']);

                $canvas->line(
                    $horizontalMargin,
                    $pageHeight - 18 * $pointPerMillimeter,
                    $pageWidth - $horizontalMargin,
                    $pageHeight - 18 * $pointPerMillimeter,
                    [0.86, 0.90, 0.92],
                    0.5
                );
                $footerText = str_replace(
                    ['{PAGE_NUM}', '{PAGE_COUNT}'],
                    [(string) $pageNumber, (string) $pageCount],
                    $footer
                );
                $canvas->text($footerX, $footerY, $footerText, $footerFont, $footerSize, $footerColor);
            }
        );
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function rgb(string $hex): array
    {
        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    }
}

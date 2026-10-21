<?php

namespace App\Services;

class ReportLetterhead
{
    /**
     * @return array{
     *     country: string,
     *     province: string,
     *     municipality: string,
     *     office: string,
     *     font_family: string,
     *     body_font_size_pt: int,
     *     colors: array{ink: string, muted: string, office: string, rule: string},
     *     columns: list<int>,
     *     seal_size_px: int,
     *     font_sizes_pt: array{institution: int, country: float, office: int, title: int},
     *     line_heights: array{institution: float, title: float},
     *     page_margins_mm: array{top: int, horizontal: int, bottom: int},
     *     cell_padding_px: int,
     *     table_to_rule_gap_px: int,
     *     title_gap_px: int,
     *     rule_width_px: int,
     *     rule_gap_px: int,
     *     content_gap_twips: int
     * }
     */
    public static function templateData(): array
    {
        return [
            'country' => 'REPUBLIC OF THE PHILIPPINES',
            'province' => 'Province of Cagayan',
            'municipality' => 'Municipality of Pamplona',
            'office' => 'MDRRMO Pamplona',
            'font_family' => 'Arial',
            'body_font_size_pt' => 10,
            'colors' => [
                'ink' => '263B4D',
                'muted' => '64788A',
                'office' => '173D66',
                'rule' => '173D66',
            ],
            'columns' => [16, 68, 16],
            'seal_size_px' => 58,
            'font_sizes_pt' => [
                'institution' => 10,
                'country' => 8.5,
                'office' => 11,
                'title' => 14,
            ],
            'line_heights' => [
                'institution' => 1.3,
                'title' => 1.2,
            ],
            'page_margins_mm' => [
                'top' => 12,
                'horizontal' => 15,
                'bottom' => 16,
            ],
            'cell_padding_px' => 6,
            'table_to_rule_gap_px' => 10,
            'title_gap_px' => 7,
            'rule_width_px' => 1,
            'rule_gap_px' => 12,
            'content_gap_twips' => 180,
        ];
    }

    public static function millimetersToTwips(float $millimeters): int
    {
        return (int) round($millimeters * 1440 / 25.4);
    }
}

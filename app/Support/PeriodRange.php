<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Shared month / quarter / year windows for the community dashboard,
 * the staff dashboard, and report presets. One definition so a "quarter"
 * on the public board is the same quarter in a downloaded report.
 */
class PeriodRange
{
    public static function key(?string $period): string
    {
        return in_array($period, ['month', 'quarter', 'year'], true) ? $period : 'month';
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     caption: string,
     *     from: Carbon,
     *     to: Carbon,
     *     previous_from: Carbon,
     *     previous_to: Carbon,
     *     buckets: list<array{label: string, from: Carbon, to: Carbon}>
     * }
     */
    public static function resolve(?string $period): array
    {
        $key = self::key($period);
        $now = now();

        if ($key === 'year') {
            $from = $now->copy()->startOfYear();
            $buckets = [];
            for ($i = 4; $i >= 0; $i--) {
                $start = $now->copy()->subYears($i)->startOfYear();
                $buckets[] = [
                    'label' => $start->format('Y'),
                    'from' => $start->copy(),
                    'to' => $i === 0 ? $now->copy()->endOfDay() : $start->copy()->endOfYear(),
                ];
            }

            return [
                'key' => $key,
                'label' => $from->format('Y'),
                'caption' => 'Last 5 years',
                'from' => $from,
                'to' => $now->copy()->endOfDay(),
                'previous_from' => $from->copy()->subYear(),
                'previous_to' => $now->copy()->subYear()->endOfDay(),
                'buckets' => $buckets,
            ];
        }

        if ($key === 'quarter') {
            $from = $now->copy()->startOfQuarter();
            $buckets = [];
            for ($i = 3; $i >= 0; $i--) {
                $start = $now->copy()->subQuarters($i)->startOfQuarter();
                $buckets[] = [
                    'label' => 'Q'.$start->quarter.' '.$start->format('Y'),
                    'from' => $start->copy(),
                    'to' => $i === 0 ? $now->copy()->endOfDay() : $start->copy()->endOfQuarter(),
                ];
            }

            return [
                'key' => $key,
                'label' => 'Q'.$from->quarter.' '.$from->format('Y'),
                'caption' => 'Last 4 quarters',
                'from' => $from,
                'to' => $now->copy()->endOfDay(),
                'previous_from' => $from->copy()->subQuarter(),
                'previous_to' => $now->copy()->subQuarter()->endOfDay(),
                'buckets' => $buckets,
            ];
        }

        $from = $now->copy()->startOfMonth();
        $buckets = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = $now->copy()->subMonths($i)->startOfMonth();
            $buckets[] = [
                'label' => $start->format('M'),
                'from' => $start->copy(),
                'to' => $i === 0 ? $now->copy()->endOfDay() : $start->copy()->endOfMonth(),
            ];
        }

        return [
            'key' => 'month',
            'label' => $from->format('F Y'),
            'caption' => 'Last 6 months',
            'from' => $from,
            'to' => $now->copy()->endOfDay(),
            'previous_from' => $from->copy()->subMonthNoOverflow(),
            'previous_to' => $now->copy()->subMonthNoOverflow()->endOfDay(),
            'buckets' => $buckets,
        ];
    }

    /**
     * @return array{current: int, previous: int, delta: int, percent: int, direction: string}
     */
    public static function change(int $current, int $previous): array
    {
        $delta = $current - $previous;
        if ($previous === 0) {
            $percent = $current > 0 ? 100 : 0;
        } else {
            $percent = (int) round(($delta / $previous) * 100);
        }

        return [
            'current' => $current,
            'previous' => $previous,
            'delta' => $delta,
            'percent' => $percent,
            'direction' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat'),
        ];
    }

    public static function band(int $count): string
    {
        return match (true) {
            $count >= 7 => 'High',
            $count >= 4 => 'Elevated',
            $count >= 2 => 'Watch',
            default => 'Low',
        };
    }
}

<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Shared week / month / year windows for the community dashboard,
 * the staff dashboard, and report presets. One definition so a week
 * on the public board is the same week in a downloaded report.
 */
class PeriodRange
{
    public static function key(?string $period): string
    {
        // A quarter begins on the first of a month, so in January, April,
        // July, and October it counted the same reports as the month.
        if ($period === 'quarter') {
            $period = 'week';
        }

        return in_array($period, ['week', 'month', 'year'], true) ? $period : 'month';
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

        if ($key === 'week') {
            $from = $now->copy()->startOfWeek();
            $buckets = [];
            for ($i = 7; $i >= 0; $i--) {
                $start = $now->copy()->subWeeks($i)->startOfWeek();
                $buckets[] = [
                    'label' => $start->format('M j'),
                    'from' => $start->copy(),
                    'to' => $i === 0 ? $now->copy()->endOfDay() : $start->copy()->endOfWeek(),
                ];
            }

            return [
                'key' => 'week',
                'label' => 'Week of '.$from->format('M j'),
                'caption' => 'Last 8 weeks',
                'from' => $from,
                'to' => $now->copy()->endOfDay(),
                'previous_from' => $from->copy()->subWeek(),
                'previous_to' => $now->copy()->subWeek()->endOfDay(),
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

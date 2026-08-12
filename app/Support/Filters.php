<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class Filters
{
    /**
     * Pull the common search/date-range params off a request into a plain array.
     * Controllers can merge page-specific keys in after calling this.
     */
    public static function fromRequest(Request $request): array
    {
        return [
            'q' => $request->string('q')->trim()->value() ?: null,
            'date_from' => $request->string('date_from')->trim()->value() ?: null,
            'date_to' => $request->string('date_to')->trim()->value() ?: null,
        ];
    }

    /**
     * Apply an inclusive date-range filter to $column using date_from / date_to
     * from $filters. Safe no-op if either key is missing/empty.
     *
     * @param  Builder|BuilderContract  $query
     */
    public static function dateRange($query, string $column, array $filters)
    {
        $today = now()->toDateString();

        $from = $filters['date_from'] ?? null;
        if (! empty($from)) {
            $query->whereDate($column, '>=', min($from, $today));
        }

        $to = $filters['date_to'] ?? null;
        if (! empty($to)) {
            $query->whereDate($column, '<=', min($to, $today));
        }

        return $query;
    }

    /**
     * Apply a LIKE search across the given columns using 'q' from $filters.
     *
     * @param  Builder|BuilderContract  $query
     */
    public static function search($query, array $columns, array $filters)
    {
        $term = trim((string) ($filters['q'] ?? ''));

        if ($term === '') {
            return $query;
        }

        return $query->where(function ($w) use ($columns, $term) {
            foreach ($columns as $i => $column) {
                $method = $i === 0 ? 'where' : 'orWhere';
                $w->{$method}($column, 'like', "%{$term}%");
            }
        });
    }
}

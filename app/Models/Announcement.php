<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class Announcement extends Model
{
    use HasFactory;

    /** Badge values the announcement form can save, in display order. */
    public const TYPES = [
        'Update' => 'Update',
        'New Feature' => 'New Feature',
        'Maintenance' => 'Maintenance',
        'Safety Alert' => 'Safety Alert',
        'Reminder' => 'Reminder',
    ];

    protected $fillable = [
        'title',
        'badge',
        'body',
        'icon',
        'is_published',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Announcement $announcement) {
            $announcement->published_at ??= now();
            $announcement->icon ??= 'bi-megaphone-fill';
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopeApplyListFilters(Builder $query, Request $request, bool $publishedOnly = false): Builder
    {
        if ($publishedOnly) {
            $query->published();
        } else {
            $status = $request->string('status')->value();
            if ($status === 'published') {
                $query->where('is_published', true);
            } elseif ($status === 'hidden') {
                $query->where('is_published', false);
            }
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->trim()->value().'%';
            $query->where(function (Builder $sub) use ($term) {
                $sub->where('title', 'like', $term)
                    ->orWhere('body', 'like', $term)
                    ->orWhere('badge', 'like', $term);
            });
        }

        $type = $request->string('type')->value();
        if ($type !== '' && $type !== 'all') {
            $query->where('badge', $type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('published_at', '>=', $request->string('date_from')->value());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('published_at', '<=', $request->string('date_to')->value());
        }

        return $query;
    }

    public static function listFiltersActive(Request $request, bool $includeStatus = false): bool
    {
        if ($request->filled('q') || $request->filled('date_from') || $request->filled('date_to')) {
            return true;
        }

        $type = $request->string('type')->value();
        if ($type !== '' && $type !== 'all') {
            return true;
        }

        if (! $includeStatus) {
            return false;
        }

        $status = $request->string('status')->value();

        return $status !== '' && $status !== 'all';
    }

    /** Official types first, then any older badge still stored on an announcement. */
    public static function filterableBadges(bool $publishedOnly = false): array
    {
        $presets = array_keys(self::TYPES);
        $query = static::query()->whereNotNull('badge')->where('badge', '!=', '');
        if ($publishedOnly) {
            $query->published();
        }

        $stored = $query->distinct()->pluck('badge')->filter()->values()->all();
        $selected = request('type');
        $selected = is_string($selected) && $selected !== '' && $selected !== 'all' ? $selected : null;
        $ordered = array_values(array_filter(
            $presets,
            fn (string $badge) => in_array($badge, $stored, true) || $badge === $selected
        ));
        $extras = collect($stored)
            ->reject(fn ($badge) => in_array($badge, $presets, true))
            ->sort()
            ->values()
            ->all();
        if ($selected && ! in_array($selected, $ordered, true) && ! in_array($selected, $extras, true)) {
            $extras[] = $selected;
        }

        return array_merge($ordered, $extras);
    }

    /** @return array<string, string> */
    public static function filterablePublicationStatuses(): array
    {
        $states = [];
        if (static::query()->where('is_published', true)->exists()) {
            $states[] = 'published';
        }
        if (static::query()->where('is_published', false)->exists()) {
            $states[] = 'hidden';
        }

        return \App\Support\FilterOptions::present([
            'published' => 'Published',
            'hidden' => 'Hidden',
        ], $states, request('status'));
    }

    public static function typeLabel(string $badge): string
    {
        return self::TYPES[$badge] ?? $badge;
    }
}

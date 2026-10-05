<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PublicHotline extends Model
{
    protected $fillable = [
        'name',
        'number',
        'detail',
        'sort_order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order')->orderBy('name');
    }

    public function dialHref(): string
    {
        $digits = preg_replace('/[^\d+]/', '', $this->number) ?? '';

        return 'tel:'.$digits;
    }
}

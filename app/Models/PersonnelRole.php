<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonnelRole extends Model
{
    protected $fillable = [
        'title',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** Titles usable in a "select personnel role" dropdown, active ones only. */
    public static function activeTitles(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->pluck('title')
            ->all();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HazardZone extends Model
{
    protected $fillable = [
        'hazard_zone_type_id', 'name', 'barangay', 'geometry', 'color',
        'advisory_note', 'advisory_url', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'geometry' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(HazardZoneType::class, 'hazard_zone_type_id');
    }

    public function displayColor(): string
    {
        if (is_string($this->color) && $this->color !== '') {
            return $this->color;
        }

        return $this->type?->color ?: '#b45309';
    }
}

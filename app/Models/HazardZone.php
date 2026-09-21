<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HazardZone extends Model
{
    protected $fillable = [
        'hazard_zone_type_id', 'name', 'barangay', 'geometry',
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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evacuee extends Model
{
    protected $fillable = [
        'evacuation_center_id', 'full_name', 'age', 'sex', 'barangay',
        'is_vulnerable', 'vulnerability_notes', 'checked_in_at', 'checked_out_at',
    ];

    protected function casts(): array
    {
        return [
            'is_vulnerable' => 'boolean',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(EvacuationCenter::class, 'evacuation_center_id');
    }
}

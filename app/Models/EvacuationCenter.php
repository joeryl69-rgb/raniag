<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvacuationCenter extends Model
{
    protected $fillable = [
        'name', 'barangay', 'address', 'latitude', 'longitude',
        'capacity', 'is_open', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_open' => 'boolean',
            'capacity' => 'integer',
        ];
    }

    public function evacuees(): HasMany
    {
        return $this->hasMany(Evacuee::class);
    }
}

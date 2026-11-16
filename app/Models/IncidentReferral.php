<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentReferral extends Model
{
    use HasFactory;

    protected $fillable = [
        'municipality_contact_id',
        'referred_by',
        'municipality',
        'office_name',
        'hotline',
        'location_address',
        'latitude',
        'longitude',
        'public_note',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(MunicipalityContact::class, 'municipality_contact_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }
}

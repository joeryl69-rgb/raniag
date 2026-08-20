<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'agency_id',
        'assigned_by',
        'assigned_to',
        'notes',
        'is_active',
        'acknowledged_at',
        'acknowledged_by',
        'assigned_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'acknowledged_at' => 'datetime',
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}

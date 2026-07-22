<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'requesting_agency_id',
        'requested_by',
        'request_type',
        'request_note',
        'status',
        'admin_comment',
        'generated_path',
        'generated_at',
        'sent_at',
        'failed_reason',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'sent_at' => 'datetime',
        'admin_comment' => 'string',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function requestingAgency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'requesting_agency_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}

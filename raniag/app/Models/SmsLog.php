<?php

namespace App\Models;

use App\Enums\SmsLogStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'user_id',
        'recipient_phone',
        'message',
        'status',
        'provider',
        'provider_message_id',
        'provider_response',
        'sent_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SmsLogStatus::class,
            'provider_response' => 'array',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

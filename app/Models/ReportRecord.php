<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportRecord extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'date_from',
        'date_to',
        'status',
        'report_kind',
        'chapters',
        'snapshot',
        'filed_path',
        'filed_at',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'chapters' => 'array',
            'snapshot' => 'array',
            'filed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFiled(): bool
    {
        return $this->status === 'filed';
    }
}

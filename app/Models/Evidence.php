<?php

namespace App\Models;

use App\Enums\EvidenceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evidence extends Model
{
    use HasFactory;

    protected $table = 'evidence';

    protected $fillable = [
        'incident_id',
        'uploaded_by',
        'type',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'caption',
        'priority',
        'is_gps_capture',
    ];

    protected function casts(): array
    {
        return [
            'type' => EvidenceType::class,
            'file_size' => 'integer',
            'priority' => 'integer',
            'is_gps_capture' => 'boolean',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

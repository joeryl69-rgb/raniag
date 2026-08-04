<?php

namespace App\Models;

use App\Enums\IncidentDocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'uploaded_by',
        'document_type',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'is_camera_capture',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => IncidentDocumentType::class,
            'file_size' => 'integer',
            'is_camera_capture' => 'boolean',
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

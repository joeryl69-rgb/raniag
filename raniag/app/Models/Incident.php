<?php

namespace App\Models;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tracking_number',
        'incident_type_id',
        'agency_id',
        'status',
        'priority',
        'title',
        'description',
        'location_address',
        'barangay',
        'latitude',
        'longitude',
        'reporter_name',
        'reporter_email',
        'reporter_phone',
        'is_anonymous',
        'reported_at',
        'resolved_at',
        'closed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => IncidentStatus::class,
            'priority' => IncidentPriority::class,
            'is_anonymous' => 'boolean',
            'reported_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'meta' => 'array',
        ];
    }

    public function incidentType(): BelongsTo
    {
        return $this->belongsTo(IncidentType::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function currentAssignments(): HasMany
    {
        return $this->assignments()->where('created_at', '>=', $this->created_at);
    }

    public function statusUpdates(): HasMany
    {
        return $this->hasMany(StatusUpdate::class)->orderBy('created_at');
    }

    public function getStatusTimelineAttribute()
    {
        $updates = $this->relationLoaded('statusUpdates') ? $this->statusUpdates : $this->statusUpdates()->get();

        return $updates->filter(function ($update) {
            return $update->created_at >= $this->created_at;
        })->sortBy('created_at')->values();
    }

    public function resolutions(): HasMany
    {
        return $this->hasMany(Resolution::class);
    }

    public function systemNotifications(): HasMany
    {
        return $this->hasMany(SystemNotification::class);
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function documentRequests()
    {
        return $this->hasMany(DocumentRequest::class);
    }

    public function activeAssignment(): HasMany
    {
        return $this->assignments()->where('is_active', true);
    }
}

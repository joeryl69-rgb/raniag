<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'incident_id',
        'type',
        'title',
        'message',
        'data',
        'channel',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Icon + color mapping per notification type, used by the bell dropdown
     * and the full notifications page. Keeping this centralized avoids
     * icon drift between the two views.
     */
    public function icon(): string
    {
        return match ($this->type) {
            'incident.new' => 'bi-exclamation-triangle-fill',
            'incident.assigned' => 'bi-clipboard-check-fill',
            'incident.status_changed' => 'bi-arrow-repeat',
            'incident.outside_aor' => 'bi-sign-turn-right-fill',
            'resolution.submitted' => 'bi-check2-circle',
            'document_request.new' => 'bi-file-earmark-arrow-up-fill',
            'document_request.ready' => 'bi-file-earmark-check-fill',
            'document_request.unavailable' => 'bi-file-earmark-excel-fill',
            default => 'bi-bell-fill',
        };
    }

    public function color(): string
    {
        return match ($this->type) {
            'incident.new', 'incident.outside_aor' => 'danger',
            'incident.assigned', 'document_request.new' => 'primary',
            'incident.status_changed' => 'info',
            'resolution.submitted', 'document_request.ready' => 'success',
            'document_request.unavailable' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Where clicking the notification should navigate to. Falls back to
     * the incident show route for the recipient's own role area, since
     * that's the destination for nearly every event type today.
     */
    public function url(): ?string
    {
        if (! empty($this->data['url'])) {
            return $this->data['url'];
        }

        if ($this->incident_id && $this->user) {
            $area = match (true) {
                $this->user->isAdministrator() => 'admin',
                $this->user->isAgency() => 'agency',
                $this->user->isPersonnel() => 'personnel',
                default => null,
            };

            if ($area) {
                return rtrim(config('app.url'), '/')."/{$area}/incidents/{$this->incident_id}";
            }
        }

        return null;
    }
}

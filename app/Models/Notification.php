<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Route;

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
     *
     * Uses named routes with relative (non-absolute) URLs rather than
     * string-concatenating config('app.url') — that config value is
     * frequently stale in local/tunnelled/deployed environments (it
     * doesn't auto-track the domain actually being served), which was
     * sending clicks to the wrong host entirely.
     */
    public function url(): ?string
    {
        if (! empty($this->data['url'])) {
            return $this->data['url'];
        }

        if (str_starts_with((string) $this->type, 'document_request.')) {
            $documentUrl = $this->documentRequestUrl();
            if ($documentUrl) {
                return $documentUrl;
            }
        }

        if ($this->incident_id && $this->user) {
            $routeName = match (true) {
                $this->user->isAdministrator() => 'admin.incidents.show',
                $this->user->isAgency() => 'agency.incidents.show',
                $this->user->isPersonnel() => 'personnel.incidents.show',
                default => null,
            };

            if ($routeName && \Illuminate\Support\Facades\Route::has($routeName)) {
                return route($routeName, $this->incident_id, absolute: false);
            }
        }

        return null;
    }

    /**
     * Document-request notices open the request itself, or the generated
     * file once it exists. Case notices stay on the incident.
     */
    private function documentRequestUrl(): ?string
    {
        if (! $this->user) {
            return null;
        }

        $requestId = $this->data['document_request_id'] ?? null;
        $documentRequest = $requestId
            ? DocumentRequest::query()->find($requestId)
            : null;

        if (! $documentRequest && $this->incident_id) {
            $documentRequest = DocumentRequest::query()
                ->where('incident_id', $this->incident_id)
                ->latest('id')
                ->first();
        }

        if ($this->user->isAdministrator() && Route::has('admin.document_requests.index')) {
            return route('admin.document_requests.index', array_filter([
                'request' => $documentRequest?->id,
                'status' => $documentRequest && $documentRequest->status !== 'pending' ? 'all' : null,
            ]), absolute: false);
        }

        if ($documentRequest?->generated_path) {
            return '/storage/'.ltrim((string) $documentRequest->generated_path, '/');
        }

        if ($this->user->isAgency() && Route::has('agency.document_requests.index')) {
            return route('agency.document_requests.index', array_filter([
                'request' => $documentRequest?->id,
            ]), absolute: false);
        }

        return null;
    }
}

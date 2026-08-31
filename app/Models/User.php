<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Mail\ResetPasswordMail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Send a branded reset-password email instead of Laravel's plain
     * default notification (which also exposes the raw reset URL as
     * fallback text — this replaces that behavior entirely).
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ]);

        Mail::to($this->getEmailForPasswordReset())->send(new ResetPasswordMail($url));
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'agency_id',
        'phone',
        'role_title',
        'team_assignment',
        'is_active',
        'avatar_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function assignmentsMade(): HasMany
    {
        return $this->hasMany(Assignment::class, 'assigned_by');
    }

    public function assignmentsReceived(): HasMany
    {
        return $this->hasMany(Assignment::class, 'assigned_to');
    }

    public function statusUpdates(): HasMany
    {
        return $this->hasMany(StatusUpdate::class);
    }

    public function resolutions(): HasMany
    {
        return $this->hasMany(Resolution::class, 'resolved_by');
    }


    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Public URL for this user's profile photo, or null if they haven't
     * uploaded one (callers fall back to an initials/icon avatar).
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path
            ? \Illuminate\Support\Facades\Storage::url($this->avatar_path)
            : null;
    }

    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name));
        $initials = collect($words)->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');

        return $initials !== '' ? $initials : '?';
    }

    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator;
    }

    public function isAgency(): bool
    {
        return $this->role === UserRole::Agency;
    }

    public function isPersonnel(): bool
    {
        return $this->role === UserRole::Personnel;
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->isPersonnel()
            ? ($this->role_title ?? $this->name)
            : $this->name;
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            UserRole::Administrator => 'admin.dashboard',
            UserRole::Agency => 'agency.dashboard',
            UserRole::Personnel => 'personnel.dashboard',
        };
    }
}

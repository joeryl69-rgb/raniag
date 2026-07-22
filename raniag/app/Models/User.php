<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    public function systemNotifications(): HasMany
    {
        return $this->hasMany(SystemNotification::class);
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
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

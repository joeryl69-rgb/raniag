<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackSubmission extends Model
{
    use HasFactory;

    /**
     * Support Center categories shown as icon cards on the submission form
     * (public + agency). Broader than the original four so it mirrors a
     * real helpdesk intake: login trouble, account, reports, technical,
     * data, suggestions, other.
     */
    public const CATEGORIES = [
        'login' => ['label' => 'Login Issues', 'hint' => 'Invalid credentials, cannot log in', 'icon' => 'bi-shield-lock'],
        'account' => ['label' => 'Account', 'hint' => 'Profile, password reset', 'icon' => 'bi-person-gear'],
        'report' => ['label' => 'Incident Reports', 'hint' => 'Cannot submit, tracking errors', 'icon' => 'bi-file-earmark-text'],
        'technical' => ['label' => 'Technical', 'hint' => 'Page errors, loading problems', 'icon' => 'bi-bug'],
        'data' => ['label' => 'Data', 'hint' => 'Incorrect information', 'icon' => 'bi-database'],
        'suggestion' => ['label' => 'Suggestion', 'hint' => 'Ideas to improve RANIAG', 'icon' => 'bi-lightbulb'],
        'other' => ['label' => 'Other', 'hint' => 'Other concerns', 'icon' => 'bi-three-dots'],
        // Legacy values kept so historical rows still render correctly.
        'feedback' => ['label' => 'General Feedback', 'hint' => 'General comments', 'icon' => 'bi-chat-square-text'],
        'concern' => ['label' => 'Concern', 'hint' => 'Raise a concern', 'icon' => 'bi-exclamation-circle'],
        'bug' => ['label' => 'Bug Report', 'hint' => 'Something is broken', 'icon' => 'bi-bug'],
    ];

    protected $fillable = [
        'category',
        'submitted_via',
        'agency_id',
        'submitted_by',
        'subject',
        'message',
        'submitter_name',
        'submitter_email',
        'status',
        'admin_notes',
        'admin_reply',
        'replied_by',
        'replied_at',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'replied_at' => 'datetime',
    ];

    public function replier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category]['label'] ?? ucfirst($this->category);
    }

    public function categoryIcon(): string
    {
        return self::CATEGORIES[$this->category]['icon'] ?? 'bi-chat-square-text';
    }

    public function isFromAgency(): bool
    {
        return $this->submitted_via === 'agency';
    }
}

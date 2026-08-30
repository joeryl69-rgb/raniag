<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
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

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'concern' => 'Concern',
            'suggestion' => 'Suggestion',
            'bug' => 'Bug Report',
            default => 'General Feedback',
        };
    }

    public function categoryIcon(): string
    {
        return match ($this->category) {
            'concern' => 'bi-exclamation-circle',
            'suggestion' => 'bi-lightbulb',
            'bug' => 'bi-bug',
            default => 'bi-chat-square-text',
        };
    }
}

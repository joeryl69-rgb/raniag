<?php

namespace App\Mail;

use App\Models\FeedbackSubmission;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FeedbackReplyMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly FeedbackSubmission $feedback,
    ) {}

    public function build(): self
    {
        $subject = 'RANIAG: Response to your '.$this->feedback->categoryLabel().' — "'.$this->feedback->subject.'"';

        return $this->subject($subject)
            ->view('emails.feedback.reply')
            ->with([
                'feedback' => $this->feedback,
                // admin_reply is authored with the rich-text editor in
                // Admin > Feedback & Concerns and stored as sanitized HTML.
                'replyHtml' => $this->feedback->admin_reply,
            ]);
    }
}

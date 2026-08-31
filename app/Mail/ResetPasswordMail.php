<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $resetUrl,
    ) {}

    public function build(): self
    {
        return $this->subject('RANIAG: Reset your password')
            ->view('emails.auth.reset-password')
            ->with(['resetUrl' => $this->resetUrl]);
    }
}

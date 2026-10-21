<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ResetPasswordMail extends Mailable
{
    public function __construct(
        public readonly string $resetUrl,
    ) {}

    public function build(): self
    {
        return $this->subject('RANIAG: Reset your password')
            ->view('emails.auth.reset-password')
            ->text('emails.auth.reset-password-text')
            ->with(['resetUrl' => $this->resetUrl]);
    }
}

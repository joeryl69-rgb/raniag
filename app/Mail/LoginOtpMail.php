<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $name,
    ) {}

    public function build(): self
    {
        return $this->subject('RANIAG: Your login verification code')
            ->view('emails.auth.login-otp')
            ->with([
                'code' => $this->code,
                'name' => $this->name,
            ]);
    }
}

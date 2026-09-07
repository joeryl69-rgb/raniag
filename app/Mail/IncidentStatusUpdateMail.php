<?php

namespace App\Mail;

use App\Models\Incident;
use Illuminate\Mail\Mailable;

class IncidentStatusUpdateMail extends Mailable
{
    public function __construct(
        public readonly Incident $incident,
        public readonly string $updateMessage,
    ) {}

    public function build(): self
    {
        return $this->subject('RANIAG: Update for report '.$this->incident->tracking_number)
            ->view('emails.incidents.status-update')
            ->with([
                'incident' => $this->incident,
                'updateMessage' => $this->updateMessage,
            ]);
    }
}

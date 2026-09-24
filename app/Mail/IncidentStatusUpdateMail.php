<?php

namespace App\Mail;

use App\Models\Incident;
use Illuminate\Mail\Mailable;

class IncidentStatusUpdateMail extends Mailable
{
    public function __construct(
        public readonly Incident $incident,
        public readonly string $updateMessage,
        public readonly string $trackUrl = '',
    ) {}

    public function build(): self
    {
        $trackUrl = $this->trackUrl !== ''
            ? $this->trackUrl
            : route('public.track', ['tracking_number' => $this->incident->tracking_number]);

        return $this->subject('RANIAG: Update for report '.$this->incident->tracking_number)
            ->view('emails.incidents.status-update')
            ->with([
                'incident' => $this->incident,
                'updateMessage' => $this->updateMessage,
                'trackUrl' => $trackUrl,
            ]);
    }
}

<?php

namespace App\Mail;

use App\Models\Incident;
use Illuminate\Mail\Mailable;

class IncidentReportReceivedMail extends Mailable
{
    public function __construct(
        public readonly Incident $incident,
        public readonly string $trackUrl,
    ) {}

    public function build(): self
    {
        return $this->subject('RANIAG: We received your report '.$this->incident->tracking_number)
            ->view('emails.incidents.report-received')
            ->with([
                'incident' => $this->incident,
                'trackUrl' => $this->trackUrl,
            ]);
    }
}

<?php

namespace App\Mail;

use App\Models\DocumentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentRequestApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly DocumentRequest $documentRequest,
    ) {}

    public function build(): self
    {
        $incident = $this->documentRequest->incident()->first();

        $trackingNumber = $incident?->tracking_number ?? 'N/A';
        $subject = "RANIAG: Printable PDF Approved (Tracking {$trackingNumber})";

        $diskPath = $this->documentRequest->generated_path;

        return $this->subject($subject)
            ->view('emails.document_requests.approved')
            ->with([
                'documentRequest' => $this->documentRequest,
                'tracking_number' => $trackingNumber,
            ])
            ->attachFromStorageDisk('public', $diskPath, basename((string) $diskPath), [
                'mime' => 'application/pdf',
            ]);
    }
}

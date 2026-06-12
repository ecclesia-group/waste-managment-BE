<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HandoverZoneProviderAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $providerName,
        public string $requestCode,
        public string $requesterType,
        public string $requesterName,
        public string $requesterPhone,
        public string $pickupLocation,
        public string $wasteTypes,
        public string $title,
        public ?string $fleetTypeLabel = null,
        public float $amountPayable = 0,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New {$this->requesterType} handover in your zone — {$this->requestCode}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'handoverZoneProviderAlertMail',
        );
    }
}

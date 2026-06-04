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
            htmlString: '<p>Hello '.$this->providerName.',</p>'
                .'<p>A new <strong>'.$this->requesterType.'</strong> waste handover request is available in your zone.</p>'
                .'<p><strong>Code:</strong> '.$this->requestCode.'</p>'
                .'<p><strong>Title:</strong> '.$this->title.'</p>'
                .'<p><strong>Location:</strong> '.$this->pickupLocation.'</p>'
                .'<p><strong>Waste type:</strong> '.$this->wasteTypes.'</p>'
                .'<p><strong>Requester:</strong> '.$this->requesterName
                .($this->requesterPhone !== '' ? ' — '.$this->requesterPhone : '').'</p>'
                .'<p>Log in to the provider app to accept this request.</p>',
        );
    }
}

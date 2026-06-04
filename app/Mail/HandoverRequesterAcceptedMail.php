<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HandoverRequesterAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $requesterName,
        public string $requestCode,
        public string $providerName,
        public string $providerPhone,
        public string $fleetLabel,
        public ?string $driverName,
        public string $pickupLocation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your handover request was accepted — '.$this->requestCode,
        );
    }

    public function content(): Content
    {
        $driverLine = $this->driverName
            ? '<p><strong>Driver:</strong> '.$this->driverName.'</p>'
            : '';

        return new Content(
            htmlString: '<p>Hello '.$this->requesterName.',</p>'
                .'<p><strong>'.$this->providerName.'</strong> has accepted your waste handover request '
                .'<strong>'.$this->requestCode.'</strong> and is on the way.</p>'
                .'<p><strong>Pickup location:</strong> '.$this->pickupLocation.'</p>'
                .'<p><strong>Provider phone:</strong> '.$this->providerPhone.'</p>'
                .'<p><strong>Fleet:</strong> '.$this->fleetLabel.'</p>'
                .$driverLine
                .'<p>Please keep your phone available so they can reach you for directions.</p>',
        );
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HandoverDriverAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $driverName,
        public string $requestCode,
        public string $requesterName,
        public string $pickupLocation,
        public float $feeAmount,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New waste handover request '.$this->requestCode,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Hello '.$this->driverName.',</p>'
                .'<p>'.$this->requesterName.' submitted handover request <strong>'.$this->requestCode.'</strong>.</p>'
                .'<p>Location: '.$this->pickupLocation.'</p>'
                .'<p>Fee: GHS '.number_format($this->feeAmount, 2).'</p>'
                .'<p>Your provider can accept this request in the app.</p>',
        );
    }
}

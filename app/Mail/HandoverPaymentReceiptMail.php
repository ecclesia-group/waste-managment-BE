<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HandoverPaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array<string, mixed>  $receipt */
    public function __construct(
        public string $recipientName,
        public array $receipt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment receipt — '.$this->receipt['handover_code'],
        );
    }

    public function content(): Content
    {
        $amount = number_format((float) ($this->receipt['amount'] ?? 0), 2);
        $method = (string) ($this->receipt['payment_method'] ?? 'N/A');
        $provider = (string) (($this->receipt['accepted_provider']['name'] ?? null) ?: 'Provider');

        return new Content(
            htmlString: '<p>Hello '.$this->recipientName.',</p>'
                .'<p>Thank you. Your payment for handover <strong>'.$this->receipt['handover_code'].'</strong> was received.</p>'
                .'<p><strong>Amount:</strong> GHS '.$amount.'</p>'
                .'<p><strong>Method:</strong> '.$method.'</p>'
                .'<p><strong>Collected by:</strong> '.$provider.'</p>'
                .'<p><strong>Receipt #:</strong> '.$this->receipt['receipt_number'].'</p>'
                .'<p>You can download this receipt anytime from the app.</p>',
        );
    }
}

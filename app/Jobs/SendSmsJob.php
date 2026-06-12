<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $phoneNumber,
        public string $message,
        public string $from,
        public string $context = 'general',
    ) {
        // $this->onQueue('sms');
    }

    public function handle(): void
    {
        $endpoint = 'https://api.txtconnect.net/dev/api/sms/send';

        $payload = [
            'to' => $this->phoneNumber,
            'from' => $this->from,
            'unicode' => 1,
            'sms' => $this->message,
        ];

        $headers = [
            "Authorization: Bearer " . "2p6iDItRUfCFxjVBXbm9cGQ5eAYln0NZPzEqsLKrJvWy8hgou3",
            "Content-Type: application/json"
        ];


        try {
            $response = Http::withHeaders($headers)->post($endpoint, $payload);

            Log::channel('sent_sms')->info('SMS API Response', [
                'context' => $this->context,
                'phone_number' => $this->phoneNumber,
                'response' => json_encode($response->json()),
            ]);

            if (! $response->successful()) {
                Log::channel('sent_sms')->warning('SMS provider non-success', [
                    'context' => $this->context,
                    'phone_number' => $this->phoneNumber,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Throwable $e) {
            Log::channel('sent_sms')->error('SendSmsJob exception', [
                'context' => $this->context,
                'phone_number' => $this->phoneNumber,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('sent_sms')->error('SendSmsJob failed', [
            'context' => $this->context,
            'phone_number' => $this->phoneNumber,
            'error' => $exception->getMessage(),
        ]);
    }
}

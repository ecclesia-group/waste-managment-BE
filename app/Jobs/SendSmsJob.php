<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $phoneNumber,
        public string $message,
        public string $context = 'general',
    ) {}

    public function handle(): void
    {
        $endpoint = config('services.sms.endpoint');
        $token = config('services.sms.token');
        $from = config('services.sms.from', 'APP');

        if (empty($endpoint) || empty($token)) {
            Log::warning('SMS job: missing endpoint or token', ['context' => $this->context]);

            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(15)->post($endpoint, [
                'to' => $this->phoneNumber,
                'from' => $from,
                'unicode' => 1,
                'sms' => $this->message,
            ]);

            if (! $response->successful()) {
                Log::warning('SMS provider non-success', [
                    'context' => $this->context,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('SMS job exception', [
                'context' => $this->context,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

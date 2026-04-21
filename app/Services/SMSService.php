<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use Illuminate\Support\Facades\Log;

class SMSService
{
    /**
     * Queue SMS delivery (configure queue worker). Uses env-driven HTTP provider when enabled.
     */
    public function queue(string $phoneNumber, string $message, string $context = 'general'): void
    {
        if (! config('services.sms.enabled', false)) {
            Log::debug('SMS skipped (disabled)', ['context' => $context, 'to' => $phoneNumber]);

            return;
        }

        SendSmsJob::dispatch($phoneNumber, $message, $context);
    }
}

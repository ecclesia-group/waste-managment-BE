<?php

namespace App\Services;

use App\Jobs\SendSmsJob;

class SMSService
{
    /**
     * Queue SMS delivery via SendSmsJob (respects SMS_ENABLED).
     */
    public function queue(string $phoneNumber, string $message, string $context = 'general'): void
    {
        if (! config('services.sms.enabled', false)) {
            return;
        }

        SendSmsJob::dispatch(
            $phoneNumber,
            $message,
            (string) config('services.sms.from', 'WMS'),
            $context
        );
    }
}

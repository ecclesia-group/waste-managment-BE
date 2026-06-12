<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $email,
        public string $mailableClass,
        public array $parameters,
        public string $context = 'general',
    ) {
        // $this->onQueue('emails');
    }

    public function handle(): void
    {
        if (! class_exists($this->mailableClass)) {
            Log::error('SendEmailJob: mailable class not found', [
                'context' => $this->context,
                'class' => $this->mailableClass,
                'email' => $this->email,
            ]);

            return;
        }

        Mail::to($this->email)->send(new $this->mailableClass(...$this->parameters));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendEmailJob failed', [
            'context' => $this->context,
            'email' => $this->email,
            'class' => $this->mailableClass,
            'error' => $exception->getMessage(),
        ]);
    }
}

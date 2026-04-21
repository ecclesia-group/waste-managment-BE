<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Paystack charge webhooks — extend to finalize Payment rows and tie to pickups.
 * Set PAYSTACK_SECRET_KEY and configure webhook URL in Paystack dashboard.
 */
class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $secret = config('services.paystack.secret');
        if (empty($secret)) {
            return response()->json(['message' => 'Webhook not configured'], 503);
        }

        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        $computed = hash_hmac('sha512', $payload, $secret);

        if (! hash_equals($computed, (string) $signature)) {
            Log::warning('Paystack webhook: invalid signature');

            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $data = $request->all();
        $event = $data['event'] ?? null;
        $reference = $data['data']['reference'] ?? $data['data']['transaction']['reference'] ?? null;

        if ($reference) {
            Payment::query()->where('transaction_id', $reference)->update([
                'status' => match ($event) {
                    'charge.success' => 'successful',
                    'charge.failed' => 'failed',
                    default => 'pending',
                },
            ]);
        }

        return response()->json(['ok' => true]);
    }
}

<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\CalPay\CalPayPaymentFinalizer;
use App\Services\CalPay\CalPayResponseParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * CalPay server-to-server callback (configure CALPAY_CALLBACK_URL in .env).
 */
class CalPayCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('CalPay callback received', ['payload' => $payload]);

        $orderCode = CalPayResponseParser::extractOrderCodeFromCallback($payload);
        $paymentCode = CalPayResponseParser::extractPaymentCodeFromCallback($payload);

        $payment = $this->findPayment($orderCode, $paymentCode);

        if (! $payment) {
            Log::warning('CalPay callback: payment not found', [
                'order_code' => $orderCode,
                'payment_code' => $paymentCode,
            ]);

            return response()->json(['ok' => false, 'message' => 'Payment not found'], 404);
        }

        $rawStatus = $this->extractStatus($payload);
        $finalizer = app(CalPayPaymentFinalizer::class);
        $status = $finalizer->normalizeStatus($rawStatus);

        $payment->callback_payload = $payload;
        $payment->save();

        $finalizer->apply($payment->fresh(), $status);

        return response()->json([
            'ok' => true,
            'order_code' => $orderCode ?? $payment->calpay_order_code,
            'status' => $status,
        ]);
    }

    private function findPayment(?string $orderCode, ?string $paymentCode): ?Payment
    {
        if ($orderCode) {
            $found = Payment::query()
                ->where('calpay_order_code', $orderCode)
                ->orWhere('transaction_id', $orderCode)
                ->orWhere('gateway_payload->request_order_code', $orderCode)
                ->orWhere('gateway_payload->result_order_code', $orderCode)
                ->latest()
                ->first();
            if ($found) {
                return $found;
            }
        }

        if ($paymentCode) {
            return Payment::query()
                ->where('gateway_payload->payment_code', $paymentCode)
                ->latest()
                ->first();
        }

        return null;
    }

    private function extractStatus(array $payload): mixed
    {
        $inner = CalPayResponseParser::unwrap($payload);

        return data_get($payload, 'paymentStatus')
            ?? data_get($payload, 'PAYMENTSTATUS')
            ?? data_get($inner, 'paymentStatus')
            ?? data_get($payload, 'status')
            ?? data_get($inner, 'status')
            ?? data_get($payload, 'transactionStatus')
            ?? ((($inner['SUCCESS'] ?? false) === true) ? 'paid' : 'pending');
    }
}

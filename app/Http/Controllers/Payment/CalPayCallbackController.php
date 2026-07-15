<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\CalPay\CalPayPaymentFinalizer;
use App\Services\CalPay\CalPayResponseParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * CalPay server callback + browser return URLs.
 * Configure CALPAY_CALLBACK_URL = {APP_URL}/api/payment_callback
 * datacompleteurl / datacancelurl may point here or at the client app.
 */
class CalPayCallbackController extends Controller
{
    public function handle(Request $request)
    {
        return $this->process($request, source: 'callback');
    }

    /** Browser landings after CalPay (fixes backend /payment/success|cancelled 404). */
    public function browserReturn(Request $request, string $outcome)
    {
        $result = $this->process($request, source: 'browser_'.$outcome);

        $clientBase = rtrim((string) (config('custom.urls.client_url') ?: ''), '/');
        if ($clientBase !== '') {
            $path = $outcome === 'success' ? '/payment/success' : '/payment/cancelled';
            $query = $request->query();
            $url = $clientBase.$path.($query ? ('?'.http_build_query($query)) : '');

            return redirect()->away($url);
        }

        $ok = (bool) data_get($result->getData(true), 'ok');
        $status = (string) data_get($result->getData(true), 'status', 'unknown');
        $title = $outcome === 'success' ? 'Payment completed' : 'Payment cancelled';
        $hint = $ok
            ? 'Status: '.$status.'. Return to the client app and open the dashboard — it will confirm registration payment.'
            : 'We could not match this to a payment yet. Return to the client app and tap “Check payment status”.';

        return response(
            '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$title.'</title></head>'
            .'<body style="font-family:system-ui,sans-serif;max-width:520px;margin:48px auto;padding:0 16px;line-height:1.5">'
            .'<h1>'.$title.'</h1><p>'.$hint.'</p>'
            .'<p style="color:#666;font-size:14px">You can close this tab.</p></body></html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    private function process(Request $request, string $source)
    {
        $payload = array_merge($request->query(), $request->all());

        Log::info('CalPay inbound', [
            'source' => $source,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'query' => $request->query(),
            'body' => $request->all(),
            'raw' => $request->getContent(),
            'content_type' => $request->header('Content-Type'),
        ]);

        $orderCode = CalPayResponseParser::extractOrderCodeFromCallback($payload);
        $paymentCode = CalPayResponseParser::extractPaymentCodeFromCallback($payload);
        $payment = $this->findPayment($orderCode, $paymentCode);

        if (! $payment) {
            Log::warning('CalPay inbound: payment not found', [
                'source' => $source,
                'order_code' => $orderCode,
                'payment_code' => $paymentCode,
                'payload_keys' => array_keys($payload),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Payment not found',
                'order_code' => $orderCode,
                'payment_code' => $paymentCode,
            ], 404);
        }

        $rawStatus = $this->extractStatus($payload, $source);
        $finalizer = app(CalPayPaymentFinalizer::class);
        $status = $finalizer->normalizeStatus($rawStatus);
        $before = $payment->status;

        $payment->callback_payload = array_merge(
            is_array($payment->callback_payload) ? $payment->callback_payload : [],
            [
                'source' => $source,
                'received_at' => now()->toIso8601String(),
                'payload' => $payload,
            ]
        );
        $payment->save();

        $finalizer->apply($payment->fresh(), $status);
        $payment->refresh();

        Log::info('CalPay inbound: payment updated', [
            'source' => $source,
            'payment_id' => $payment->id,
            'order_code' => $orderCode ?? $payment->calpay_order_code,
            'payment_code' => $paymentCode,
            'raw_status' => $rawStatus,
            'normalized_status' => $status,
            'status_before' => $before,
            'status_after' => $payment->status,
            'payment_type' => $payment->payment_type,
            'client_slug' => $payment->client_slug,
            'registration_status' => optional($payment->client)->registration_status,
        ]);

        return response()->json([
            'ok' => true,
            'order_code' => $orderCode ?? $payment->calpay_order_code,
            'payment_code' => $paymentCode,
            'status' => $payment->status,
            'status_before' => $before,
        ]);
    }

    private function findPayment(?string $orderCode, ?string $paymentCode): ?Payment
    {
        if ($orderCode) {
            $found = Payment::query()
                ->where(function ($q) use ($orderCode) {
                    $q->where('calpay_order_code', $orderCode)
                        ->orWhere('transaction_id', $orderCode)
                        ->orWhere('gateway_payload->request_order_code', $orderCode)
                        ->orWhere('gateway_payload->result_order_code', $orderCode);
                })
                ->latest('id')
                ->first();

            if ($found) {
                return $found;
            }
        }

        if ($paymentCode) {
            return Payment::query()
                ->where(function ($q) use ($paymentCode) {
                    $q->where('gateway_payload->payment_code', $paymentCode)
                        ->orWhere('gateway_payload->payment_token', $paymentCode);
                })
                ->latest('id')
                ->first();
        }

        return null;
    }

    private function extractStatus(array $payload, string $source): mixed
    {
        $inner = CalPayResponseParser::unwrap($payload);
        $result = CalPayResponseParser::firstResult($payload) ?? [];

        $candidates = [
            data_get($payload, 'paymentStatus'),
            data_get($payload, 'PAYMENTSTATUS'),
            data_get($payload, 'payment_status'),
            data_get($payload, 'transactionStatus'),
            data_get($payload, 'TRANSACTIONSTATUS'),
            data_get($payload, 'status'),
            data_get($payload, 'STATUS'),
            data_get($payload, 'RESPONSECODE'),
            data_get($payload, 'responseCode'),
            data_get($payload, 'CODE'),
            data_get($inner, 'paymentStatus'),
            data_get($inner, 'PAYMENTSTATUS'),
            data_get($inner, 'status'),
            data_get($inner, 'STATUS'),
            data_get($inner, 'CODE'),
            data_get($inner, 'RESPONSECODE'),
            data_get($result, 'PAYMENTSTATUS'),
            data_get($result, 'STATUS'),
            data_get($result, 'CODE'),
        ];

        foreach ($candidates as $value) {
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        if (($inner['SUCCESS'] ?? false) === true) {
            return 'paid';
        }

        // Browser success landing without an explicit status: treat as paid.
        // Do NOT treat browser_cancelled as cancelled by itself — CalPay often
        // lands on cancel URLs even after a successful charge; trust callback payload.
        if (str_contains($source, 'browser_success')) {
            return 'paid';
        }

        return 'pending';
    }
}

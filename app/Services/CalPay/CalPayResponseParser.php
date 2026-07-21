<?php

namespace App\Services\CalPay;

/**
 * CalPay wraps the real payload in a JSON string under "return".
 *
 * @see Sample: { "return": "{\"SUCCESS\":true,\"RESULT\":[{\"APIPAYREDIRECTURL\":\"...\"}]}" }
 */
class CalPayResponseParser
{
    public static function unwrap(mixed $body): array
    {
        if (! is_array($body)) {
            return [];
        }

        if (isset($body['return']) && is_string($body['return'])) {
            $decoded = json_decode($body['return'], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $body;
    }

    public static function firstResult(mixed $body): ?array
    {
        $inner = self::unwrap($body);
        $results = $inner['RESULT'] ?? null;

        if (! is_array($results) || $results === []) {
            return null;
        }

        return $results[0] ?? null;
    }

    public static function isSuccess(mixed $body): bool
    {
        $inner = self::unwrap($body);

        return ($inner['SUCCESS'] ?? false) === true
            || (string) ($inner['CODE'] ?? '') === '0';
    }

    public static function message(mixed $body): ?string
    {
        $inner = self::unwrap($body);

        return isset($inner['MESSAGE']) ? (string) $inner['MESSAGE'] : null;
    }

    public static function checkoutUrl(mixed $body): ?string
    {
        $row = self::firstResult($body);
        $url = $row['APIPAYREDIRECTURL'] ?? $row['apipayredirecturl'] ?? null;

        if (! is_string($url) || $url === '') {
            return null;
        }

        return str_replace('\\/', '/', $url);
    }

    public static function paymentMeta(mixed $body): array
    {
        $row = self::firstResult($body);
        if ($row === null) {
            return [];
        }

        return [
            'payment_token' => $row['PAYMENTTOKEN'] ?? $row['payToken'] ?? $row['PAYTOKEN'] ?? null,
            'pay_token' => $row['payToken'] ?? $row['PAYTOKEN'] ?? $row['PAYMENTTOKEN'] ?? null,
            'payment_code' => $row['PAYMENTCODE'] ?? $row['paymentCode'] ?? null,
            'short_pay_code' => $row['SHORTPAYCODE'] ?? null,
            'order_code' => $row['ORDERCODE'] ?? $row['orderCode'] ?? null,
            'qr_code_url' => isset($row['QRLCODEURL'])
                ? str_replace('\\/', '/', (string) $row['QRLCODEURL'])
                : (isset($row['qrCodeUrl']) ? str_replace('\\/', '/', (string) $row['qrCodeUrl']) : null),
            'description' => $row['DESCRIPTION'] ?? $row['description'] ?? null,
            'status' => $row['STATUS'] ?? $row['status'] ?? $row['FINALSTATUS'] ?? null,
        ];
    }

    /** Normalize invoice STATUS/FINALSTATUS/GSTATUS from GetInvoiceDetails. */
    public static function invoiceStatus(mixed $body): ?string
    {
        $row = self::firstResult($body);
        if ($row === null) {
            return null;
        }

        foreach (['FINALSTATUS', 'GSTATUS', 'TRFINALSTATUS', 'STATUS', 'status'] as $key) {
            if (! empty($row[$key])) {
                return (string) $row[$key];
            }
        }

        return null;
    }

    public static function invoiceLooksPaid(mixed $body): bool
    {
        $row = self::firstResult($body);
        if ($row === null) {
            return false;
        }

        $candidates = [
            $row['FINALSTATUS'] ?? null,
            $row['GSTATUS'] ?? null,
            $row['TRFINALSTATUS'] ?? null,
            $row['STATUS'] ?? null,
            $row['status'] ?? null,
            $row['GMESSAGE'] ?? null,
        ];

        foreach ($candidates as $value) {
            $v = strtoupper(trim((string) $value));
            if ($v === '') {
                continue;
            }

            if (in_array($v, [
                'PAID',
                'SUCCESS',
                'SUCCESSFUL',
                'COMPLETED',
                'COMPLETE',
                'APPROVED',
                'MMCAPTURED',
                'MM-CAPTURED',
                'CAPTURED',
            ], true)) {
                return true;
            }

            if (str_contains($v, 'CAPTURED') || str_contains($v, 'SUCCESS')) {
                return true;
            }
        }

        return false;
    }

    public static function extractOrderCodeFromCallback(array $payload): ?string
    {
        $candidates = [
            data_get($payload, 'orderCode'),
            data_get($payload, 'ORDERCODE'),
            data_get($payload, 'ordercode'),
            data_get($payload, 'order.orderCode'),
            data_get($payload, 'order_code'),
            data_get($payload, 'orderid'),
            data_get($payload, 'transaction_id'),
            data_get($payload, 'TRANSACTIONID'),
            data_get(self::firstResult($payload), 'ORDERCODE'),
            data_get(self::unwrap($payload), 'ORDERCODE'),
            data_get(self::unwrap($payload), 'orderCode'),
        ];

        foreach ($candidates as $code) {
            if (is_string($code) && $code !== '') {
                return $code;
            }
        }

        return null;
    }

    public static function extractPaymentCodeFromCallback(array $payload): ?string
    {
        $candidates = [
            data_get($payload, 'paymentCode'),
            data_get($payload, 'PAYMENTCODE'),
            data_get($payload, 'payment_code'),
            data_get($payload, 'PAYMENTTOKEN'),
            data_get($payload, 'paymentToken'),
            data_get($payload, 'paytoken'),
            data_get($payload, 'payToken'),
            data_get(self::firstResult($payload), 'PAYMENTCODE'),
            data_get(self::firstResult($payload), 'PAYMENTTOKEN'),
        ];

        foreach ($candidates as $code) {
            if (is_string($code) && $code !== '') {
                return $code;
            }
        }

        return null;
    }
}

<?php

namespace App\Services\CalPay;

readonly class CalPayPaymentContext
{
    public function __construct(
        public float $amount,
        public string $orderCode,
        public string $description,
        public string $itemName,
        public string $itemCode,
        public string $clientSlug,
        public string $providerSlug,
        public string $paymentType,
        public string $payableReference,
        public ?int $purchaseId = null,
        public ?int $pickupId = null,
        public ?string $otherData = null,
    ) {}
}

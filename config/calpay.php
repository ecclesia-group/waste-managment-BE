<?php

return [
    'endpoint' => env('CALPAY_ENDPOINT', 'https://calpayapi.caleservice.net/api/calpay'),
    'x_auth' => env('CALPAY_X_AUTH'),

    'merchant' => [
        'email_or_mobile' => env('CALPAY_MERCHANT_EMAIL', 'CALPAYOFFICIAL@CALBANK.NET'),
        'api_key' => env('CALPAY_MERCHANT_API_KEY'),
        'type' => env('CALPAY_MERCHANT_TYPE', 'EMAIL'),
        'env' => env('CALPAY_MERCHANT_ENV', 'LIVE'),
        // Optional settlement fields (docs mark required; sample often uses empty string)
        'destination_account' => env('CALPAY_DESTINATION_ACCOUNT', ''),
        'sb_merchant_id' => env('CALPAY_SB_MERCHANT_ID', ''),
    ],

    'payment' => [
        'account_type' => env('CALPAY_PAYMENT_ACCOUNT_TYPE', ''),
        'account_number' => env('CALPAY_PAYMENT_ACCOUNT_NUMBER', ''),
        'mode' => env('CALPAY_PAYMENT_MODE', ''),
    ],

    'callback_url' => env('CALPAY_CALLBACK_URL', env('APP_URL').'/api/payment_callback'),

    'defaults' => [
        'currency' => 'GHS',
        'customer_country' => 'GHA',
        'customer_city' => 'ACCRA',
        'pay_option' => 'ALL',
        'card_mode' => 'PURCHASE',
        'send_invoice' => 'FALSE',
    ],
];

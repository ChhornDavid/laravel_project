<?php

return [
    'api_url' => env('ABA_PAYWAY_API_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase'),
    'api_key' => env('ABA_PAYWAY_API_KEY', '4b24aa6fc5f6c1857177a4e3bb57495e9d94fe22'),
    'merchant_id' => env('ABA_PAYWAY_MERCHANT_ID', 'ec449352'),
];
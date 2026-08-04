<?php

return [
    'enabled' => (bool) env('CANADA_POST_ENABLED', false),
    'environment' => env('CANADA_POST_ENVIRONMENT', 'sandbox'),
    'api_key' => env('CANADA_POST_API_KEY'),
    'api_secret' => env('CANADA_POST_API_SECRET'),
    'customer_number' => env('CANADA_POST_CUSTOMER_NUMBER'),
    'contract_id' => env('CANADA_POST_CONTRACT_ID'),
    'origin_postal_code' => env('CANADA_POST_ORIGIN_POSTAL_CODE', 'L4W2S1'),
    'default_item_weight_kg' => (float) env('CANADA_POST_DEFAULT_ITEM_WEIGHT_KG', 0.25),
    'maximum_package_weight_kg' => (float) env('CANADA_POST_MAX_PACKAGE_WEIGHT_KG', 30),
    'timeout_seconds' => (int) env('CANADA_POST_TIMEOUT_SECONDS', 12),
];

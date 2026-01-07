<?php

return [
    'api_key' => env('CLICKPESA_API_KEY', ''),
    'client_id' => env('CLICKPESA_CLIENT_ID', ''),
    'environment' => env('CLICKPESA_ENVIRONMENT', 'sandbox'), // sandbox or live
    'callback_url' => env('CLICKPESA_CALLBACK_URL', ''),
    'currency' => env('CLICKPESA_CURRENCY', 'TZS'),
    
    // Logging configuration
    'logging' => [
        'enabled' => env('CLICKPESA_LOGGING_ENABLED', true),
        'channel' => env('CLICKPESA_LOGGING_CHANNEL', 'stack'),
    ],
    
    // Signature verification for callbacks
    'verify_signature' => env('CLICKPESA_VERIFY_SIGNATURE', false),
];

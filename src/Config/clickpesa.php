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
    
    // Caching configuration
    'cache' => [
        'enabled' => env('CLICKPESA_CACHE_ENABLED', true),
        'driver' => env('CLICKPESA_CACHE_DRIVER', 'default'), // uses Laravel's default cache driver
        'ttl' => env('CLICKPESA_CACHE_TTL', 3600), // 1 hour - matches JWT token validity
        'preview_enabled' => env('CLICKPESA_CACHE_PREVIEW_ENABLED', true),
        'preview_ttl' => env('CLICKPESA_CACHE_PREVIEW_TTL', 300), // 5 minutes
    ],
    
    // Signature verification for callbacks
    'verify_signature' => env('CLICKPESA_VERIFY_SIGNATURE', false),
];

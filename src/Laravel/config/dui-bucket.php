<?php

return [
    // Base API endpoint for the bucket microservice
    'api_url'         => env('DUI_BUCKET_SDK_ENDPOINT', ''),
    // Service token for authenticating requests
    'api_key'         => env('DUI_BUCKET_SDK_API_KEY', ''),
    // Default bucket/folder to use when none specified
    'default_bucket'  => env('DUI_BUCKET_DEFAULT_BUCKET', 'public'),
    // Whether to encrypt files by default
    'encryption'      => env('DUI_BUCKET_ENCRYPTION', false),
    // Toggle SDK-driven error logging
    'log_enabled'     => env('DUI_BUCKET_LOG_ENABLED', false),
    // Name of the logging channel
    'log_channel'     => env('DUI_BUCKET_LOG_CHANNEL', 'dui_bucket'),
    // Extend with bucket-specific size/mime limits or TTLs if needed
    'disable_ssl_verify'     => env('DUI_DISABLE_SSL_VERIFY', false),
];

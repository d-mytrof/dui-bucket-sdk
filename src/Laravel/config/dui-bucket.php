<?php

return [
    'domain' => env('DUI_BUCKET_DOMAIN', ''),

    // --- API Configuration ---
    'api_url' => env('DUI_BUCKET_ENDPOINT', ''),
    'api_key' => env('DUI_BUCKET_API_KEY', ''),
    'default_bucket' => env('DUI_BUCKET_DEFAULT_BUCKET', 'public'),

    // --- API Key Provider Configuration ---
    'api_key_provider' => env('DUI_BUCKET_API_KEY_PROVIDER', 'env'), // 'env' or 'database'
    'database_client_name' => env('DUI_BUCKET_DB_CLIENT_NAME'),
    'database_model_class' => env('DUI_BUCKET_DB_MODEL_CLASS', 'App\Models\ApiKeyClient'),

    // --- Encryption ---
    'cookie_secret_key' => env('DUI_BUCKET_COOKIE_SECRET_KEY'),
    'cookie_iv_secret' => env('DUI_BUCKET_COOKIE_IV_SECRET'),

    // --- Logging & Debugging ---
    'log_enabled' => env('DUI_BUCKET_LOG_ENABLED', false),
    'log_channel' => env('DUI_BUCKET_LOG_CHANNEL', 'dui_bucket'),

    // --- Misc ---
    'encryption' => env('DUI_BUCKET_ENCRYPTION', false),
    'disable_ssl_verify' => env('DUI_BUCKET_DISABLE_SSL_VERIFY', false),
    'environment' => env('DUI_BUCKET_DEFAULT_ENVIRONMENT', ''),
    'service' => env('DUI_BUCKET_DEFAULT_SERVICE', ''),
];

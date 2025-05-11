<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Laravel;

use dmytrof\DuiBucketSDK\Logging\LoggerInterface;
use Illuminate\Support\Facades\Log;

class LaravelLogger implements LoggerInterface
{
    public function log(string $level, string $message, array $context = []): void
    {
        Log::channel(config('dui-bucket.log_channel', 'stack'))->{$level}($message, $context);
    }
}

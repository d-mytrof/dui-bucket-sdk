<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Laravel;

use dmytrof\DuiBucketSDK\Logging\LoggerInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class LaravelLogger implements LoggerInterface
{
    public function log(string $level, string $message, array $context = []): void
    {
        $channel = 'stack';

        if (Config::has('dui-bucket.log_channel')) {
            $channel = Config::get('dui-bucket.log_channel', 'stack');
        }

        Log::channel($channel)->{$level}($message, $context);
    }
}

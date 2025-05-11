<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Laravel;

use dmytrof\DuiBucketSDK\Config\Config;
use dmytrof\DuiBucketSDK\Http\BucketClient;
use dmytrof\DuiBucketSDK\Logging\ErrorManager;
use dmytrof\DuiBucketSDK\Logging\LogManager;
use dmytrof\DuiBucketSDK\Logging\LoggerInterface;
use Illuminate\Support\Facades\App;

class DuiBucket
{
    protected static ?ErrorManager $errorManager = null;
    protected static ?LogManager $logManager = null;

    public static function getErrorManager(): ErrorManager
    {
        if (!static::$errorManager) {
            static::$errorManager = new ErrorManager(
                App::make(BucketClient::class),
                App::make(LoggerInterface::class)
            );
        }

        return static::$errorManager;
    }

    public static function getLogManager(): LogManager
    {
        if (!static::$logManager) {
            static::$logManager = new LogManager(
                App::make(BucketClient::class),
                App::make(LoggerInterface::class)
            );
        }

        return static::$logManager;
    }
}

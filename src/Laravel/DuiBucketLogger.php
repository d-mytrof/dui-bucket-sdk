<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Laravel;

use Monolog\Logger;
use dmytrof\DuiBucketSDK\Laravel\DuiBucketLoggerHandler;

class DuiBucketLogger
{
    public function __invoke(array $config): Logger
    {
        return new Logger('dui_bucket', [
            new DuiBucketLoggerHandler(),
        ]);
    }
}

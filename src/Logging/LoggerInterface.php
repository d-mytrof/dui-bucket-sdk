<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Logging;

interface LoggerInterface
{
    public function log(string $level, string $message, array $context = []): void;
}
<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Yii2;

use dmytrof\DuiBucketSDK\Logging\LoggerInterface;
use yii\log\Logger;

class YiiLogger implements LoggerInterface
{
    protected const LEVELS = [
        'emergency' => Logger::LEVEL_ERROR,
        'alert'     => Logger::LEVEL_ERROR,
        'critical'  => Logger::LEVEL_ERROR,
        'error'     => Logger::LEVEL_ERROR,
        'warning'   => Logger::LEVEL_WARNING,
        'notice'    => Logger::LEVEL_INFO,
        'info'      => Logger::LEVEL_INFO,
        'debug'     => Logger::LEVEL_TRACE,
    ];

    public function log(string $level, string $message, array $context = []): void
    {
        $mappedLevel = self::LEVELS[$level] ?? Logger::LEVEL_INFO;
        \Yii::getLogger()->log($message, $mappedLevel, $context);
    }
}
<?php
namespace dmytrof\DuiBucketSDK\Yii2;

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

use yii\log\Target;
use yii\log\Logger;

class FileLoggerTarget extends Target
{
    public $exportInterval = 1;

    protected const LEVEL_NAMES = [
        Logger::LEVEL_ERROR   => 'error',
        Logger::LEVEL_WARNING => 'warning',
        Logger::LEVEL_INFO    => 'info',
        Logger::LEVEL_TRACE   => 'debug',
        Logger::LEVEL_PROFILE_BEGIN => 'profile_begin',
        Logger::LEVEL_PROFILE_END   => 'profile_end',
    ];

    public function export(): void
    {
        $sdk = \Yii::$app->duiBucket;

        foreach ($this->messages as $message) {
            [$text, $level, $category, $timestamp] = $message;
            $trace = $message[4] ?? null;
            $context = $message[5] ?? [];

            $payload = [
                'message'   => $text,
                'level'     => self::LEVEL_NAMES[$level] ?? 'info',
                'trace_log' => isset($context['trace_log'])
                    ? (is_array($context['trace_log']) ? json_encode($context['trace_log']) : $context['trace_log'])
                    : (is_array($trace) ? json_encode($trace) : $trace),
            ];

            $sdk->getErrorManager()->save(
                $payload['message'],
                $payload
            );
        }
    }
}

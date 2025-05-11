<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Laravel;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Illuminate\Support\Facades\Config;
use dmytrof\DuiBucketSDK\Logging\ErrorManager;

class DuiBucketLoggerHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        if (
            $record->channel === Config::get('dui-bucket.log_channel') ||
            ($record->context['internal'] ?? false) === true
        ) {
            return;
        }

        $context = array_merge($record->context ?? [], ['internal' => true]);

        app(ErrorManager::class)->save($record->message, [
            'level'     => strtolower($record->level->getName()),
            'context'   => $context,
            'trace_log' => $record->extra['trace'] ?? '',
        ]);
    }
}

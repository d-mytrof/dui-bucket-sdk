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

        $context = array_merge($record->context ?? []);
        $traceLog = $context['trace_log'] ?? ($record->extra['trace'] ?? '');
        if (isset($context['trace_log'])) {
            unset($context['trace_log']);
        }
        if (isset($context['trace'])) {
            unset($context['trace']);
        }

        if (empty($traceLog)) {
            $traceLog = $context['trace_log']
                ?? (isset($context['exception']) && $context['exception'] instanceof \Throwable
                    ? $context['exception']->getTraceAsString()
                    : ($record->extra['trace'] ?? ''));
        }

        $fullUrl = null;
        if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
            $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
                . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        }

        if (!empty($fullUrl)) {
            app(ErrorManager::class)->save($record->message, [
                'level'     => strtolower($record->level->getName()),
                'context'   => $context,
                'trace_log' => $traceLog,
                'environment' => Config::get('dui-bucket.environment'),
                'service' => Config::get('dui-bucket.service'),
                'url' => $fullUrl,
            ]);
        }
    }
}

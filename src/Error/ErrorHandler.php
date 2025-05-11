<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Error;

use Throwable;
use dmytrof\DuiBucketSDK\Logging\LoggerInterface;
use dmytrof\DuiBucketSDK\Http\BucketClient;

final class ErrorHandler
{
    public static function register(LoggerInterface $logger, BucketClient $client): void
    {
        set_exception_handler(function (Throwable $e) use ($logger, $client) {
            $message = $e->getMessage();
            $logger->log('error', $message, ['trace' => $e->getTraceAsString()]);
            $client->sendError($message, $e->getTraceAsString());
        });

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($logger, $client) {
            $message = "$errstr in $errfile on line $errline";
            $logger->log('error', $message);
            $client->sendError($message);
        });

        register_shutdown_function(function () use ($logger, $client) {
            $error = error_get_last();
            if ($error) {
                $message = $error['message'] ?? 'Shutdown error';
                $logger->log('critical', $message, $error);
                $client->sendError($message, json_encode($error));
            }
        });
    }
}
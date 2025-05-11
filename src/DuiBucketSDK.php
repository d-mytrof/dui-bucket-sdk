<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK;

use dmytrof\DuiBucketSDK\Config\Config;
use dmytrof\DuiBucketSDK\Error\ErrorHandler;
use dmytrof\DuiBucketSDK\Http\BucketClient;
use dmytrof\DuiBucketSDK\Logging\LoggerInterface;
use dmytrof\DuiBucketSDK\Logging\FileLogger;
use dmytrof\DuiBucketSDK\Upload\FileUploader;
use dmytrof\DuiBucketSDK\Bucket\BucketManager;
use dmytrof\DuiBucketSDK\Logging\LogManager;
use dmytrof\DuiBucketSDK\Logging\ErrorManager;

final class DuiBucketSDK
{
    private static Config $config;
    private static LoggerInterface $logger;
    private static BucketClient $client;
    private static FileUploader $uploader;
    private static BucketManager $bucketManager;
    private static LogManager $logManager;
    private static ErrorManager $errorManager;

    public static function init(array $config): void
    {
        self::$config = new Config($config);
        self::$logger = new FileLogger(self::$config->get('log_path'));
        self::$client = new BucketClient(self::$config, self::$logger);
        self::$uploader = new FileUploader(self::$client, self::$config, self::$logger);
        self::$bucketManager = new BucketManager(self::$client, self::$logger);
        self::$logManager = new LogManager(self::$client, self::$logger);
        self::$errorManager = new ErrorManager(self::$client, self::$logger);

        ErrorHandler::register(self::$logger, self::$client);
    }

    public static function getUploader(): FileUploader
    {
        return self::$uploader;
    }

    public static function getBucketManager(): BucketManager
    {
        return self::$bucketManager;
    }

    public static function getLogManager(): LogManager
    {
        return self::$logManager;
    }

    public static function getErrorManager(): ErrorManager
    {
        return self::$errorManager;
    }
}

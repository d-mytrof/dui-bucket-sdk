<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Laravel;

use Illuminate\Support\ServiceProvider;
use dmytrof\DuiBucketSDK\Config\Config;
use dmytrof\DuiBucketSDK\Http\BucketClient;
use dmytrof\DuiBucketSDK\Logging\FileLogger;
use dmytrof\DuiBucketSDK\Logging\ErrorManager;
use dmytrof\DuiBucketSDK\Logging\LogManager;
use dmytrof\DuiBucketSDK\Logging\LoggerInterface;
use dmytrof\DuiBucketSDK\Laravel\LaravelLogger;
use dmytrof\DuiBucketSDK\Laravel\DuiBucket;
use dmytrof\DuiBucketSDK\Error\ErrorHandler;
use dmytrof\DuiBucketSDK\Helpers\DuiEncryption;

class DuiBucketServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/dui-bucket.php', 'dui-bucket');

        $this->app->singleton(Config::class, function () {
            return new Config([
                'x_api_key'            => config('dui-bucket.api_key'),
                'api_base_url'       => config('dui-bucket.api_url'),
                'default_bucket'     => config('dui-bucket.default_bucket'),
                'log_enabled'        => config('dui-bucket.log_enabled'),
                'log_channel'        => config('dui-bucket.log_channel'),
                'disable_ssl_verify' => config('dui-bucket.disable_ssl_verify'),
            ]);
        });

        $this->app->singleton(LoggerInterface::class, function () {
            return new LaravelLogger();
        });

        $this->app->singleton(DuiEncryption::class, function () {
            return new DuiEncryption();
        });

        $this->app->singleton(BucketClient::class, function ($app) {
            return new BucketClient(
                $app->make(Config::class),
                $app->make(LoggerInterface::class),
                $app->make(DuiEncryption::class),
            );
        });

        $this->app->singleton(ErrorManager::class, function ($app) {
            return new ErrorManager(
                $app->make(BucketClient::class),
                $app->make(LoggerInterface::class)
            );
        });

        $this->app->singleton(LogManager::class, function ($app) {
            return new LogManager(
                $app->make(BucketClient::class),
                $app->make(LoggerInterface::class)
            );
        });

        $this->app->alias(DuiBucket::class, 'dui-bucket-sdk');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/dui-bucket.php' => config_path('dui-bucket.php'),
        ], 'dui-bucket-config');

        ErrorHandler::register(
            $this->app->make(LoggerInterface::class),
            $this->app->make(BucketClient::class)
        );
    }
}

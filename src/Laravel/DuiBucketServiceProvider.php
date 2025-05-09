<?php

namespace dmytrof\DuiBucketSDK\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use dmytrof\DuiBucketSDK\Config\Config as DuiConfig;
use dmytrof\DuiBucketSDK\DuiBucketSDK;
use dmytrof\DuiBucketSDK\Http\BucketClient;
use dmytrof\DuiBucketSDK\Error\ErrorHandler;
use dmytrof\DuiBucketSDK\Logging\FileLogger;

class DuiBucketServiceProvider extends ServiceProvider
{
    /**
     * Register Config, SDK, HTTP client, and custom log channel.
     */
    public function register()
    {
        // Merge the package configuration file
        $this->mergeConfigFrom(__DIR__ . '/config/dui-bucket.php', 'dui-bucket');

        // Bind the Config instance (array → Config object)
        $this->app->singleton(DuiConfig::class, function ($app) {
            return new DuiConfig($app['config']->get('dui-bucket'));
        });

        // Bind the core SDK singleton (accepts array internally)
        $this->app->singleton('dui-bucket-sdk', function ($app) {
            return new DuiBucketSDK($app['config']->get('dui-bucket'));
        });

        // Bind the HTTP client for SDK and error reporting
        $this->app->singleton(BucketClient::class, function ($app) {
            $config = $app->make(DuiConfig::class);
            $logger = $app->make(LoggerInterface::class);
            return new BucketClient($config, $logger);
        });

        // Extend Laravel logging with a custom FileLogger channel
        Log::extend('dui_bucket', function ($app, $config) {
            $apiUrl = $config['api_url'] ?? $app['config']->get('dui-bucket.api_url');
            $apiKey = $config['api_key'] ?? $app['config']->get('dui-bucket.api_key');
            $level  = $config['level']   ?? 'error';
            return new FileLogger($apiUrl, $apiKey, $level);
        });
    }

    /**
     * Publish configuration and register the global exception handler.
     */
    public function boot()
    {
        // Publish the config file when running in console
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/dui-bucket.php' => config_path('dui-bucket.php'),
            ], 'dui-bucket-config');
        }

        // Load package config
        $cfg = $this->app['config']->get('dui-bucket');

        // Choose logger: custom channel or default PSR-3 logger
        if (!empty($cfg['log_enabled'])) {
            $logger = Log::channel($cfg['log_channel']);
        } else {
            $logger = $this->app->make(LoggerInterface::class);
        }

        // Resolve the HTTP client
        $client = $this->app->make(BucketClient::class);

        // Register the global exception handler
        ErrorHandler::register($logger, $client);
    }
}

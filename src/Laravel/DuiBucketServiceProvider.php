<?php
namespace dmytrof\DuiBucketSDK\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use dmytrof\DuiBucketSDK\DuiBucketSDK;
use dmytrof\DuiBucketSDK\Http\BucketClient;
use dmytrof\DuiBucketSDK\Error\ErrorHandler;
use dmytrof\DuiBucketSDK\Logging\FileLogger;

class DuiBucketServiceProvider extends ServiceProvider
{
    /**
     * Register the SDK instance, HTTP client, and logging channel.
     */
    public function register()
    {
        // Merge the package configuration file
        $this->mergeConfigFrom(__DIR__.'/config/dui-bucket.php', 'dui-bucket');

        // Register the core SDK singleton
        $this->app->singleton('dui-bucket-sdk', function ($app) {
            $config = $app['config']->get('dui-bucket');
            return new DuiBucketSDK([
                'api_url'        => $config['api_url'],
                'api_key'        => $config['api_key'],
                'default_bucket' => $config['default_bucket'],
                'encryption'     => $config['encryption'],
            ]);
        });

        // Register the HTTP client used by the SDK and error handler
        $this->app->singleton(BucketClient::class, function ($app) {
            $config = $app['config']->get('dui-bucket');
            return new BucketClient($config['api_url'], $config['api_key']);
        });

        // Extend Laravel logging with the custom FileLogger channel
        Log::extend('dui_bucket', function ($app, $config) {
            // Allow overriding via the logging channel config
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
        // Publish the config file when running in the console
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/dui-bucket.php' => config_path('dui-bucket.php'),
            ], 'dui-bucket-config');
        }

        // Load package config
        $config = $this->app['config']->get('dui-bucket');

        // Choose the appropriate logger: custom channel or default PSR-3 logger
        if (!empty($config['log_enabled'])) {
            $logger = Log::channel($config['log_channel']);
        } else {
            $logger = $this->app->make(LoggerInterface::class);
        }

        // Resolve the HTTP client for error reporting
        $client = $this->app->make(BucketClient::class);

        // Register the global exception handler with the logger and HTTP client
        ErrorHandler::register($logger, $client);
    }
}

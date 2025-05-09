<?php
namespace dmytrof\DuiBucketSDK\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use dmytrof\DuiBucketSDK\DuiBucketSDK;
use dmytrof\DuiBucketSDK\Error\ErrorHandler;
use dmytrof\DuiBucketSDK\Logging\FileLogger;

class DuiBucketServiceProvider extends ServiceProvider
{
    /**
     * Register the SDK singleton and custom log channel.
     */
    public function register()
    {
        // Merge package config
        $this->mergeConfigFrom(__DIR__.'/config/dui-bucket.php', 'dui-bucket');

        // Bind SDK singleton
        $this->app->singleton('dui-bucket-sdk', function ($app) {
            $cfg = $app['config']->get('dui-bucket');
            return new DuiBucketSDK([
                'api_url'        => $cfg['api_url'],
                'api_key'        => $cfg['api_key'],
                'default_bucket' => $cfg['default_bucket'],
                'encryption'     => $cfg['encryption'],
            ]);
        });

        // Extend logging with FileLogger via SDK
        Log::extend('dui_bucket', function ($app, $config) {
            return new FileLogger(
                $config['api_url'],
                $config['api_key'],
                $config['level'] ?? 'error'
            );
        });
    }

    /**
     * Publish config and register the error handler.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/dui-bucket.php' => config_path('dui-bucket.php'),
            ], 'dui-bucket-config');
        }

        // Hook into global exception handling
        $handler = $this->app->make(ErrorHandler::class);
        $handler->setConfig($this->app['config']->get('dui-bucket'));
        $handler->register();
    }
}
<?php
namespace dmytrof\DuiBucketSDK\Yii2;

use yii\base\BootstrapInterface;
use yii\base\Application;
use yii\base\Module;
use dmytrof\DuiBucketSDK\DuiBucketSDK;
use dmytrof\DuiBucketSDK\Error\ErrorHandler;
use dmytrof\DuiBucketSDK\Logging\FileLogger;

class DuiBucketExtension extends Module implements BootstrapInterface
{
    /**
     * Bootstrap method to register the SDK component and error logging.
     *
     * @param Application $app
     */
    public function bootstrap($app)
    {
        // Register duiBucket component in Yii container
        $app->set('duiBucket', [
            'class'          => DuiBucketSDK::class,
            'api_url'        => getenv('DUI_BUCKET_API_URL'),
            'api_key'        => getenv('DUI_BUCKET_API_KEY'),
            'default_bucket' => getenv('DUI_BUCKET_DEFAULT_BUCKET') ?: 'public',
            'encryption'     => getenv('DUI_BUCKET_ENCRYPTION') !== 'false',
        ]);

        // Attach SDK-driven error logging after Yii handles exceptions
        $app->getErrorHandler()->on(\yii\web\ErrorEvent::EVENT_AFTER_HANDLE, function($event) use ($app) {
            $exception = $event->exception;
            // Log via SDK (assumes a logError method exists)
            $app->duiBucket->logError($exception);
        });

        // Optionally add a Yii log target for FileLogger
        if (getenv('DUI_BUCKET_LOG_ENABLED') === 'true') {
            $app->get('log')->targets['duiBucket'] = [
                'class'  => FileLogger::class,
                'levels' => ['error', 'warning'],
                'api_url' => getenv('DUI_BUCKET_API_URL'),
                'api_key' => getenv('DUI_BUCKET_API_KEY'),
            ];
        }
    }
}
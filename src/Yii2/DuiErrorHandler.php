<?php
/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Yii2;

use Yii;
use yii\web\ErrorHandler as BaseHandler;
use Throwable;
use yii\log\Logger;

class DuiErrorHandler extends BaseHandler
{
    public function handleException($exception)
    {
        try {
            if (Yii::$app->has('duiBucket')) {
                $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
                    . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                $sdk = Yii::$app->duiBucket->getClient();
                Yii::$app
                    ->duiBucket
                    ->getErrorManager()
                    ->save(
                        $exception->getMessage(),
                        [
                            'level'     => Logger::getLevelName(Logger::LEVEL_ERROR),
                            'trace_log' => $exception->getTrace(),
                            'context'   => [
                                'file' => $exception->getFile(),
                                'line' => $exception->getLine(),
                            ],
                            'environment'   => $sdk->getConfig()->get('environment'),
                            'service'   => $sdk->getConfig()->get('service'),
                            'url'   => $fullUrl,
                        ]
                    );
            }
        } catch (Throwable $e) {
            Yii::error('DuiBucket logging failed: ' . $e->getMessage(), __METHOD__);
        }

        parent::handleException($exception);
    }
}

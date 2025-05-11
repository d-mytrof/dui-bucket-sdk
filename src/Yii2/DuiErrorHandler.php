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

class ErrorHandler extends BaseHandler
{
    public function handleException($exception)
    {
        try {
            if (Yii::$app->has('duiBucket')) {
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
                        ]
                    );
            }
        } catch (Throwable $e) {
            Yii::error('DuiBucket logging failed: ' . $e->getMessage(), __METHOD__);
        }

        parent::handleException($exception);
    }
}

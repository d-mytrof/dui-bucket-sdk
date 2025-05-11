<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Yii2;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Application;
use yii\base\Module;
use dmytrof\DuiBucketSDK\DuiBucketSDK;

class DuiBucketExtension extends Module implements BootstrapInterface
{
    public function bootstrap($app)
    {
        $config = [
            'api_url' => getenv('DUI_BUCKET_SDK_ENDPOINT'),
            'api_key' => getenv('DUI_BUCKET_SDK_API_KEY'),
            'default_bucket' => getenv('DUI_BUCKET_DEFAULT_BUCKET'),
            'encryption' => getenv('DUI_BUCKET_ENCRYPTION') !== 'false',
            'disable_ssl_verify' => getenv('DUI_DISABLE_SSL_VERIFY'),
            'log_path' => Yii::getAlias('@runtime/logs/dui-bucket-sdk.log'),
        ];

        DuiBucketSDK::init($config);
    }
}

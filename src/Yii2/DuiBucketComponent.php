<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Yii2;

use yii\base\Component;
use dmytrof\DuiBucketSDK\Http\BucketClient;
use dmytrof\DuiBucketSDK\Config\Config as DuiConfig;
use dmytrof\DuiBucketSDK\Logging\ErrorManager;
use dmytrof\DuiBucketSDK\Logging\LogManager;
use dmytrof\DuiBucketSDK\Logging\FileLogger;
use dmytrof\DuiBucketSDK\Helpers\DuiEncryption;

class DuiBucketComponent extends Component
{
    public string $apiKey;
    public string $apiUrl;
    public string $defaultBucket;
    public bool $logEnabled = false;
    public string $logChannel = 'dui_bucket';
    public bool $disableSslVerify = false;
    public string $logDriver = 'yii'; // 'yii' or 'file'

    private ErrorManager $errorManager;
    private LogManager $logManager;

    public function init(): void
    {
        $this->apiKey = getenv('DUI_BUCKET_SDK_API_KEY') ?: $this->apiKey;
        $this->apiUrl = getenv('DUI_BUCKET_SDK_ENDPOINT') ?: $this->apiUrl;
        $this->defaultBucket = getenv('DUI_BUCKET_DEFAULT_BUCKET') ?: 'public';
        $this->logEnabled = filter_var(getenv('DUI_BUCKET_LOG_ENABLED'), FILTER_VALIDATE_BOOLEAN);
        $this->logChannel = getenv('DUI_BUCKET_LOG_CHANNEL') ?: 'dui_bucket';
        $this->disableSslVerify = filter_var(getenv('DUI_DISABLE_SSL_VERIFY'), FILTER_VALIDATE_BOOLEAN);

        $logger = match ($this->logDriver) {
            'file' => new FileLogger($this->apiUrl, $this->apiKey),
            default => new YiiLogger(),
        };

        $config = new DuiConfig([
            'x_api_key' => $this->apiKey,
            'api_base_url' => $this->apiUrl,
            'default_bucket' => $this->defaultBucket,
            'log_enabled' => $this->logEnabled,
            'log_channel' => $this->logChannel,
            'disable_ssl_verify' => $this->disableSslVerify,
        ]);

        $encryption = new DuiEncryption(
            getenv('DUI_BUCKET_COOKIE_SECRET_KEY') ?: null,
            getenv('DUI_BUCKET_COOKIE_IV_SECRET') ?: null
        );

        $client = new BucketClient($config, $logger, $encryption);

        $this->errorManager = new ErrorManager($client, $logger);
        $this->logManager = new LogManager($client, $logger);
    }

    public function getErrorManager(): ErrorManager
    {
        return $this->errorManager;
    }

    public function getLogManager(): LogManager
    {
        return $this->logManager;
    }
}

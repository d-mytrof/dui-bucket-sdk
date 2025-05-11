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
use dmytrof\DuiBucketSDK\Helpers\DuiEncryption;
use dmytrof\DuiBucketSDK\Logging\LoggerInterface;

class DuiBucketComponent extends Component
{
    public string $apiKey;
    public string $apiUrl;
    public string $defaultBucket;
    public bool $logEnabled = false;
    public string $logChannel = 'dui_bucket';
    public bool $disableSslVerify = false;

    private ErrorManager $errorManager;
    private LogManager $logManager;
    private BucketClient $client;

    public function init(): void
    {
        $this->apiKey = getenv('DUI_BUCKET_SDK_API_KEY') ?: $this->apiKey;
        $this->apiUrl = getenv('DUI_BUCKET_SDK_ENDPOINT') ?: $this->apiUrl;
        $this->defaultBucket = getenv('DUI_BUCKET_DEFAULT_BUCKET') ?: 'public';
        $this->logEnabled = filter_var(getenv('DUI_BUCKET_LOG_ENABLED'), FILTER_VALIDATE_BOOLEAN);
        $this->logChannel = getenv('DUI_BUCKET_LOG_CHANNEL') ?: 'dui_bucket';
        $this->disableSslVerify = filter_var(getenv('DUI_DISABLE_SSL_VERIFY'), FILTER_VALIDATE_BOOLEAN);

        $config = new DuiConfig([
            'x_api_key'         => $this->apiKey,
            'api_base_url'      => $this->apiUrl,
            'default_bucket'    => $this->defaultBucket,
            'log_enabled'       => $this->logEnabled,
            'log_channel'       => $this->logChannel,
            'disable_ssl_verify'=> $this->disableSslVerify,
        ]);

        $encryption = new DuiEncryption(
            getenv('DUI_BUCKET_COOKIE_SECRET_KEY') ?: null,
            getenv('DUI_BUCKET_COOKIE_IV_SECRET') ?: null
        );

        $this->client = new BucketClient($config, new class implements LoggerInterface {
            public function log(string $level, string $message, array $context = []): void {}
        }, $encryption);

        $logger = new class($this->client) implements LoggerInterface {
            private BucketClient $client;
            private bool $locked = false;

            public function __construct(BucketClient $client)
            {
                $this->client = $client;
            }

            public function log(string $level, string $message, array $context = []): void
            {
                if ($this->locked) return;
                $this->locked = true;

                try {
                    $this->client->request('POST', '/errors', [
                        'message'   => $message,
                        'level'     => $level,
                        'trace_log' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
                        'context'   => $context,
                    ]);
                } catch (\Throwable) {
                    // silent fail
                } finally {
                    $this->locked = false;
                }
            }
        };

        $this->errorManager = new ErrorManager($this->client, $logger);
        $this->logManager = new LogManager($this->client, $logger);
    }

    public function getErrorManager(): ErrorManager
    {
        return $this->errorManager;
    }

    public function getLogManager(): LogManager
    {
        return $this->logManager;
    }

    public function getClient(): BucketClient
    {
        return $this->client;
    }
}

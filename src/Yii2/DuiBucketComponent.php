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
use dmytrof\DuiBucketSDK\File\FileManager;
use dmytrof\DuiBucketSDK\Bucket\BucketManager;
use dmytrof\DuiBucketSDK\ApiKey\ApiKeyProviderInterface;
use dmytrof\DuiBucketSDK\ApiKey\EnvApiKeyProvider;

class DuiBucketComponent extends Component
{
    public string $apiKey = '';
    public string $apiUrl = '';
    public string $defaultBucket = 'public';
    public bool $logEnabled = false;
    public string $logChannel = 'dui_bucket';
    public bool $disableSslVerify = false;
    public string $environment = '';
    public string $service = '';
    public string $domain = '';

    // API Key Provider Configuration
    public string $apiKeyProvider = 'env'; // 'env' or 'database'
    public ?string $databaseClientName = null;
    public string $databaseModelClass = 'models\ApiKeyClient';

    /**
     * @var ApiKeyProviderInterface|null The API key provider
     */
    public ?ApiKeyProviderInterface $customApiKeyProvider = null;

    private ErrorManager $errorManager;
    private LogManager $logManager;
    private BucketClient $client;
    private FileManager $fileManager;
    private BucketManager $bucketManager;

    public function init(): void
    {
        parent::init();

        // Load configuration from environment variables
        $this->domain = getenv('DUI_BUCKET_DOMAIN') ?: $this->domain;
        $this->apiKey = getenv('DUI_BUCKET_API_KEY') ?: $this->apiKey;
        $this->apiUrl = getenv('DUI_BUCKET_ENDPOINT') ?: $this->apiUrl;
        $this->defaultBucket = getenv('DUI_BUCKET_DEFAULT_BUCKET') ?: $this->defaultBucket;
        $this->logEnabled = filter_var(getenv('DUI_BUCKET_LOG_ENABLED'), FILTER_VALIDATE_BOOLEAN) ?: $this->logEnabled;
        $this->logChannel = getenv('DUI_BUCKET_LOG_CHANNEL') ?: $this->logChannel;
        $this->disableSslVerify = filter_var(getenv('DUI_BUCKET_DISABLE_SSL_VERIFY'), FILTER_VALIDATE_BOOLEAN) ?: $this->disableSslVerify;
        $this->environment = getenv('DUI_BUCKET_DEFAULT_ENVIRONMENT') ?: $this->environment;
        $this->service = getenv('DUI_BUCKET_DEFAULT_SERVICE') ?: $this->service;

        // API Key Provider Configuration from environment
        $this->apiKeyProvider = getenv('DUI_BUCKET_API_KEY_PROVIDER') ?: $this->apiKeyProvider;
        $this->databaseClientName = getenv('DUI_BUCKET_DB_CLIENT_NAME') ?: $this->databaseClientName;
        $this->databaseModelClass = getenv('DUI_BUCKET_DB_MODEL_CLASS') ?: $this->databaseModelClass;

        $config = new DuiConfig([
            'x_api_key'         => $this->apiKey,
            'domain'            => $this->domain,
            'api_base_url'      => $this->apiUrl,
            'default_bucket'    => $this->defaultBucket,
            'log_enabled'       => $this->logEnabled,
            'log_channel'       => $this->logChannel,
            'disable_ssl_verify'=> $this->disableSslVerify,
            'environment'       => $this->environment,
            'service'           => $this->service,
        ]);

        $encryption = new DuiEncryption(
            getenv('DUI_BUCKET_COOKIE_SECRET_KEY') ?: null,
            getenv('DUI_BUCKET_COOKIE_IV_SECRET') ?: null
        );

        // Create API key provider based on configuration
        $apiKeyProvider = $this->createApiKeyProvider($config);

        $this->client = new BucketClient(
            $config,
            new class implements LoggerInterface {
                public function log(string $level, string $message, array $context = []): void {}
            },
            $encryption,
            $apiKeyProvider
        );

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
                    $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
                        . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                    $this->client->request('POST', '/errors', [
                        'message'   => $message,
                        'level'     => $level,
                        'trace_log' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
                        'context'   => $context,
                        'environment' => $this->client->getConfig()->get('environment'),
                        'service' => $this->client->getConfig()->get('service'),
                        'url'   => $fullUrl,
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
        $this->fileManager = new FileManager($this->client, $config, $logger);
        $this->bucketManager = new BucketManager($this->client, $logger);
    }

    private function createApiKeyProvider(DuiConfig $config): ApiKeyProviderInterface
    {
        // If custom provider is set, use it
        if ($this->customApiKeyProvider !== null) {
            return $this->customApiKeyProvider;
        }

        switch ($this->apiKeyProvider) {
            case 'database':
                if ($this->databaseClientName === null) {
                    throw new \RuntimeException('Database client name is required when using database API key provider');
                }

                $provider = new YiiApiKeyProvider(['apiKey' => $this->apiKey]);
                $provider->setDatabaseProvider($this->databaseClientName, $this->databaseModelClass);
                return $provider;

            case 'env':
            default:
                return new EnvApiKeyProvider('DUI_BUCKET_API_KEY', $config->get('x_api_key'));
        }
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

    public function getFileManager(): FileManager
    {
        return $this->fileManager;
    }

    public function getBucketManager(): BucketManager
    {
        return $this->bucketManager;
    }
}

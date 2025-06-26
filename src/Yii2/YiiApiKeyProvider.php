<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Yii2;

use dmytrof\DuiBucketSDK\ApiKey\ApiKeyProviderInterface;
use yii\base\Component;

/**
 * Yii2 implementation of ApiKeyProviderInterface
 *
 * This class can be extended to implement custom API key retrieval logic,
 * such as fetching the key from a database.
 */
class YiiApiKeyProvider extends Component implements ApiKeyProviderInterface
{
    /**
     * @var string|null The API key
     */
    public ?string $apiKey = null;

    /**
     * @var callable|null A callback function that returns the API key
     */
    private $apiKeyCallback = null;

    /**
     * Set a callback function that will be used to retrieve the API key
     *
     * This allows for dynamic API key retrieval, such as from a database
     *
     * @param callable $callback A function that returns the API key
     * @return void
     */
    public function setApiKeyCallback(callable $callback): void
    {
        $this->apiKeyCallback = $callback;
    }

    /**
     * Configure the provider to get API key from database by client name
     *
     * @param string $clientName The name of the API client
     * @param string $modelClass The ActiveRecord model class (default: 'models\ApiKeyClient')
     * @return void
     */
    public function setDatabaseProvider(string $clientName, string $modelClass = 'models\ApiKeyClient'): void
    {
        $this->setApiKeyCallback(function() use ($clientName, $modelClass) {
            if (!class_exists($modelClass)) {
                throw new \RuntimeException("Model class '{$modelClass}' not found");
            }

            $client = $modelClass::find()
                ->where(['name' => $clientName])
                ->andWhere(['status' => $modelClass::STATUS_ACTIVE])
                ->one();

            if (!$client) {
                throw new \RuntimeException("API client '{$clientName}' not found or inactive");
            }

            return $client->api_key;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKey(): string
    {
        // If a callback is set, use it to get the API key
        if ($this->apiKeyCallback !== null) {
            $apiKey = call_user_func($this->apiKeyCallback);
            if (is_string($apiKey) && $apiKey !== '') {
                return $apiKey;
            }
        }

        // Otherwise, use the apiKey property
        if ($this->apiKey === null || $this->apiKey === '') {
            throw new \RuntimeException('API key not provided');
        }

        return $this->apiKey;
    }
}

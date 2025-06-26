<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Laravel;

use dmytrof\DuiBucketSDK\ApiKey\ApiKeyProviderInterface;
use Illuminate\Contracts\Foundation\Application;

/**
 * Laravel implementation of ApiKeyProviderInterface
 *
 * This class can be used to retrieve the API key from various sources in a Laravel application,
 * such as the database, cache, or a custom service.
 */
class LaravelApiKeyProvider implements ApiKeyProviderInterface
{
    /**
     * @var string|null The API key
     */
    protected ?string $apiKey = null;

    /**
     * @var callable|null A callback function that returns the API key
     */
    protected $apiKeyCallback = null;

    /**
     * @var Application The Laravel application instance
     */
    protected Application $app;

    /**
     * Constructor
     *
     * @param Application $app The Laravel application instance
     * @param string|null $apiKey The API key (optional)
     */
    public function __construct(Application $app, ?string $apiKey = null)
    {
        $this->app = $app;
        $this->apiKey = $apiKey;
    }

    /**
     * Set a callback function that will be used to retrieve the API key
     *
     * This allows for dynamic API key retrieval, such as from a database
     *
     * @param callable $callback A function that returns the API key
     * @return self
     */
    public function setApiKeyCallback(callable $callback): self
    {
        $this->apiKeyCallback = $callback;
        return $this;
    }

    /**
     * Configure the provider to get API key from database by client name
     *
     * @param string $clientName The name of the API client
     * @param string $modelClass The Eloquent model class (default: 'App\Models\ApiKeyClient')
     * @return self
     */
    public function setDatabaseProvider(string $clientName, string $modelClass = 'App\Models\ApiKeyClient'): self
    {
        $this->setApiKeyCallback(function() use ($clientName, $modelClass) {
            if (!class_exists($modelClass)) {
                throw new \RuntimeException("Model class '{$modelClass}' not found");
            }

            $client = $modelClass::where('name', $clientName)
                ->where('status', $modelClass::STATUS_ACTIVE)
                ->first();

            if (!$client) {
                throw new \RuntimeException("API client '{$clientName}' not found or inactive");
            }

            return $client->api_key;
        });

        return $this;
    }

    /**
     * Set the API key directly
     *
     * @param string $apiKey The API key
     * @return self
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKey(): string
    {
        // If a callback is set, use it to get the API key
        if ($this->apiKeyCallback !== null) {
            $apiKey = call_user_func($this->apiKeyCallback, $this->app);
            if (is_string($apiKey) && $apiKey !== '') {
                return $apiKey;
            }
        }

        // If an API key is set directly, use it
        if ($this->apiKey !== null && $this->apiKey !== '') {
            return $this->apiKey;
        }

        // Otherwise, try to get it from the config
        $apiKey = config('dui-bucket.api_key');
        if (is_string($apiKey) && $apiKey !== '') {
            return $apiKey;
        }

        throw new \RuntimeException('API key not provided');
    }
}

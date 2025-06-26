<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\ApiKey;

/**
 * Default implementation of ApiKeyProviderInterface that retrieves the API key from an environment variable
 */
class EnvApiKeyProvider implements ApiKeyProviderInterface
{
    /**
     * @var string The name of the environment variable that contains the API key
     */
    private string $envName;

    /**
     * @var string|null Default API key to use if the environment variable is not set
     */
    private ?string $defaultApiKey;

    /**
     * Constructor
     * 
     * @param string $envName The name of the environment variable that contains the API key
     * @param string|null $defaultApiKey Default API key to use if the environment variable is not set
     */
    public function __construct(string $envName = 'DUI_BUCKET_API_KEY', ?string $defaultApiKey = null)
    {
        $this->envName = $envName;
        $this->defaultApiKey = $defaultApiKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKey(): string
    {
        $apiKey = getenv($this->envName);
        
        if ($apiKey === false && $this->defaultApiKey === null) {
            throw new \RuntimeException("API key not found in environment variable '{$this->envName}' and no default provided");
        }
        
        return $apiKey !== false ? $apiKey : $this->defaultApiKey;
    }
}
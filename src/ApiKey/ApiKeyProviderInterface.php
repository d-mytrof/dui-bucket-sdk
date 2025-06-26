<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\ApiKey;

/**
 * Interface for API key providers
 * 
 * Implementations of this interface are responsible for providing
 * the API key used for service-to-service authentication with the bucket service.
 */
interface ApiKeyProviderInterface
{
    /**
     * Get the API key for bucket service authentication
     * 
     * @return string The API key
     */
    public function getApiKey(): string;
}
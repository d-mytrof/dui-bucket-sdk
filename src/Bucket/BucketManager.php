<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Bucket;

use dmytrof\DuiBucketSDK\Http\BucketClient;
use dmytrof\DuiBucketSDK\Logging\LoggerInterface;

class BucketManager
{
    private BucketClient $client;
    private LoggerInterface $logger;

    public function __construct(BucketClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function create(string $name, array $options = []): array
    {
        $required = ['access', 'groups'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $options)) {
                throw new \InvalidArgumentException("Missing required field: $key");
            }
        }

        $payload = array_merge([
            'name' => $name,
            'access' => $options['access'],
            'groups' => $options['groups'],
        ], array_filter($options, fn($k) => !in_array($k, ['access', 'groups']), ARRAY_FILTER_USE_KEY));

        $response = $this->client->request('POST', '/buckets', $payload);

        $this->logger->log('info', 'Bucket create response', ['response' => $response]);

        return $response;
    }

    public function update(string $bucketName, array $fields): array
    {
        if (empty($fields)) {
            throw new \InvalidArgumentException("Update fields cannot be empty");
        }

        $response = $this->client->request('PUT', "/buckets/{$bucketName}", $fields);

        $this->logger->log('info', 'Bucket update response', ['response' => $response]);

        return $response;
    }

    public function delete(string $bucketName, string $confirmation = 'DELETE'): array
    {
        $response = $this->client->request('DELETE', "/buckets/{$bucketName}", [
            'confirmation' => $confirmation
        ]);

        $this->logger->log('info', 'Bucket delete response', ['response' => $response]);

        return $response;
    }

    public function stats(): array
    {
        $response = $this->client->request('GET', '/buckets/stats');

        $this->logger->log('info', 'Bucket stats response', ['response' => $response]);

        return $response;
    }

    public function list(array $query = []): array
    {
        $queryString = http_build_query($query);
        $uri = '/buckets' . ($queryString ? "?{$queryString}" : '');

        $response = $this->client->request('GET', $uri);

        $this->logger->log('info', 'Bucket list response', ['response' => $response]);

        return $response;
    }
}
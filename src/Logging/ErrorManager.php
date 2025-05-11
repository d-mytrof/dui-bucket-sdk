<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Logging;

use dmytrof\DuiBucketSDK\Http\BucketClient;
use dmytrof\DuiBucketSDK\Logging\LoggerInterface;

class ErrorManager
{
    public function __construct(
        private BucketClient $client,
        private LoggerInterface $logger,
    ) {}

    public function save(string $message, array $options = []): array
    {
        $payload = array_merge([
            'message' => $message,
        ], array_filter($options, fn($k) => in_array($k, ['trace_log', 'context', 'level']), ARRAY_FILTER_USE_KEY));

        $payload['trace_log'] = json_encode($payload['trace_log']);

        $response = $this->client->request('POST', '/errors', $payload);

        $this->logger->log('info', 'Error save response', ['response' => $response]);
        return $response;
    }

    public function index(array $filters = []): array
    {
        $query = http_build_query($filters);
        $uri = '/errors' . ($query ? '?' . $query : '');

        $response = $this->client->request('GET', $uri);
        $this->logger->log('info', 'Error index response', ['response' => $response]);
        return $response;
    }

    public function delete(array $filters = []): array
    {
        $response = $this->client->request('DELETE', '/errors', $filters);
        $this->logger->log('info', 'Error delete response', ['response' => $response]);
        return $response;
    }

    public function stats(): array
    {
        $response = $this->client->request('GET', '/errors/stats');
        $this->logger->log('info', 'Error stats response', ['response' => $response]);
        return $response;
    }
}

<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Logging;

use dmytrof\DuiBucketSDK\Http\BucketClient;
use dmytrof\DuiBucketSDK\Logging\LoggerInterface;

class LogManager
{
    public function __construct(
        private BucketClient $client,
        private LoggerInterface $logger,
    ) {}

    public function save(string $uid, string $action, array $metadata = []): array
    {
        $response = $this->client->request('POST', '/logs', [
            'uid' => $uid,
            'action' => $action,
            'metadata' => $metadata,
        ]);

        $this->logger->log('info', 'Log save response', ['response' => $response]);
        return $response;
    }

    public function index(array $filters = []): array
    {
        $query = http_build_query($filters);
        $uri = '/logs' . ($query ? '?' . $query : '');

        $response = $this->client->request('GET', $uri);
        $this->logger->log('info', 'Log index response', ['response' => $response]);
        return $response;
    }

    public function delete(array $filters = []): array
    {
        $response = $this->client->request('DELETE', '/logs', $filters);
        $this->logger->log('info', 'Log delete response', ['response' => $response]);
        return $response;
    }

    public function stats(): array
    {
        $response = $this->client->request('GET', '/logs/stats');
        $this->logger->log('info', 'Log stats response', ['response' => $response]);
        return $response;
    }
}

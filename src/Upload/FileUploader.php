<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Upload;

use dmytrof\DuiBucketSDK\Http\BucketClient;
use dmytrof\DuiBucketSDK\Config\Config;
use dmytrof\DuiBucketSDK\Logging\LoggerInterface;

class FileUploader
{
    private BucketClient $client;
    private Config $config;
    private LoggerInterface $logger;

    public function __construct(BucketClient $client, Config $config, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function upload(string $filePath, string $bucket, array $options = []): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File does not exist: $filePath");
        }

        $fileContent = file_get_contents($filePath);

        return $this->client->request('POST', "/upload/$bucket", [
            'filename' => basename($filePath),
            'content' => base64_encode($fileContent),
            'encrypt' => $options['encrypt'] ?? false,
        ]);
    }
}
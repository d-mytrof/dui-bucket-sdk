<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\File;

use dmytrof\DuiBucketSDK\Http\BucketClient;
use dmytrof\DuiBucketSDK\Config\Config;
use dmytrof\DuiBucketSDK\Logging\LoggerInterface;
use RuntimeException;

/**
 * SDK for interacting with the bucket file service.
 */
class FileManager
{
    private BucketClient $client;
    private Config $config;
    private LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param BucketClient    $client Guzzle client configured for bucket service base URI
     * @param Config          $config SDK configuration
     * @param LoggerInterface $logger PSR-compatible logger for error reporting
     */
    public function __construct(BucketClient $client, Config $config, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Upload a file to the specified bucket.
     *
     * @param string     $filePath Absolute path to the file to upload
     * @param string     $bucket   Name of the target bucket
     * @param array      $options  [
     *     'path'    => string|null Subpath inside the bucket,
     *     'name'    => string|null Custom filename with extension,
     *     'encrypt' => bool         Whether to encrypt the file before storage
     * ]
     *
     * @return array    Upload response data: bucket, uuid, name, size, mime_type, owner_uid, created_at, updated_at
     *
     * @throws RuntimeException If the file is unreadable or upload fails
     */
    public function upload(string $filePath, string $bucket, array $options = []): array
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException("File does not exist or is not readable: {$filePath}");
        }

        $multipart = [
            ['name' => 'bucket', 'contents' => $bucket],
            ['name' => 'file',   'contents' => fopen($filePath, 'r'), 'filename' => basename($filePath)],
            ['name' => 'encrypt','contents' => !empty($options['encrypt']) ? '1' : '0'],
        ];
        if (!empty($options['path'])) {
            $multipart[] = ['name' => 'path', 'contents' => $options['path']];
        }
        if (!empty($options['name'])) {
            $multipart[] = ['name' => 'name', 'contents' => $options['name']];
        }

        $response = $this->request('POST', '/files', ['multipart' => $multipart], 201);
        return $response['data'];
    }

    /**
     * Get information about a single file.
     *
     * @param string $bucketName Name of the bucket
     * @param string $fileUuid   UUID of the file
     *
     * @return array File information: bucket, uuid, name, size, mime_type, owner_uid, created_at, updated_at
     *
     * @throws RuntimeException On request failure
     */
    public function info(string $bucketName, string $fileUuid): array
    {
        $path = "/buckets/{$bucketName}/files/{$fileUuid}";
        return $this->request('GET', $path);
    }

    /**
     * Delete a file by bucket and UUID.
     *
     * @param string $bucketName Name of the bucket
     * @param string $fileUuid   UUID of the file
     *
     * @return bool True on successful deletion
     *
     * @throws RuntimeException On request failure
     */
    public function delete(string $bucketName, string $fileUuid): bool
    {
        $path = "/buckets/{$bucketName}/files/{$fileUuid}";
        $this->request('DELETE', $path);
        return true;
    }

    /**
     * List files in a bucket with optional filters and pagination.
     *
     * @param string $bucketName Name of the bucket
     * @param array  $query      Query parameters:
     *                            - perPage: int
     *                            - mime_type: string
     *                            - size_min: int
     *                            - size_max: int
     *                            - name: string
     *                            - owner_uid: string
     *                            - created_from: string (YYYY-MM-DD)
     *                            - created_to: string (YYYY-MM-DD)
     *                            - page: int
     *
     * @return array Paginated list: items, pageCount, perPage, page, total
     *
     * @throws RuntimeException On request failure
     */
    public function list(string $bucketName, array $query = []): array
    {
        $path = "/buckets/{$bucketName}/files";
        return $this->request('GET', $path, ['query' => $query]);
    }

    /**
     * Generate a signed or public download link for a single file.
     *
     * @param string $fileUuid UUID of the file
     *
     * @return string Download URL
     *
     * @throws RuntimeException On request failure
     */
    public function generateLink(string $fileUuid): string
    {
        $path = "/files/{$fileUuid}/link";
        $response = $this->request('GET', $path);
        return $response['url'];
    }

    /**
     * Download a file via signed URL validation.
     *
     * @param string $fileUuid UUID of the file
     * @param array  $query    Query parameters, e.g. ['signature' => '...']
     *
     * @return string Raw file contents
     *
     * @throws RuntimeException On request failure
     */
    public function download(string $fileUuid, array $query = []): string
    {
        $path = "/files/{$fileUuid}";
        $response = $this->client->request('GET', $path, ['query' => $query]);
        return $response->getBody()->getContents();
    }

    /**
     * Generate download links for multiple files.
     *
     * @param array $uuids Array of file UUIDs
     *
     * @return array Map of UUID to ['url' => string] or ['error' => string]
     *
     * @throws RuntimeException On request failure
     */
    public function generateLinks(array $uuids): array
    {
        $payload = ['uuids' => $uuids];
        $response = $this->request('POST', '/files/links', ['json' => $payload]);
        return $response['links'];
    }

    /**
     * List all files across buckets with filters and sorting.
     *
     * @param array $query Query parameters:
     *                     - perPage: int
     *                     - page: int
     *                     - sort_by: string
     *                     - sort_order: string
     *                     - bucket: string
     *                     - uuid: string
     *                     - owner_uid: string
     *                     - mime_type: string
     *                     - size_min: int
     *                     - size_max: int
     *                     - created_from: string (YYYY-MM-DD)
     *                     - created_to: string (YYYY-MM-DD)
     *
     * @return array Paginated list: items, pageCount, perPage, page, total
     *
     * @throws RuntimeException On request failure
     */
    public function listAll(array $query = []): array
    {
        return $this->request('GET', '/files', ['query' => $query]);
    }

    /**
     * Internal helper to send requests, decode JSON, check status and return data.
     *
     * @param string     $method         HTTP method
     * @param string     $uri            Request URI (relative to base path)
     * @param array      $options        Guzzle request options
     * @param int|null   $expectedStatus HTTP status code expected for success
     *
     * @return array|string Decoded JSON response or raw string
     *
     * @throws RuntimeException on unexpected status or decode error
     */
    private function request(string $method, string $uri, array $options = [], int $expectedStatus = null)
    {
        try {
            $response = $this->client->request($method, $uri, $options);
            $status = $response->getStatusCode();
            $body = (string) $response->getBody();
            $decoded = json_decode($body, true);

            if ($expectedStatus !== null && $status !== $expectedStatus) {
                $this->logger->error('Unexpected status', ['method' => $method, 'uri' => $uri, 'status' => $status, 'body' => $decoded]);
                throw new RuntimeException("Unexpected status code: {$status}");
            }

            return $decoded;
        } catch (\Exception $e) {
            $this->logger->error('Request failed', ['exception' => $e]);
            throw new RuntimeException('Request failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}

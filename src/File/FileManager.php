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
            ['name' => 'bucket',  'contents' => $bucket],
            ['name' => 'file',    'contents' => fopen($filePath, 'r'), 'filename' => basename($filePath)],
            ['name' => 'encrypt', 'contents' => !empty($options['encrypt']) ? '1' : '0'],
        ];
        if (!empty($options['path'])) {
            $multipart[] = ['name' => 'path', 'contents' => $options['path']];
        }
        if (!empty($options['name'])) {
            $multipart[] = ['name' => 'name', 'contents' => $options['name']];
        }

        $response = $this->request('POST', '/files', [
            'multipart' => $multipart
        ]);

        if (isset($response['data'])) {
            return $response['data'];
        }

        if (isset($response['message'])) {
            return $response['message'];
        }

        return $response;
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
        $path     = "/files/{$fileUuid}/link";
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
        // BucketClient::request only returns JSON arrays, so here we bypass it
        $path    = "/files/{$fileUuid}";
        $options = [];
        if (!empty($query)) {
            $options['query'] = $query;
        }
        $response = $this->client->request('GET', $path, $options);
        // Assuming the API returns raw body for downloads:
        return $response['body'] ?? '';
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
        $payload  = ['uuids' => $uuids];
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
     * Internal helper to send requests through BucketClient.
     *
     * @param string $method  HTTP method
     * @param string $uri     Request URI (relative)
     * @param array  $options [
     *     'multipart' => array,        // for file uploads
     *     'json'      => mixed,        // for JSON body
     *     'query'     => array,        // for query parameters
     *     'headers'   => array         // additional headers
     * ]
     *
     * @return array Decoded JSON response
     *
     * @throws RuntimeException on request failure
     */
    private function request(string $method, string $uri, array $options = []): array
    {
        // extract headers
        $headers = $options['headers'] ?? [];

        // append query to URI if present
        if (!empty($options['query'])) {
            $uri .= '?' . http_build_query($options['query']);
        }

        // determine body payload
        if (isset($options['multipart'])) {
            $body = ['multipart' => $options['multipart']];
        } elseif (isset($options['json'])) {
            $body = $options['json'];
        } else {
            $body = [];
        }

        // perform the request
        try {
            return $this->client->request($method, $uri, $body, $headers);
        } catch (\Exception $e) {
            $this->logger->log('error', 'Request failed: '.$e->getMessage(), [
                'method'    => $method,
                'uri'       => $uri,
                'options'   => $options,
                'exception' => $e
            ]);

            if ($e->getCode() >= 400) {
                $status = max(400, min(599, (int)$e->getCode()));
                if (!headers_sent()) {
                    http_response_code($status);
                    header('Content-Type: application/json');
                }
                echo json_encode([
                    'error'   => true,
                    'message' => $e->getMessage(),
                ]);
                exit;
            }

            return [];
        }
    }

    /**
     * @param string $uuid
     * @return string
     */
    public function getLink(string $uuid): string
    {
        return $this->config->get('domain') . '/files/' . $uuid;
    }
}

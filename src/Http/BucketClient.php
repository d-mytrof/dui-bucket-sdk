<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Http;

use dmytrof\DuiBucketSDK\Config\Config;
use dmytrof\DuiBucketSDK\Logging\LoggerInterface;
use dmytrof\DuiBucketSDK\Helpers\DuiEncryption;
use dmytrof\DuiBucketSDK\ApiKey\ApiKeyProviderInterface;
use dmytrof\DuiBucketSDK\ApiKey\EnvApiKeyProvider;
use RuntimeException;

class BucketClient
{
    private Config $config;
    private LoggerInterface $logger;
    private DuiEncryption $encryption;
    private ?string $userToken = null;
    private ApiKeyProviderInterface $apiKeyProvider;

    public function __construct(
        Config $config, 
        LoggerInterface $logger, 
        DuiEncryption $encryption, 
        ?ApiKeyProviderInterface $apiKeyProvider = null
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->encryption = $encryption;
        $this->apiKeyProvider = $apiKeyProvider ?? new EnvApiKeyProvider('DUI_BUCKET_API_KEY', $this->config->get('x_api_key'));
    }

    public function setUserToken(?string $token): void
    {
        $this->userToken = $token;
    }

    /**
     * Set the API key provider
     * 
     * @param ApiKeyProviderInterface $apiKeyProvider The API key provider
     * @return void
     */
    public function setApiKeyProvider(ApiKeyProviderInterface $apiKeyProvider): void
    {
        $this->apiKeyProvider = $apiKeyProvider;
    }

    /**
     * Get the current API key provider
     * 
     * @return ApiKeyProviderInterface The API key provider
     */
    public function getApiKeyProvider(): ApiKeyProviderInterface
    {
        return $this->apiKeyProvider;
    }

    public function request(string $method, string $uri, array $body = [], array $headers = []): array
    {
        $url       = rtrim($this->config->get('api_base_url'), '/') . '/' . ltrim($uri, '/');
        $sslVerify = !$this->config->get('disable_ssl_verify', false);

        // Build default headers
        $defaultHeaders = [
            'Accept: application/json',
            'Cookie: x-api-key=' . $this->encryption->encrypt($this->apiKeyProvider->getApiKey()),
        ];

        if ($this->userToken) {
            $defaultHeaders[] = 'Authorization: Bearer ' . $this->userToken;
        }

        $langHeader = null;
        if (!empty($_SERVER['HTTP_LANG'])) {
            $langHeader = $_SERVER['HTTP_LANG'];
        } elseif (function_exists('getallheaders')) {
            $all = getallheaders();
            if (!empty($all['Lang'])) {
                $langHeader = $all['Lang'];
            }
        }
        if ($langHeader) {
            $defaultHeaders[] = 'Lang: ' . $langHeader;
        }

        // Detect and prepare multipart/form-data vs JSON
        if (isset($body['multipart']) && is_array($body['multipart'])) {
            // Convert Guzzle 'multipart' spec into CURLFile and simple fields
            $postFields = [];
            foreach ($body['multipart'] as $part) {
                $name = $part['name'];
                $contents = $part['contents'];

                if (is_resource($contents)) {
                    // Stream resource => extract path and wrap in CURLFile
                    $meta     = stream_get_meta_data($contents);
                    $filePath = $meta['uri'];
                    $filename = $part['filename'] ?? basename($filePath);
                    $mime     = function_exists('mime_content_type')
                        ? mime_content_type($filePath)
                        : 'application/octet-stream';

                    $postFields[$name] = new \CURLFile($filePath, $mime, $filename);
                } else {
                    // Simple field
                    $postFields[$name] = $contents;
                }
            }
            // Let cURL set its own multipart Content-Type header with boundary
        } else {
            // JSON payload
            $defaultHeaders[] = 'Content-Type: application/json';
            $postFields      = json_encode($body);
        }

        $mergedHeaders = array_merge($defaultHeaders, $headers);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => $mergedHeaders,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_HEADER         => true,
        ]);

        $raw    = curl_exec($curl);
        $err    = curl_error($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $hdrSz  = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);

        if ($err) {
            $this->logger->log('error', 'cURL error', ['method'=>$method,'uri'=>$uri,'error'=>$err]);
            throw new RuntimeException("cURL error: {$err}");
        }

        $bodyContent = substr($raw, $hdrSz);
        $decoded     = json_decode($bodyContent, true) ?: [];

        if ($status >= 400) {
            $msg = $decoded['error'] ?? $decoded['message'] ?? "HTTP error {$status}";

            if (!$this->isLoggingRequest($uri)) {
                $this->logger->log('error', 'HTTP error response', [
                    'method' => $method,
                    'uri'    => $uri,
                    'status' => $status,
                    'body'   => $decoded,
                ]);
            }
        }

        return $decoded;
    }

    private function isLoggingRequest(string $uri): bool {
        return strpos($uri, '/errors') !== false;
    }

    public function sendError(string $message, string $trace = ''): void
    {
        $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
            . "://" . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
        $this->request('POST', '/errors', [
            'level' => 'error',
            'message' => $message,
            'trace' => $trace,
            'environment' => $this->config->get('environment'),
            'service' => $this->config->get('service'),
            'url' => $fullUrl,
        ]);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}

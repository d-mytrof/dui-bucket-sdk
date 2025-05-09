<?php

namespace dmytrof\DuiBucketSDK\Http;

use dmytrof\DuiBucketSDK\Config\Config;
use dmytrof\DuiBucketSDK\Logging\LoggerInterface;

class BucketClient
{
    private Config $config;
    private LoggerInterface $logger;
    private ?string $userToken = null;

    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function setUserToken(?string $token): void
    {
        $this->userToken = $token;
    }

    public function request(string $method, string $uri, array $body = [], array $headers = []): array
    {
        $url = rtrim($this->config->get('api_base_url'), '/') . '/' . ltrim($uri, '/');
        $sslVerify = !$this->config->get('disable_ssl_verify', false);

        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Cookie: x-api-key=' . $this->config->get('x_api_key'),
        ];

        if ($this->userToken) {
            $defaultHeaders[] = 'Authorization: Bearer ' . $this->userToken;
        }

        $mergedHeaders = array_merge($defaultHeaders, $headers);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $mergedHeaders,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_HEADER => true
        ]);

        $response = curl_exec($curl);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $bodyContent = substr($response, $headerSize);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        $this->logger->log('debug', 'HTTP request debug (cURL)', [
            'url' => $url,
            'method' => $method,
            'headers' => $mergedHeaders,
            'body' => $body,
            'status' => $status,
            'response_headers' => $header,
            'response_body' => $bodyContent,
            'curl_error' => $error,
        ]);

        if ($error) {
            throw new \RuntimeException("cURL error: $error");
        }

        return json_decode($bodyContent, true) ?? [];
    }

    public function sendError(string $message, string $trace = ''): void
    {
        $this->request('POST', '/errors', [
            'message' => $message,
            'trace' => $trace,
        ]);
    }
}
<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Logging;

use dmytrof\DuiBucketSDK\Http\BucketClient;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class FileLogger implements LoggerInterface
{
    private BucketClient $client;
    private string $minLevel;

    public function __construct(BucketClient $client, string $minLevel = LogLevel::ERROR)
    {
        $this->client = $client;
        $this->minLevel = $minLevel;
    }

    public function log($level, $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $trace = $context['trace_log'] ?? '';
        $extra = $context['context'] ?? array_diff_key($context, ['trace_log' => true]);

        $payload = [
            'message'   => $message,
            'trace_log' => $trace,
            'context'   => $extra,
        ];

        $this->client->request('POST', '/errors', $payload);
    }

    private function shouldLog(string $level): bool
    {
        $levels = [
            LogLevel::EMERGENCY => 0,
            LogLevel::ALERT     => 1,
            LogLevel::CRITICAL  => 2,
            LogLevel::ERROR     => 3,
            LogLevel::WARNING   => 4,
            LogLevel::NOTICE    => 5,
            LogLevel::INFO      => 6,
            LogLevel::DEBUG     => 7,
        ];

        return $levels[$level] <= $levels[$this->minLevel];
    }

    public function emergency($message, array $context = []): void { $this->log(LogLevel::EMERGENCY, $message, $context); }
    public function alert($message,     array $context = []): void { $this->log(LogLevel::ALERT,     $message, $context); }
    public function critical($message,  array $context = []): void { $this->log(LogLevel::CRITICAL,  $message, $context); }
    public function error($message,     array $context = []): void { $this->log(LogLevel::ERROR,     $message, $context); }
    public function warning($message,   array $context = []): void { $this->log(LogLevel::WARNING,   $message, $context); }
    public function notice($message,    array $context = []): void { $this->log(LogLevel::NOTICE,    $message, $context); }
    public function info($message,      array $context = []): void { $this->log(LogLevel::INFO,      $message, $context); }
    public function debug($message,     array $context = []): void { $this->log(LogLevel::DEBUG,     $message, $context); }
}

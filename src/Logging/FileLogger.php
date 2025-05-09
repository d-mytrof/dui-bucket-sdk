<?php

namespace dmytrof\DuiBucketSDK\Logging;

use DateTime;

class FileLogger implements LoggerInterface
{
    private string $logPath;

    public function __construct(string $logPath)
    {
        $this->logPath = $logPath;
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $entry = sprintf(
            "[%s] %s: %s %s\n",
            (new DateTime())->format(DateTime::ATOM),
            strtoupper($level),
            $message,
            json_encode($context)
        );
        file_put_contents($this->logPath, $entry, FILE_APPEND);
    }
}
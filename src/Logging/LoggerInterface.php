<?php

namespace dmytrof\DuiBucketSDK\Logging;

interface LoggerInterface
{
    public function log(string $level, string $message, array $context = []): void;
}
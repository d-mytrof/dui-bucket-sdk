<?php

namespace dmytrof\DuiBucketSDK\Yii2;

use Yii;
use yii\log\Target;
use yii\log\Logger;

class FileLoggerTarget extends Target
{
    private array $hashes = [];

    public function export(): void
    {
        $sdk = Yii::$app->duiBucket ?? null;
        if (!$sdk || !$sdk->getClient()) {
            return;
        }

        foreach ($this->messages as $message) {
            [$text, $level, $category, $timestamp] = $message;

            $hash = md5($text . $level);
            if (in_array($hash, $this->hashes, true)) {
                continue;
            }
            $this->hashes[] = $hash;

            $payload = [
                'message'   => $text,
                'level'     => Logger::getLevelName($level),
                'trace_log' => $this->extractTrace($message),
                'context'   => [],
            ];

            try {
                $sdk->getClient()->request('POST', '/errors', $payload);
            } catch (\Throwable $e) {
                //
            }
        }
    }

    private function extractTrace(array $message): string
    {
        $trace = $message[4] ?? null;

        if (is_array($trace)) {
            return json_encode($trace, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return is_string($trace) ? $trace : '';
    }
}

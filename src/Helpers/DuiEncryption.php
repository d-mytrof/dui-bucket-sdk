<?php

/**
 * @copyright Copyright © 2025 Dmytro Mytrofanov
 * @package dui-bucket-sdk
 * @version 1.0.0
 */

namespace dmytrof\DuiBucketSDK\Helpers;

use Exception;

class DuiEncryption
{
    private string $key;
    private string $iv;

    public function __construct(?string $key = null, ?string $iv = null)
    {
        $this->key = $key ?? getenv('DUI_BUCKET_COOKIE_SECRET_KEY') ?: '';
        $this->iv = $iv ?? getenv('DUI_BUCKET_COOKIE_IV_SECRET') ?: '';

        if (!$this->key || !$this->iv) {
            throw new Exception("DUI_BUCKET_COOKIE_SECRET_KEY and DUI_BUCKET_COOKIE_IV_SECRET must be set in environment variables.");
        }

        if (strlen($this->iv) !== 16) {
            throw new Exception("DUI_BUCKET_COOKIE_IV_SECRET must be exactly 16 bytes.");
        }
    }

    public function encrypt(string $data): string
    {
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $this->key, 0, $this->iv);
        if ($encrypted === false) {
            throw new Exception("Encryption failed.");
        }
        return base64_encode($encrypted);
    }

    public function decrypt(string $data): string
    {
        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            throw new Exception("Invalid base64 string.");
        }

        $decrypted = openssl_decrypt($decoded, 'AES-256-CBC', $this->key, 0, $this->iv);
        if ($decrypted === false) {
            throw new Exception("Decryption failed.");
        }
        return $decrypted;
    }
}

<?php

namespace App\Helpers;

/**
 * LogSanitizer — PRD FR-16
 *
 * Sanitizes sensitive credentials, private keys, passwords, and tokens before logging.
 */
class LogSanitizer
{
    /**
     * Keys that must always be masked.
     */
    public const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'private_key',
        'google_private_key',
        'GOOGLE_PRIVATE_KEY',
        'secret',
        'api_secret',
        'api_key',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'cookie',
        'set-cookie',
        'bearer',
    ];

    /**
     * Sanitize an array or scalar value recursively.
     *
     * @param mixed $data
     * @return mixed
     */
    public static function sanitize(mixed $data): mixed
    {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                if (is_string($key) && self::isSensitiveKey($key)) {
                    $sanitized[$key] = '[REDACTED]';
                } else {
                    $sanitized[$key] = self::sanitize($value);
                }
            }
            return $sanitized;
        }

        if (is_string($data)) {
            return self::sanitizeString($data);
        }

        return $data;
    }

    /**
     * Check if a given key name is sensitive.
     */
    public static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', '_'], '', $key));

        foreach (self::SENSITIVE_KEYS as $sensitive) {
            $normalizedSensitive = strtolower(str_replace(['-', '_'], '', $sensitive));
            if (str_contains($normalized, $normalizedSensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize sensitive patterns within a string (e.g. private keys, bearer tokens).
     */
    public static function sanitizeString(string $value): string
    {
        // Mask PEM private keys
        if (str_contains($value, 'BEGIN PRIVATE KEY') || str_contains($value, 'BEGIN RSA PRIVATE KEY')) {
            return '[REDACTED_PRIVATE_KEY]';
        }

        // Mask Bearer tokens
        $masked = preg_replace('/Bearer\s+[A-Za-z0-9_\-\.]+/i', 'Bearer [REDACTED_TOKEN]', $value);

        return $masked ?? $value;
    }
}

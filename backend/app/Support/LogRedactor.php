<?php

namespace App\Support;

/**
 * Masks secrets and personal data before anything is written to a log — used for integration
 * HTTP calls (headers, query, bodies) and any other context array.
 */
final class LogRedactor
{
    public const MASK = '[REDACTED]';

    /** Key fragments (case-insensitive, ignoring - and _) whose values are always masked. */
    private const SENSITIVE_KEYS = [
        'authorization', 'apikey', 'key', 'secret', 'token', 'password', 'passwd', 'signature',
        'cookie', 'setcookie', 'credential', 'phone', 'phonenumber', 'customerphone', 'email',
    ];

    public static function redact(mixed $value): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = is_string($key) && self::isSensitiveKey($key) ? self::MASK : self::redact($item);
            }

            return $result;
        }

        if (is_string($value)) {
            return self::redactString($value);
        }

        return $value;
    }

    /** Masks bearer tokens and key=value secrets inside free text (URLs, error messages). */
    public static function redactString(string $value): string
    {
        $value = preg_replace('/\b(Bearer|Basic)\s+[A-Za-z0-9._~+\/=\-|]+/i', '$1 '.self::MASK, $value);

        return preg_replace_callback(
            '/([?&;\s]|^)([A-Za-z0-9_\-]+)=([^&;\s]+)/',
            fn (array $m) => self::isSensitiveKey($m[2]) ? $m[1].$m[2].'='.self::MASK : $m[0],
            $value,
        );
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', '_', ' '], '', $key));

        foreach (self::SENSITIVE_KEYS as $fragment) {
            if ($normalized === $fragment || str_ends_with($normalized, $fragment) || str_starts_with($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
